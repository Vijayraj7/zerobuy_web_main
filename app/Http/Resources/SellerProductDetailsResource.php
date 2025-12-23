<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProductDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'discount_price' => (float) $this->discount_price,
            'details' =>  $this->details,
            'quantity' => (int) $this->quantity,
            'code' => (int) $this->code,
            'min_order_quantity' => (int) $this->min_order_quantity,
            'thumbnail' => $this->thumbnail,
            'gst' => $this->tax_percentage,
            'return_period' => $this->return_period,
            'condition_status' => $this->condition_status,
            'variants' => ProductVariantResource::collection($this->variants),
            // 'videourl' => $this->videourl(),
            'additional_thumbnail' => $this->additionalThumbnails(),
            'category' => OnlyCategoryResource::make($this->categories()?->first()),
            'sub_categories' => SubCategoryResource::collection($this->subCategories),
            'child_categories' => ChildCategoryResource::collection($this->childCategories),
            'sizes' => SizeResource::collection($this->sizes),
            'colors' => ColorResource::collection($this->colors),
            'brand' => BrandResource::make($this->brand),
            'unit' => UnitResource::make($this->unit),
            'bulk_items' => ProductBulkItemResource::collection(
                $this->bulkItems
            ),
            'bulk_prices' => ProductBulkPriceResource::collection(
                $this->bulkPrices
            ),
            'short_description' => $this->short_description,
            'description' => $this->description,
        ];
    }
}
