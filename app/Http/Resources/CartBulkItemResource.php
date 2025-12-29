<?php

namespace App\Http\Resources;

use App\Models\ProductBulkItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartBulkItemResource extends JsonResource
{
    public function toArray($request)
    {
        $bulkitem = $this->bulkItem;
        return [
            'id'            => $this->id,
            'name'          => $bulkitem->name,
            'quantity'      => (int) $this->quantity,
            'moq'           => (int) $bulkitem->moq,
            'mrp'           => (float) $bulkitem->mrp,
            'selling_price' => (float) $bulkitem->selling_price,
        ];
    }
}
