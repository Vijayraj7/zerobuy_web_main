<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // � Attach fake pivot to color (for backward compatibility)
        if ($this->relationLoaded('color') && $this->color) {
            $this->color->setRelation('pivot', (object) [
                'price' => $this->price,
                'product_id' => $this->product_id,
            ]);
        }

        return [
            'id'         => $this->id,
            'product_id' => (int) $this->product_id,
            'price'      => (float) $this->price,
            'quantity'   => (int) $this->quantity,

            // ✅ SAME RESOURCE, SAME API, SINGLE OBJECT
            'color' => $this->whenLoaded(
                'color',
                fn() => new ColorResource($this->color)
            ),

            'size' => $this->whenLoaded(
                'size',
                fn() => new SizeResource($this->size)
            ),
        ];
    }
}
