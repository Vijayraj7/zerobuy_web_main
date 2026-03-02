<?php

namespace App\Http\Resources;

use App\Repositories\CartRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ProductDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load(['reviews', 'orders', 'colors', 'sizes', 'unit', 'brand', 'flashSales']);

        $lang = request()->header('accept-language') ?? 'en';

        $favorite = false;
        $user = Auth::guard('api')->user();

        if ($user && $user->customer) {
            $favorite = $user->customer->favorites()->where('product_id', $this->id)->exists();
            
            // Load shop with is_followed check for authenticated users
            $this->load([
                'shop' => function ($query) use ($user) {
                    $query->withExists([
                        'followers as is_followed' => fn($q) =>
                        $q->where('customer_id', $user->customer->id)
                    ]);
                }
            ]);
        } else {
            // Load shop without is_followed for guests
            $this->load('shop');
        }

        $discountPercentage = $this->getDiscountPercentage($this->price, $this->discount_price);
        $totalSold = $this->orders->sum('pivot.quantity');

        $flashSale = $this->flashSales?->first();
        $flashSaleProduct = null;
        $quantity = null;

        if ($flashSale) {

            $flashSaleProduct = $flashSale->products()->where('id', $this->id)->first();

            $quantity = $flashSaleProduct->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

            if ($quantity == 0) {
                $quantity = null;
                $flashSaleProduct = null;
            } else {
                $discountPercentage = $flashSale->pivot->discount;
            }
        }

        $price = $this->price;
        $discountPrice = $flashSaleProduct ? $flashSaleProduct->pivot?->price : $this->discount_price;

        $translation = $this->translations()?->where('lang', $lang)->first();
        $name = $translation?->name ?? $this->name;
        $description = $translation?->description ?? $this->description;
        $shortDescription = $translation?->short_description ?? $this->short_description;

        $brandTranslation = $this->brand?->translations()?->where('lang', $lang)->first();
        $brandName = $brandTranslation?->name ?? $this->brand?->name;
        $shop = $this->shop;

        $lastOnline = (bool) ($shop?->isOnline() ?? false);

        $carts = null;
        if ($user && $user->customer) {
            $user_carts = $user->customer->carts()->where('product_id', $this->id)->get();
            $groupCart = $user_carts->groupBy('shop_id');
            $carts = CartRepository::ShopWiseCartProducts($groupCart);
        }



        return [
            'id' => $this->id,
            'name' => $name,
            'short_description' => $shortDescription,
            'price' => (float) number_format($price, 2, '.', ''),
            'discount_price' => (float) number_format($discountPrice, 2, '.', ''),
            'details' => $this->itemDetails,
            'discount_percentage' => (float) number_format($discountPercentage, 2, '.', ''),
            'rating' => (float) $this->averageRating ?? 0.0,
            'total_reviews' => (string) Number::abbreviate($this->reviews?->count(), maxPrecision: 2),
            'total_sold' => (string) number_format($totalSold, 0, '.', ','),
            'weight' => (int) ($this->weight ?? 0),
            'quantity' => (int) ($quantity ?? $this->quantity),
            'min_order_quantity' => (int) ($this->min_order_quantity ?? 1),
            'condition_status' => $this->condition_status,
            'is_favorite' => (bool) $favorite,
            'thumbnails' => $this->thumbnails(),
            'videourl' => $this->videourl(),
            'cart_items' => $carts ? $carts['shop_wise_products'] : [],
            'sizes' => SizeResource::collection($this->sizes),
            'colors' => ColorResource::collection($this->colors),
            'bulk_items' => ProductBulkItemResource::collection(
                $this->bulkItems
            ),
            'bulk_prices' => ProductBulkPriceResource::collection(
                $this->bulkPrices
            ),
            'variants' => ProductVariantResource::collection($this->variants),
            'brand' => $brandName,
            'gst' => $this->tax_percentage,
            'return_period' => (int) $this->return_period,
            'unit' => $this->unit ? UnitResource::make($this->unit) : null,
            'description' => $description,
            'shop' => ProductShopResource::make($this->shop),
            'flash_sale' => $flashSaleProduct ? FlashSaleResource::make($flashSale) : null,
            'meta_title' => $this->meta_title ?? $name,
            'meta_description' => $this->meta_description ?? $name,
            'meta_keywords' => $this->meta_keywords,
        ];
    }
}
