<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProductResource extends JsonResource
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
            'thumbnail' => $this->thumbnail,
            'price' => (float) $this->price,
            'discount_price' => (float) $this->discount_price,
            'weight' => (int) ($this->weight ?? 0),
            // 'quantity' => (int) $this->quantity,
            'bulk_items' => ProductBulkItemResource::collection(
                $this->bulkItems
            ),
            'bulk_prices' => ProductBulkPriceResource::collection(
                $this->bulkPrices
            ),
            'variants' => ProductVariantResource::collection($this->variants),
            'quantity' => $this->pivot ? (int) $this->pivot?->quantity : (int) $this->quantity,
            'min_order_quantity' => (int) $this->min_order_quantity,
            'brand' => $this->brand?->name ?? null,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
