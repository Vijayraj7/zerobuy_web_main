<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductStatus;
use App\Models\Shop;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Return status items (stories) for shops followed by the authenticated customer.
     */
    public function index(Request $request)
    {
        $customer = auth()->user()->customer;

        // Get IDs of followed shops
        $followedShopIds = $customer->followedShops()->pluck('shops.id');

        // Get active product statuses from followed shops
        $productStatuses = ProductStatus::with(['shop.mediaLogo', 'product.media'])
            ->whereIn('shop_id', $followedShopIds)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by shop
        $statusesByShop = $productStatuses->groupBy('shop_id');

        $statuses = $statusesByShop->map(function ($shopStatuses) {
            $shop = $shopStatuses->first()->shop;

            $statusItems = $shopStatuses->map(function ($productStatus) {
                // Get product image URL
                $imageUrl = null;
                if ($productStatus->product && $productStatus->product->media) {
                    $imageUrl = $productStatus->product->media->srcUrl ?? null;
                }

                // Fallback to a default image if no product image
                if (!$imageUrl) {
                    $imageUrl = asset('default/default.jpg');
                }

                return [
                    'image_url' => $imageUrl,
                    'message' => $productStatus->message,
                    'timestamp' => optional($productStatus->created_at)->toIso8601String(),
                    'product_id' => (int) $productStatus->product_id,
                    'status_id' => $productStatus->id,
                ];
            })->values();

            // Get shop logo
            $userImageUrl = $shop->logo;

            // Get the latest timestamp
            $latestTs = $statusItems->first()['timestamp'] ?? now()->toIso8601String();

            return [
                'store_name' => $shop->name,
                'user_image_url' => $userImageUrl,
                'timestamp' => $latestTs,
                'status_items' => $statusItems,
                'shop_id' => $shop->id,
            ];
        })->values();

        return $this->json('Statuses from followed shops', [
            'statuses' => $statuses,
        ]);
    }

    /**
     * Increment view count for a product status.
     */
    public function incrementView(Request $request, $statusId)
    {
        $productStatus = ProductStatus::findOrFail($statusId);
        $productStatus->increment('views');

        return $this->json('View count updated', [
            'views' => $productStatus->views,
        ]);
    }
}
