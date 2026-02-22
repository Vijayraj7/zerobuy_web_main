<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopDetailsResource;
use App\Http\Resources\ShopResource;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopFollower;
use App\Models\State;
use App\Repositories\ProductRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;

class ShopFollowerController extends Controller
{
    public function index(Request $request)
    {
        $customer = auth()->user()->customer;
        $perPage = $request->per_page ?? 20;
        $businessCategoryId = $request->integer('business_category_id');
        $applyBusinessCategoryFilter = ! is_null($businessCategoryId) && $request->type !== 'following';

        if ($request->type === 'all') {
            $shops = ShopRepository::query()
                ->isActive()
                ->when($applyBusinessCategoryFilter, function ($query) use ($businessCategoryId) {
                    return $query->whereHas('businessCategories', function ($categoryQuery) use ($businessCategoryId) {
                        $categoryQuery->where('business_categories.id', $businessCategoryId);
                    });
                })
                // ->where('is_branded', true)
                ->withCount('orders')
                ->withAvg('reviews as average_rating', 'rating')
                ->withExists([
                    'followers as is_followed' => fn($q) =>
                    $q->where('customer_id', $customer->id)
                ])
                ->paginate(20);
        } else if ($request->type === 'branded') {
            $shops = ShopRepository::query()
                ->isActive()
                ->where('is_branded', true)
                ->when($applyBusinessCategoryFilter, function ($query) use ($businessCategoryId) {
                    return $query->whereHas('businessCategories', function ($categoryQuery) use ($businessCategoryId) {
                        $categoryQuery->where('business_categories.id', $businessCategoryId);
                    });
                })
                ->withCount('orders')
                ->withAvg('reviews as average_rating', 'rating')
                ->withExists([
                    'followers as is_followed' => fn($q) =>
                    $q->where('customer_id', $customer->id)
                ])
                ->paginate(20);
        } else if ($request->type === 'verified') {
            $shops = ShopRepository::query()
                ->isActive()
                ->where('is_verified', true)
                ->when($applyBusinessCategoryFilter, function ($query) use ($businessCategoryId) {
                    return $query->whereHas('businessCategories', function ($categoryQuery) use ($businessCategoryId) {
                        $categoryQuery->where('business_categories.id', $businessCategoryId);
                    });
                })
                ->withCount('orders')
                ->withAvg('reviews as average_rating', 'rating')
                ->withExists([
                    'followers as is_followed' => fn($q) =>
                    $q->where('customer_id', $customer->id)
                ])
                ->paginate(20);
        } else if ($request->type === 'following') {
            $shops = $customer
                ->followedShops()
                ->isActive()
                ->with(['products', 'categories', 'reviews', 'banners'])
                ->withExists([
                    'followers as is_followed' => function ($q) use ($customer) {
                        $q->where('customer_id', $customer->id);
                    }
                ])
                ->latest()
                ->paginate($perPage);
        }

        return $this->json('Stores list', [
            'type' => $request->type,
            's' => Shop::all(),
            'stores' => ShopDetailsResource::collection($shops),
            'meta' => [
                'current_page' => $shops->currentPage(),
                'last_page' => $shops->lastPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
            ],
        ]);
    }

    public function followStore(Request $request)
    {
        $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
        ]);

        $customer = auth()->user()->customer;
        $shopId = $request->shop_id;

        $alreadyFollowed = ShopFollower::where([
            'customer_id' => $customer->id,
            'shop_id'     => $shopId,
        ])->first();

        // 🔁 Toggle logic
        if ($alreadyFollowed) {
            $alreadyFollowed->delete();

            return $this->json('Store unfollowed', [
                'followed' => false,
                'shop_id'  => $shopId,
            ]);
        }

        ShopFollower::create([
            'customer_id' => $customer->id,
            'shop_id'     => $shopId,
        ]);

        return $this->json('Store followed', [
            'followed' => true,
            'shop_id'  => $shopId,
        ]);
    }

    public function getStates(Request $request)
    {
        return $this->json('State List', [
            'states' => State::select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
