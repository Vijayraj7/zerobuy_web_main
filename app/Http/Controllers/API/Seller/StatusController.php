<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStatus;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Return status items for the authenticated shop.
     */
    public function index(Request $request)
    {
        $shop = generaleSetting('shop');

        $statuses = ProductStatus::with(['product.media'])
            ->where('shop_id', $shop->id)
            ->active()
            ->latest()
            ->get();

        $data = $statuses->map(function ($status) {
            $imageUrl = null;

            if ($status->product && $status->product->media) {
                $imageUrl = $status->product->media->srcUrl ?? null;
            }

            if (! $imageUrl) {
                $imageUrl = asset('default/default.jpg');
            }

            return [
                'id' => $status->id,
                'product_id' => (int) $status->product_id,
                'product_name' => $status->product?->name ?? '',
                'message' => $status->message,
                'image_url' => $imageUrl,
                'views' => (int) $status->views,
                'is_active' => (bool) $status->is_active,
                'started_at' => optional($status->started_at ?? $status->created_at)->toIso8601String(),
                'expired_at' => optional($status->expired_at)->toIso8601String(),
            ];
        })->values();

        return $this->json('Shop statuses', [
            'statuses' => $data,
        ]);
    }

    /**
     * Store a new status for the authenticated shop.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message' => 'nullable|string',
        ]);

        $shop = generaleSetting('shop');

        $product = Product::where('id', $request->product_id)
            ->where('shop_id', $shop->id)
            ->first();

        if (! $product) {
            return $this->json('Product not found for this shop', [], 404);
        }

        $exists = ProductStatus::where('product_id', $request->product_id)
            ->where('shop_id', $shop->id)
            ->active()
            ->exists();

        if ($exists) {
            return $this->json('Status already active. Wait until it expires.', [], 409);
        }

        $status = ProductStatus::create([
            'shop_id' => $shop->id,
            'product_id' => $request->product_id,
            'message' => $request->message,
            'is_active' => 1,
            'started_at' => now(),
            'expired_at' => now()->addHours(24),
        ]);

        return $this->json('Status activated for 24 hours', [
            'status_id' => $status->id,
        ]);
    }
}