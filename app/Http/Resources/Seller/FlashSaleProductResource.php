<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleProductResource extends JsonResource
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
            'quantity' => (int) $this->quantity,
            'is_active' => (bool) $this->is_active,
            'flash_sale_price' => (float) ($this->pivot?->price ?? 0),
            'flash_sale_quantity' => (int) ($this->pivot?->quantity ?? 0),
        ];
    }
}
