<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBulkPriceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'min_qty' => (int) $this->min_qty,
            'max_qty' => (int) $this->max_qty,
            'price'   => (float) $this->price,
        ];
    }
}
