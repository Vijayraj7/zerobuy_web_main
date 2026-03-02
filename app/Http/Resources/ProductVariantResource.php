<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'product_id' => $this->product_id == null ? null : (int) $this->product_id,
            'price' => (float) $this->price,
            'quantity' => (int) $this->quantity,
            'weight' => (int) ($this->weight ?? 0),
            'length' => $this->length == null ? null : (float) $this->length,
            'width' => $this->width == null ? null : (float) $this->width,
            'height' => $this->height == null ? null : (float) $this->height,
            // 'color' => $this->color,
            // 'size' => $this->size,
            'color' => ColorResource::make($this->color),
            'size' => SizeResource::make($this->size),
        ];
    }
}
