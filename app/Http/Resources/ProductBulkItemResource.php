<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBulkItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'quantity'      => (int) $this->quantity,
            'moq'           => (int) $this->moq,
            'mrp'           => (float) $this->mrp,
            'selling_price' => (float) $this->selling_price,
            'weight'        => (int) ($this->weight ?? 0),
            'length'        => $this->length == null ? null : (float) $this->length,
            'width'         => $this->width == null ? null : (float) $this->width,
            'height'        => $this->height == null ? null : (float) $this->height,
        ];
    }
}
