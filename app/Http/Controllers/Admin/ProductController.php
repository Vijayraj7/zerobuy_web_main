<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProductApproveEvent;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\NotificationRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


use App\Models\ProductVariant;
use App\Models\ProductBulkPrice;
use App\Models\ProductItemDetail;
use App\Models\ProductVariantMedia;
use App\Models\Media; 
use Illuminate\Support\Facades\DB; 

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $status = $request->status;
        $shop = $request->shop;
        $approve = $request->approve;

        $products = Product::when($status == '1', function ($query) {
            return $query->where('is_approve', false)->where('is_new', false);
        })->when($status == '0', function ($query) {
            return $query->where('is_approve', false)->where('is_new', true);
        })->when($approve, function ($query) {
            return $query->where('is_approve', true)->where('is_active', true);
        })->when($shop, function ($query) use ($shop) {
            return $query->where('shop_id', $shop);
        })->paginate(20);

        $shops = ShopRepository::query()->isActive()->get();

        return view('admin.product.index', compact('products', 'shops'));
    }

    public function show(Product $product)
    {

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
            'url' => '/shop/product/'.$product->id.'/show',
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

        $categories = $rshop?->categories()->active()->get();
        $colors = $shop?->colors()->isActive()->get();
        $sizes = $shop?->sizes()->isActive()->get();

        return view('admin.product.create', compact('categories','colors','sizes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'condition_status' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'main_category' => 'nullable|integer',
            'sub_categories' => 'nullable|array',
            'child_categories' => 'nullable|array',
            'quantity' => 'required|integer',
            'min_order_quantity' => 'required|integer|min:1',
            'return_period' => 'nullable|integer',
            'item_details' => 'nullable|array',
            'variants' => 'nullable|array',
            'mrp' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'tax_percentage' => 'nullable|numeric',
            'bulk' => 'nullable|array',
            'thumbnail' => 'nullable|file|image|max:5120',
            'additional_images.*' => 'nullable|image|max:5120',
            'video_type' => 'nullable|string',
            'video_link' => 'nullable|string',
        ]);

        $shop = generaleSetting('shop');
        $generaleSetting = generaleSetting('setting');
        $approve = $generaleSetting?->new_product_approval ? false : true;

        $user = auth()->user();
        $isAdmin = false;
        if ($user->hasRole('root') || ($generaleSetting?->shop_type == 'single')) {
            $isAdmin = true;
        }

        DB::beginTransaction();
        try {
            // 1. create product
            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'condition_status' => $data['condition_status'],
                'quantity' => $data['quantity'],
                'min_order_quantity' => $data['min_order_quantity'],
                'return_period' => $data['return_period'] ?? null, 
                'price' => $data['mrp'],
                'discount_price' => $data['selling_price'],
                'tax_percentage' => $data['tax_percentage'] ?? null,

                'shop_id' => $shop?->id,
                'is_active' => $isAdmin ? true : $approve,
                'is_new' => true,
                'is_approve' => $isAdmin ? true : $approve,
            ]);

            // 2. categories
            if (!empty($data['main_category'])) {
                DB::table('product_categories')->insert([
                    'product_id' => $product->id,
                    'category_id' => $data['main_category'],
                ]);
            }
            if (!empty($data['sub_categories'])) {
                $rows = [];
                foreach ($data['sub_categories'] as $sc) {
                    $rows[] = ['product_id' => $product->id, 'sub_category_id' => $sc];
                }
                if ($rows) DB::table('product_subcategories')->insert($rows);
            }
            if (!empty($data['child_categories'])) {
                $rows = [];
                foreach ($data['child_categories'] as $cc) {
                    $rows[] = ['product_id' => $product->id, 'child_category_id' => $cc];
                }
                if ($rows) DB::table('product_child_categories')->insert($rows);
            }

            // 3. item details
            if (!empty($data['item_details'])) {
                foreach ($data['item_details'] as $text) {
                    if (trim($text) === '') continue;
                    ProductItemDetail::create([
                        'product_id' => $product->id,
                        'item_text' => $text,
                    ]);
                }
            }

            // 4. Bulk prices
            if (!empty($data['bulk'])) {
                foreach ($data['bulk'] as $b) {
                    if (!isset($b['min_qty']) || !isset($b['max_qty']) || !isset($b['price'])) continue;
                    ProductBulkPrice::create([
                        'product_id' => $product->id,
                        'min_qty' => intval($b['min_qty']),
                        'max_qty' => intval($b['max_qty']),
                        'price' => floatval($b['price']),
                    ]);
                }
            }

            // 5. Variants
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $v) {
                    // Each v expected to contain keys size_id, color_id, price, quantity
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size_id' => $v['size_id'] ? intval($v['size_id']) : null,
                        'color_id' => $v['color_id'] ? intval($v['color_id']) : null,
                        'price' => isset($v['price']) ? floatval($v['price']) : 0,
                        'quantity' => isset($v['quantity']) ? intval($v['quantity']) : 0,
                    ]);
                }
            }

            // 6. Video: store in media table if link present and set products.video_id
            if (!empty($data['video_link']) && !empty($data['video_type'])) {
                $media = Media::create([
                    'type' => $data['video_type'],
                    'name' => basename($data['video_link']),
                    'original_name' => basename($data['video_link']),
                    'src' => $data['video_link'],
                    'extention' => null,
                ]);
                $product->video_id = $media->id;
                $product->save();
            }

            // 7. Thumbnail upload => store in media & products.media_id
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $path = $file->store('products/thumbnail','public'); // storage/app/public/products/thumbnail
                $media = Media::create([
                    'type' => 'image',
                    'name' => $file->hashName(),
                    'original_name' => $file->getClientOriginalName(),
                    'src' => $path,
                    'extention' => $file->getClientOriginalExtension(),
                ]);
                $product->media_id = $media->id;
                $product->save();
            }

            // 8. Additional images => save to media & product_thumbnails
            if ($request->hasFile('additional_images')) {
                $rows = [];
                foreach ($request->file('additional_images') as $file) {
                    if (!$file->isValid()) continue;
                    $path = $file->store('products/additional','public');
                    $m = Media::create([
                        'type' => 'image',
                        'name' => $file->hashName(),
                        'original_name' => $file->getClientOriginalName(),
                        'src' => $path,
                        'extention' => $file->getClientOriginalExtension(),
                    ]);
                    $rows[] = ['product_id' => $product->id, 'media_id' => $m->id];
                }
                if ($rows) DB::table('product_thumbnails')->insert($rows);
            }

            DB::commit();
            // return redirect()->route('product.index')->with('success','Product created successfully.');
            return to_route('shop.product.index')->withSuccess(__('Product created successfully!'));

        } catch (\Throwable $e) {
            DB::rollBack();
            // log the error for debugging
            \Log::error('Product store error: '.$e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Unable to save product: '.$e->getMessage()]);
        }
    }
}
