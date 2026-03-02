<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load(['reviews', 'orders', 'sizes', 'colors', 'unit', 'brand', 'shop', 'flashSales', 'categories.businessCategory']);

        $lang = request()->header('accept-language') ?? 'en';

        $favorite = false;
        $user = Auth::guard('api')->user();

        if ($user && $user->customer) {
            $favorite = $user->customer->favorites()->where('product_id', $this->id)->exists();
        }

        $discountPercentage = $this->getDiscountPercentage($this->price, $this->discount_price);
        $totalSold = $this->orders->sum('pivot.quantity');

        $flashSale = $this->flashSales?->first();
        $flashSaleProduct = null;
        $quantity = null;

        if ($flashSale) {
            $flashSaleProduct = $flashSale?->products()->where('id', $this->id)->first();

            $quantity = $flashSaleProduct?->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

            if ($quantity == 0) {
                $quantity = null;
                $flashSaleProduct = null;
            } else {
                $discountPercentage = $flashSale?->pivot->discount;
            }
        }

        $price = $this->price;
        $discountPrice = $flashSaleProduct ? $flashSaleProduct->pivot->price : $this->discount_price;

        $translation = $this->translations()?->where('lang', $lang)->first();
        $name = $translation?->name ?? $this->name;

        $brandTranslation = $this->brand?->translations()?->where('lang', $lang)->first();
        $brandName = $brandTranslation?->name ?? $this->brand?->name;

        // Get business_category_id from the first category's businessCategory
        $businessCategoryId = null;
        if ($this->categories && $this->categories->count() > 0) {
            $businessCategoryId = $this->categories->first()->businessCategory?->id;
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'thumbnail' => $this->thumbnail,
            'price' => (float) number_format($price, 2, '.', ''),
            'discount_price' => (float) number_format($discountPrice, 2, '.', ''),
            'discount_percentage' => (float) number_format($discountPercentage, 2, '.', ''),
            'rating' => (float) $this->averageRating ?? 0.0,
            'weight' => (int) ($this->weight ?? 0),
            'bulk_items' => ProductBulkItemResource::collection(
                $this->bulkItems
            ),
            'bulk_prices' => ProductBulkPriceResource::collection(
                $this->bulkPrices
            ),
            'variants' => ProductVariantResource::collection($this->variants),
            'total_reviews' => (string) Number::abbreviate($this->reviews?->count(), maxPrecision: 2),
            'total_sold' => (string) number_format($totalSold, 0, '.', ','),
            'quantity' => (int) ($flashSaleProduct ? $quantity : $this->quantity),
            'min_order_quantity' => (int) $this->min_order_quantity,
            'is_favorite' => (bool) $favorite,
            'sizes' => SizeResource::collection($this->sizes),
            'colors' => ColorResource::collection($this->colors),
            'unit' => $this->unit ? UnitResource::make($this->unit) : null,
            'brand' => $brandName,
            'condition_status' => $this->condition_status,
            'is_ad' => $this->advertisements()->active()->exists(),
            'shop' => ProductShopResource::make($this->shop),
            'business_category_id' => $businessCategoryId,
        ];
    }
}
