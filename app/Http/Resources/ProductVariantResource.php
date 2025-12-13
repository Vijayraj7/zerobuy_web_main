<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class productVarientResource extends JsonResource
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
            'product_id' => (int) $this->product_id,
            'price' => (float) $this->price,
            'quantity' => (int) $this->quantity,
            'color' => ColorResource::collection($this->color),
            'size' => SizeResource::collection($this->size),
        ];
    }
}
