<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnOrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int)$this->product_id,
            'product_name' => $this->orderProduct->product_name ?? '',
            'product_price' => (float)$this->price ?? '',
            'thumbnail' => $this->product->thumbnail ?? '',
            'quantity' => (int)$this->quantity,
            'color' => $this->orderVariant?->color_name ?? $this->color,
            'size' => $this->orderVariant?->size_name ?? $this->size,
            'unit' => $this->unit,
            'price' => (float)$this->price,
        ];
    }
}
