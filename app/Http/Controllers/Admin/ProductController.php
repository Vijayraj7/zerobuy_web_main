<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminProductRequestEvent;
use App\Events\ProductApproveEvent;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\NotificationRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Repositories\ProductRepository;
use App\Models\ProductVariant;
use App\Models\ProductBulkPrice;
use App\Models\ProductItemDetail;
use App\Models\ProductVariantMedia;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */

    // public function index(Request $request)
    // {
    //     $status = $request->status;
    //     $shop = $request->shop;
    //     $approve = $request->approve;

    //     $products = Product::when($status == '1', function ($query) {
    //         return $query->where('is_approve', false)->where('is_new', false);
    //     })->when($status == '0', function ($query) {
    //         return $query->where('is_approve', false)->where('is_new', true);
    //     })->when($approve, function ($query) {
    //         return $query->where('is_approve', true)->where('is_active', true);
    //     })->when($shop, function ($query) use ($shop) {
    //         return $query->where('shop_id', $shop);
    //     })->paginate(50);

    //     $shops = ShopRepository::query()->isActive()->get();

    //     return view('admin.product.index', compact('products', 'shops'));
    // }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $status  = $request->status;
            $shop    = $request->shop;
            $approve = $request->approve;

            // $query = Product::with('shop')
            //     ->withCount([
            //         'variants',                                  
            //         'orderItems as total_sale_count'  
            //     ])
            //     ->when($status == '1', function ($q) {
            //         $q->where('is_approve', false)->where('is_new', false);
            //     })
            //     ->when($status == '0', function ($q) {
            //         $q->where('is_approve', false)->where('is_new', true);
            //     })
            //     ->when($approve, function ($q) {
            //         $q->where('is_approve', true)->where('is_active', true);
            //     })
            //     ->when($shop, function ($q) use ($shop) {
            //         $q->where('shop_id', $shop);
            //     });

            $query = Product::with('shop')
            ->withCount([
                'variants',
                'orderItems as total_sale_count'
            ])
            ->when($status == '1', function ($q) {
                $q->where('is_approve', false)
                ->where('is_new', false);
            })
            ->when($status == '0', function ($q) {
                $q->where('is_approve', false)
                ->where('is_new', true);
            })
            ->when($approve, function ($q) {
                $q->where('is_approve', true)
                ->where(function ($sub) {
                    $sub->where('is_active', true)
                        ->orWhere('disabled_by_admin', true);
                });
            })
            ->when($shop, function ($q) use ($shop) {
                $q->where('shop_id', $shop);
            });

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('created_date', fn($row) =>
                    Carbon::parse($row->created_at)->format('d-m-Y | h:i A')
                ) 
                ->addColumn('product_code', fn($row) => 'PRD0' . $row->id) 
                ->addColumn('store_code', fn($row) => 'STD0' . $row->shop_id)
                ->addColumn('shop', function ($row) {
                    return '<a href="'.route('admin.shop.show', $row->shop_id).'" class="text-decoration-none text-dark">'
                            .$row->shop->name.
                        '</a>';
                }) 
                ->addColumn('thumbnail', function ($row) {
                    return '<img src="'.$row->thumbnail.'" width="50">';
                })
                ->addColumn('quantity', fn($row) => $row->quantity ?? 0 ) 
                ->addColumn('mrp', fn($row) => showCurrency($row->price)) 
                ->addColumn('selling_price', fn($row) => showCurrency($row->discount_price))
                ->addColumn('total_sale_count', fn($row) => $row->total_sale_count ?? 0 )
                ->addColumn('variants_count', fn($row) => $row->variants_count ?? 0 )  
                // ->addColumn('status', function ($row) {
                //     return '
                //     <label class="switch">
                //         <input type="checkbox" class="toggle-status" data-id="'.$row->id.'" '.($row->is_active ? 'checked' : '').'>
                //         <span class="slider round"></span>
                //     </label>';
                // })
                ->addColumn('status', function ($row) {
                    $badge = '';

                    if ($row->disabled_by_admin) {
                        $badge = '<div class="mt-1">
                                    <span class="badge bg-danger">Disabled by Admin</span>
                                </div>';
                    }

                    return '
                        <label class="switch">
                            <input type="checkbox"
                                class="toggle-status"
                                data-id="'.$row->id.'"
                                '.($row->is_active ? 'checked' : '').'>
                            <span class="slider round"></span>
                        </label>
                        '.$badge;
                })

                ->addColumn('action', function ($row) {
                    if (!$row->is_approve) {
                        $approve = '<a href="'.route('admin.product.approve', $row->id).'" class="btn btn-success btn-sm confirmApprove"> Approved </a>';
                        $deny = '<button class="btn btn-danger btn-sm" onclick="confirmDeny('.$row->id.')"> Denied </button>';
                        return '<div class="d-flex gap-2 justify-content-center">' .$approve.$deny. '</div>';
                    }
                    return '<a href="'.route('admin.product.show', $row->id).'" class="circleIcon btn-outline-primary"> <img src="'.asset('assets/icons-admin/eye.svg').'"> </a>';
                })

                // ->addColumn('action', function ($row) {

                //     if (!$row->is_approve) {
                //         $approveBtn = auth()->user()->can('admin.product.approve')
                //             ? '<a href="'.route('admin.product.approve', $row->id).'"
                //                 class="btn btn-success btn-sm confirmApprove">Approved</a>'
                //             : '';

                //         $denyBtn = auth()->user()->can('admin.product.destroy')
                //             ? '<button class="btn btn-danger btn-sm"
                //                 onclick="confirmDeny('.$row->id.')">Denied</button>'
                //             : '';

                //         return '<div class="d-flex gap-2 justify-content-center">'.$approveBtn.$denyBtn.'</div>';
                //     }

                //     return auth()->user()->can('admin.product.show')
                //         ? '<a href="'.route('admin.product.show', $row->id).'"
                //             class="circleIcon btn-outline-primary">
                //             <img src="'.asset('assets/icons-admin/eye.svg').'">
                //         </a>'
                //         : '';
                // })

                ->rawColumns(['thumbnail', 'shop', 'status', 'action'])
                ->make(true);
        }

        $shops = ShopRepository::query()->isActive()->get();
        return view('admin.product.index', compact('shops'));
    }

    // public function show(Product $product)
    // {

    //     return view('admin.product.show', compact('product'));
    // }

    public function show(Product $product)
    {
        $product->load([
            'itemDetails',
            'variants.color',
            'variants.size',
            'bulkItems',
            'bulkPrices',
        ]);

        return view('admin.product.show', compact('product'));
    }

    /**
     * Approve the product.
     */
    public function approve(Product $product)
    {
        // update product
        $product->update([
            'is_approve' => true,
            'is_new' => false,
            'is_active' => true,
        ]);

        // admin notification message
        $message = 'Product Approved';
        try {
            ProductApproveEvent::dispatch($message, $product->shop_id);
        } catch (\Throwable $th) {
        }

        $data = (object) [
            'title' => $message,
            'content' => 'Your product approved from admin',
            'url' => '/shop/product/' . $product->id . '/show',
            'icon' => 'bi-bag-check-fill',
            'type' => 'success',
            'shop_id' => $product->shop_id,
        ];
        // store notification
        NotificationRepository::storeByRequest($data);

        return back()->withSuccess(__('Product approved successfully'));
    }

    public function destroy(Product $product)
    {
        $shopID = $product->shop_id;
        if ($product->media && Storage::exists($product->media->src)) {
            Storage::delete($product->media->src);
        }
        $product->media()->delete();
        $product->sizes()->delete();
        $product->colors()->delete();
        $product->reviews()->delete();
        $product->categories()->detach();

        foreach ($product->medias as $media) {
            if ($media && Storage::exists($media->src)) {
                Storage::delete($media->src);
            }
            $media->delete();
        }

        $product->delete();

        // admin notification message
        $message = 'Product Deleted';
        try {
            ProductApproveEvent::dispatch($message, $shopID);
        } catch (\Throwable $th) {
        }

        $data = (object) [
            'title' => $message,
            'content' => 'Your product deleted from admin',
            'url' => null,
            'icon' => 'bi-x-octagon-fill',
            'type' => 'danger',
            'shop_id' => $shopID,
        ];
        // store notification
        NotificationRepository::storeByRequest($data);

        return back()->withSuccess(__('Product deleted successfully'));
    }

    // created by Ancy
    public function create()
    {
        $shop = generaleSetting('shop');
        $rshop = generaleSetting('rootShop');

        $categories = Category::all();
        // $categories = $rshop?->categories()->active()->get();
        $colors = $shop?->colors()->isActive()->get();
        $sizes = $shop?->sizes()->isActive()->get();

        return view('shop.product.edit', compact('categories', 'colors', 'sizes'));
    }

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

            return to_route('shop.product.index')
                ->withSuccess(__('Product updated successfully!'));
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    // public function toggleStatus(Request $request)
    // {
    //     $product = Product::findOrFail($request->id);

    //     $product->update([
    //         'is_active' => !$product->is_active,
    //         'is_approve' => 0
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'status'  => $product->is_active
    //     ]);
    // }

    public function toggleStatus(Request $request)
    {
        $product = Product::findOrFail($request->id);

        // Toggle status
        $newStatus = ! $product->is_active;

        $product->update([
            'is_active' => $newStatus,
            'disabled_by_admin' => ! $newStatus ? true : false
        ]);

        return response()->json([
            'success' => true,
            'status'  => $product->is_active
        ]);
    }

}
