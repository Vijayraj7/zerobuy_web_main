<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\AdvertisementResource;
use App\Http\Resources\SellerProductResource;
use App\Http\Resources\ShopResource;
use App\Models\AdvertisementSetting;
use App\Models\AdWallet;
use App\Models\Product;
use App\Models\Wallet;
use App\Repositories\AdvertisementRepository;
use App\Http\Resources\WalletResource;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    /**
     * Get all advertisements
     */
    public function index()
    {
        $shop = generaleSetting('shop');
        $advertisements = AdvertisementRepository::query()->where('shop_id', $shop->id)->get();

        $wallet = AdWallet::where('user_id', $shop->user_id)->first();

        $products = Product::where('shop_id', $shop->id)->get();

        $setting = AdvertisementSetting::first();

        return $this->json('all advertisements', [
            'daily_budget' => (float) $setting->daily_budget,
            'wallet' => WalletResource::make($wallet),
            'shop' => ShopResource::make($shop),
            'advertisements' => AdvertisementResource::collection($advertisements),
            'products' => SellerProductResource::collection($products),
        ]);
    }

    public function store(Request $request)
    {
        $advertisement = AdvertisementRepository::create([
            'shop_id' => $request->shop_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'link' => $request->link,
            'status' => $request->status,
        ]);

        return $this->json('advertisement created', [
            'advertisement' => new AdvertisementResource($advertisement),
        ]);
    }
}
