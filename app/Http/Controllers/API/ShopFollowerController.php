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
use App\Repositories\ProductRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;

class ShopFollowerController extends Controller
{
    public function index(Request $request)
    {
        $orderStatus = $request->order_status;

        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $customer = auth()->user()->customer;

        $followings = $customer->followedShops();

        // Response
        return $this->json('orders', [
            'followings' => $followings,
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
}
