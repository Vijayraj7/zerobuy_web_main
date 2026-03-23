<?php

namespace App\Http\Controllers\Shop;

use App\Services\Chat;
use App\Models\User;
use App\Models\Media;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Storage;
use App\Events\AdminProductRequestEvent;
use App\Http\Resources\ProductVariantResource;
use App\Models\Category;
use App\Repositories\FlashSaleRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * Display the product list.
     */
    public function index(Request $request)
    {
        // get category, sorting and search from request
        $category = $request->category;
        $priceSort = $request->price_sort;
        $quantitySort = $request->quantity_sort;
        $search = $request->input('product_search');

        if (blank($search)) {
            $search = data_get($request->input('search'), 'value', $request->input('search'));
        }

        $shop = generaleSetting('shop');

        // filter products based on category and search
        $query = $shop?->products()->with(['orderItems'])
        ->withCount(['variants', 'bulkItems', 'orderItems as total_sale_count'])
        ->when($category, function ($query) use ($category) {
            return $query->whereHas('categories', function ($query) use ($category) {
                return $query->where('category_id', $category);
            });
        })->when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%$search%");
        });

        if ($priceSort === 'low_to_high') {
            $query->orderBy('price', 'asc');
        } elseif ($priceSort === 'high_to_low') {
            $query->orderBy('price', 'desc');
        }

        if ($quantitySort === 'low_to_high') {
            $query->orderBy('quantity', 'asc');
        } elseif ($quantitySort === 'high_to_low') {
            $query->orderBy('quantity', 'desc');
        }

        if (blank($priceSort) && blank($quantitySort)) {
            $query->latest();
        } else {
            $query->latest('id');
        }
        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('created_date', fn($row) => $row->created_at?->format('d-m-Y | h:i A') ?? '-')
                ->addColumn('product_code', fn($row) => '#'.($row->product_code ?? '-'))
                ->addColumn('name', fn($row) => Str::limit($row->name, 50, '...'))
                ->addColumn('thumbnail', function ($row) {
                    return '<img src="'.$row->thumbnail.'" width="40" height="40" class="rounded" loading="lazy">';
                })
                ->addColumn('quantity', function ($row) {
                    $quantity = (int) ($row->quantity ?? 0);
                    $minOrderQty = (int) ($row->min_order_quantity ?? 0);
                    $hasOptions = (int) ($row->variants_or_bulk_items_count ?? 0) > 0;
                    $isLowStock = ($quantity < $minOrderQty) && ! $hasOptions;

                    if ($isLowStock) {
                        return '<span class="text-danger fw-bold">' . $quantity . '</span>';
                    }

                    return (string) $quantity;
                })
                ->addColumn('mrp', fn($row) => showCurrency($row->price))
                ->addColumn('selling_price', fn($row) => showCurrency($row->discount_price))
                ->addColumn('total_sale_count', fn($row) => $row->total_sale_count ?? 0)
                ->addColumn('variants_count', fn($row) => $row->variants_or_bulk_items_count)
                ->addColumn('status', function ($row) {
                    $checked = $row->is_active ? 'checked' : '';
                    $disabled = $row->disabled_by_admin ? 'disabled' : '';
                    $title = $row->disabled_by_admin ? 'Disabled by admin' : 'Update product status';
                    $badge = $row->disabled_by_admin
                        ? '<span class="badge bg-danger mt-1">Disabled by Admin</span>'
                        : '';

                    return '<label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="'.$title.'">'
                        .'<a href="'.route('shop.product.toggle', $row->id).'">'
                        .'<input data-bs-title="'.$title.'" type="checkbox" '.$checked.' '.$disabled.'>'
                        .'<span class="slider round"></span>'
                        .'</a>'
                        .'</label>'
                        .$badge;
                })
                ->addColumn('action', function ($row) {
                    $view = '<a href="'.route('shop.product.show', $row->id).'" class="svg-bg btn-outline-primary circleIcon btn-sm" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="View Product">'
                        .'<img src="'.asset('assets/icons-admin/eye.svg').'" alt="icon" loading="lazy" />'
                        .'</a>';

                    $edit = '<a href="'.route('shop.product.edit', $row->id).'" class="btn-outline-info circleIcon btn-sm" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Edit Product">'
                        .'<img src="'.asset('assets/icons-admin/edit.svg').'" alt="icon" loading="lazy" />'
                        .'</a>';

                    return '<div class="d-flex justify-content-center gap-1">'.$view.$edit.'</div>';
                })
                ->rawColumns(['thumbnail', 'quantity', 'status', 'action'])
                ->make(true);
        }

        $products = $query->paginate(20)->withQueryString();

        // categories scoped to the shop's selected business categories
        $businessCategoryIds = $shop?->businessCategories()->pluck('business_categories.id') ?? collect();
        $categories = Category::whereIn('business_category_id', $businessCategoryIds)->get();

        $flashSale = FlashSaleRepository::getIncoming();

        return view('shop.product.index', compact('products', 'categories', 'flashSale'));
    }

    /**
     * Display the product details.
     */
    public function show(Product $product)
    {
        $product->load([
            'media',
            'medias',
            'orders',
            'reviews',
            'categories',
            'colors',
            'sizes',
            'variants.color',
            'variants.size',
            'itemDetails',
            'bulkItems',
            'bulkPrices',
        ]);

        return view('shop.product.show', compact('product'));
    }

    /**
     * crete new product.
     */
    public function create()
    {
        $shop = generaleSetting('shop');
        $rshop = generaleSetting('rootShop');

        // get brands, colors and categories
        $brands = $shop?->brands()->isActive()->get();
        $colors = $shop?->colors()->isActive()->get();
        $businessCategoryIds = $shop?->businessCategories()->pluck('business_categories.id') ?? collect();
        $categories = Category::whereIn('business_category_id', $businessCategoryIds)->get();
        $units = $shop?->units()->isActive()->get();
        $sizes = $shop?->sizes()->isActive()->get();

        return view('shop.product.edit', compact('brands', 'colors', 'categories', 'units', 'sizes'));
    }

    /**
     * store new product.
     */
    // public function store(ProductRequest $request)
    // {
    //     $shop = generaleSetting('shop');

    //     $skuCode = $shop?->products()->where('code', $request->code)->exists();

    //     if ($skuCode) {
    //         return back()->withInput()->withErrors(['code' => __('Product code already exists!')])->with('error', __('Product code already exists!'));
    //     }

    //     ProductRepository::storeByRequest($request);

    //     /** @var User $user */
    //     $user = auth()->user();
    //     $isRootUser = $user?->hasRole('root');

    //     // admin notification message
    //     if (! $isRootUser && generaleSetting('setting')->shop_type != 'single') {
    //         $message = 'New product Created Request';
    //         try {
    //             AdminProductRequestEvent::dispatch($message);
    //         } catch (\Throwable $th) {
    //         }

    //         $data = (object) [
    //             'title' => $message,
    //             'content' => 'New product Created Request from ' . $shop->name,
    //             'url' => '/admin/products?status=0',
    //             'icon' => 'bi-shop',
    //             'type' => 'success',
    //         ];
    //         // store notification
    //         NotificationRepository::storeByRequest($data);
    //     }

    //     return to_route('shop.product.index')->withSuccess(__('Product created successfully!'));
    // }


    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $shop = generaleSetting('shop');
        ProductRepository::storeByProductRequest($request, $data);
        $user = auth()->user();
        $isRootUser = $user?->hasRole('root');

        // admin notification message
        if (! $isRootUser && generaleSetting('setting')->shop_type != 'single') {
            $message = 'New product Created Request';
            try {
                AdminProductRequestEvent::dispatch($message);
            } catch (\Throwable $th) {
            }

            $data = (object) [
                'title' => $message,
                'content' => 'New product Created Request from ' . $shop->name,
                'url' => '/admin/products?status=0',
                'icon' => 'bi-shop',
                'type' => 'success',
            ];
            // store notification
            NotificationRepository::storeByRequest($data);
        }
        return to_route('shop.product.index')->withSuccess(__('Product created successfully!'));
    }

    /**
     * Display the product edit form.
     */
    public function edit(Product $product)
    {
        $rootShop = $product->shop;

        // get brands, colors, units, sizes and categories
        $brands = $rootShop?->brands()->isActive()->get();
        $colors = $rootShop?->colors()->isActive()->get();
        $businessCategoryIds = $rootShop?->businessCategories()->pluck('business_categories.id') ?? collect();
        $categories = Category::whereIn('business_category_id', $businessCategoryIds)->get();
        $units = $rootShop?->units()->isActive()->get();
        $sizes = $rootShop?->sizes()->isActive()->get();

        $categoryId = $product->categories()?->latest('id')->first()?->id;

        $subCategories = SubCategory::whereHas('categories', function ($query) use ($categoryId) {
            return $query->where('category_id', $categoryId);
        })->isActive()->get();

        $metaKeywords = explode(',', $product->meta_keywords) ?: [];
        $product->load([
            'variants.color',
            'variants.size',
        ]);
        return view('shop.product.edit', compact('product', 'brands', 'colors', 'categories', 'units', 'sizes', 'subCategories', 'metaKeywords',));
    }

    /**
     * Update the product.
     */
    // public function update(ProductRequest $request, Product $product)
    // {
    //     $shop = generaleSetting('shop');

    //     $skuCode = $shop?->products()->where('code', $request->code)->where('id', '!=', $product->id)->exists();

    //     if ($skuCode) {
    //         return back()->withInput()->withErrors(['code' => __('Product code already exists!')])->with('error', __('Product code already exists!'));
    //     }

    //     ProductRepository::updateByRequest($request, $product);

    //     /** @var User $user */
    //     $user = auth()->user();
    //     $isRootUser = $user?->hasRole('root');

    //     // admin notification message
    //     if (! $isRootUser && generaleSetting('setting')->shop_type != 'single') {
    //         $message = 'Product Updated Request';
    //         try {
    //             AdminProductRequestEvent::dispatch($message);
    //         } catch (\Throwable $th) {
    //         }

    //         $data = (object) [
    //             'title' => $message,
    //             'content' => 'Product Updated Request from ' . $shop->name,
    //             'url' => '/admin/products?status=1',
    //             'icon' => 'bi-shop',
    //             'type' => 'success',
    //         ];
    //         // store notification
    //         NotificationRepository::storeByRequest($data);
    //     }

    //     return to_route('shop.product.index')->withSuccess(__('Product updated successfully!'));
    // }


    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {

            ProductRepository::updateByProductRequest(
                $product,
                $request,
                $data
            );

            DB::commit();

            return to_route('shop.product.edit', $product->id)
                ->withSuccess(__('Product updated successfully!'));
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * delete thumbnail
     */
    public function thumbnailDestroy(Product $product, Media $media)
    {
        $product->medias()->detach($media->id);
        if (Storage::exists($media->src)) {
            Storage::delete($media->src);
        }

        $media->delete();

        return back()->withSuccess(__('Thumbnail deleted successfully!'));
    }

    /**
     * status toggle a product
     */
    public function statusToggle(Product $product)
    {
        // Admin-disabled protection
        if ($product->disabled_by_admin) {
            return back()->withError(__('This product was disabled by admin. Please contact admin.'));
        }

        if (! $product->is_approve) {
            return back()->withError(__('Sorry! Your Product is not approved yet!'));
        }

        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }

    // public function statusToggle(Product $product)
    // {
    //     if (! $product->is_approve) {
    //         return back()->withError(__('Sorry! Your Product is not approved yet!'));
    //     }

    //     $product->update([
    //         'is_active' => ! $product->is_active,
    //     ]);

    //     return back()->withSuccess(__('Status updated successfully'));
    // }

    /**
     * generate barcode
     */
    public function generateBarcode(Product $product)
    {
        if (! $product->code) {
            return back()->withError(__('Sorry! Your Product code is not generated yet!'));
        }

        $quantities = request('qty', 4);

        return view('shop.product.barcode', compact('product', 'quantities'));
    }

    /**
     * generate ai data
     */
    public function generateAIData(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'short_description' => 'nullable|string',
            ]);

            $chat = new Chat();
            $chat->systemMessage($request->name);

            $question = str_replace(
                ['{product_name}', '{short_description}'],
                [$request->name, $request->short_description],
                generaleSetting()->product_description
            );

            $question .= " Write a concise e-commerce style description like Flipkart/Amazon: highlight key benefits and important specs in customer-friendly language. Keep the final description within 500 characters maximum. Avoid filler words and introductions. Return only the final content.";

            $response = $chat->send($question);

            $response = $this->formatAiProductDescription($response, 500);

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function formatAiProductDescription(?string $content, int $maxLength = 500): string
    {
        $text = trim((string) $content);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > $maxLength) {
            $short = mb_substr($text, 0, $maxLength - 3);
            $short = preg_replace('/\s+\S*$/u', '', $short) ?: $short;
            $text = rtrim($short) . '...';
        }

        return '<p>' . e($text) . '</p>';
    }
}
