<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->variant->id ?? null,
            'product_id' => (int) $this->cart->product_id,
            'price' => (float) $this->price,
            'quantity' => (int) $this->quantity,
            // 'color' => $this->color,
            // 'size' => $this->size,
            'color' => ColorResource::make($this->variant->color),
            'size' => SizeResource::make($this->variant->size),
        ];
    }
}
