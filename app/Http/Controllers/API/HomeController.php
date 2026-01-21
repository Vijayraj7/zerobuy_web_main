<?php

namespace App\Http\Controllers\API;

use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BusinessCategoryResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FlashSaleResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopResource;
use App\Models\Ad;
use App\Models\Banner;
use App\Models\BusinessCategory;
use App\Models\GeneraleSetting;
use App\Models\User;
use App\Repositories\BannerRepository;
use App\Repositories\BusinessCategoryRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\FlashSaleRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ShopRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // /**
    //  * Index method for retrieving banners, categories, and popular products.
    //  *
    //  * @return Some_Return_Value
    //  */
    public function index(Request $request)
    {
        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 8;
        $skip = ($page * $perPage) - $perPage;

        $generaleSetting = generaleSetting('setting');
        $rootShop = generaleSetting('rootShop');
        $shop = null;
        if ($generaleSetting?->shop_type == 'single') {
            $shop = $rootShop;
        }

        // $banners = BannerRepository::query()->whereNull('shop_id')->active()->get();

        $categories = CategoryRepository::query()->active()
            ->whereHas('shops', function ($query) use ($rootShop) {
                return $query->where('shop_id', $rootShop->id);
            })->whereHas('products', function ($product) {
                return $product->where('is_active', true);
            })->withCount('products')->orderByDesc('products_count')
            ->take(10)->get();

        $businesscategories = BusinessCategoryRepository::query()->active()
            // ->whereHas('shops', function ($query) use ($rootShop) {
            //     return $query->where('shop_id', $rootShop->id);
            // })->whereHas('products', function ($product) {
            //     return $product->where('is_active', true);
            // })
            // ->withCount('products')->orderByDesc('products_count')
            ->take(10)->get();



        $ads = Ad::where('status', 1)->latest('id')->take(2)->get();

        // get incoming flash sale
        $incomingFlashSale = FlashSaleRepository::getIncoming();

        // get running flash sale
        $runningFlashSale = FlashSaleRepository::getRunning();

        $today = Carbon::today();

        $banners = Banner::active()->get();

        $businessCategoryId = $request->get('business_category_id');

        $businesscategorIds = [];
        // $mainCategoryIds = [];

        if ($businessCategoryId) {

            $banners = Banner::active()->where('business_category_id', $businessCategoryId)->get();
            $businesscategorIds = [$businessCategoryId];
            $businessCategory = BusinessCategory::find($businessCategoryId);

            // if ($businessCategory) {
            //     $mainCategoryIds = $businessCategory
            //         ->categories()
            //         ->pluck('id')
            //         ->toArray();
            // }
        }

        $adProducts = ProductRepository::query()
            ->isActive()
            ->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->id);
            })
            ->whereColumn('quantity', '>', 'min_order_quantity')
            ->when($businessCategoryId, function ($q) use ($businessCategoryId) {
                $q->whereHas('categories', function ($qc) use ($businessCategoryId) {
                    $qc->where('categories.business_category_id', $businessCategoryId);
                });
            })
            ->whereHas('advertisements', function ($query) use ($today) {
                $query->active()->whereNotNull('product_id');
            })
            ->withCount('orders as orders_count')
            ->withAvg('reviews as average_rating', 'rating')
            ->orderByDesc('average_rating')
            ->orderByDesc('orders_count')
            ->take(6)
            ->get();

        $adProductIds = $adProducts->pluck('id')->toArray();

        $popularProducts = ProductRepository::query()->isActive()
            ->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->id);
            })->withCount('orders as orders_count')
            ->whereColumn('quantity', '>=', 'min_order_quantity')
            ->whereNotIn('id', $adProductIds)
            ->when($businessCategoryId, function ($q) use ($businessCategoryId) {
                $q->whereHas('categories', function ($qc) use ($businessCategoryId) {
                    $qc->where('categories.business_category_id', $businessCategoryId);
                });
            })
            ->withAvg('reviews as average_rating', 'rating')
            ->orderByDesc('average_rating')
            ->orderByDesc('orders_count')
            ->take(10)->get();


        $popularProductIds = $popularProducts->pluck('id')->toArray();
        $excludedIds = array_merge($adProductIds, $popularProductIds);


        $justForYou = ProductRepository::query()->isActive()->latest('id')
            ->whereColumn('quantity', '>', 'min_order_quantity')
            ->whereNotIn('id', $excludedIds)
            ->when($businessCategoryId, function ($q) use ($businessCategoryId) {
                $q->whereHas('categories', function ($qc) use ($businessCategoryId) {
                    $qc->where('categories.business_category_id', $businessCategoryId);
                });
            })
            ->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->id);
            });
        $total = $justForYou->count();
        $justForYou = $justForYou->skip($skip)->take($perPage)->get();

        $shops = collect([]);

        if ($generaleSetting?->shop_type != 'single') {
            $shops = ShopRepository::query()->isActive()
                ->when(!empty($businesscategorIds), function ($q) use ($businesscategorIds) {
                    $q->whereHas('businessCategories', function ($qc) use ($businesscategorIds) {
                        $qc->whereIn('business_categories.id', $businesscategorIds);
                    });
                })
                ->whereHas('products', function ($query) {
                    return $query->isActive();
                })->withCount('orders')->withAvg('reviews as average_rating', 'rating')->orderByDesc('average_rating')->orderByDesc('orders_count')->take(8)->get();
        }

        // $popularProducts = ProductRepository::query()
        //     ->isActive()
        //     ->when($shop, function ($query) use ($shop) {
        //         return $query->where('shop_id', $shop->id);
        //     })
        //     ->whereColumn('quantity', '>', 'min_order_quantity')
        //     ->whereHas('ads', function ($query) {
        //         $query->where('slider_type', 'product');
        //     })
        //     ->withCount('orders as orders_count')
        //     ->withAvg('reviews as average_rating', 'rating')
        //     ->orderByDesc('average_rating')
        //     ->orderByDesc('orders_count')
        //     ->take(6)
        //     ->get();
        // $ads = Ad::query()->paginate(20);

        return $this->json('home', [
            'business_category_id' => $businessCategoryId,
            // 'main_category_ids' => $mainCategoryIds,
            'business_category_ids' => $businesscategorIds,
            'banners' => BannerResource::collection($banners),
            'ads' => BannerResource::collection($ads),
            'categories' => CategoryResource::collection($categories),
            'business_categories' => BusinessCategoryResource::collection($businesscategories),
            'shops' => ShopResource::collection($shops),
            'ad_products' => ProductResource::collection($adProducts),
            'popular_products' => ProductResource::collection($popularProducts),
            'just_for_you' => [
                'total' => $total,
                'products' => ProductResource::collection($justForYou),
            ],
            'ads' => $ads,
            'incoming_flash_sale' => $incomingFlashSale ? FlashSaleResource::make($incomingFlashSale) : null,
            'running_flash_sale' => $runningFlashSale ? FlashSaleResource::make($runningFlashSale)->toArray(request(), 'true', 'true') : null,
        ]);
    }

    /**
     * Get recently viewed products for the current user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentlyViews()
    {
        $generaleSetting = GeneraleSetting::first();

        $shop = null;
        if ($generaleSetting?->shop_type == 'single') {
            $shop = User::role(Roles::ROOT->value)->first()?->shop;
        }

        /**
         * @var \App\Models\User $user
         */
        $user = auth()->user();

        $products = $user->recentlyViewedProducts()->when($shop, function ($query) use ($shop) {
            return $query->where('shop_id', $shop->id);
        })->where('is_active', true)->orderBy('pivot_updated_at', 'desc')->take(10)->get();

        return $this->json('recently viewed products', [
            'products' => ProductResource::collection($products),
        ]);
    }
}
