<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderBulkItem;
use App\Models\OrderVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // $this->load('brand', 'reviews');
        $pname  = $this->product->name;
        $dprice = 0;
        $mprice = (float) number_format((float) $this->price, 2, '.', '');

        $color =  null;
        $size  =  null;

        if ($this->orderVariant != null) {
            $dprice = (float) number_format($this->orderVariant->price, 2, '.', '');
            $color  = $this->orderVariant->color_name;
            $size   = $this->orderVariant->size_name;
        } elseif ($this->orderBulkItem != null) {
            $pname  = $this->orderBulkItem->name;
            $mprice = (float) number_format($this->orderBulkItem->mrp, 2, '.', '');
            $dprice = (float) number_format($this->orderBulkItem->selling_price, 2, '.', '');
        } else {
            $dprice = (float) number_format($this->discount_price, 2, '.', '');
        }

        return [
            'id' => $this->product->id,
            'name' => $pname,
            'thumbnail' => $this->product->thumbnail,
            'price' => (float) $mprice,
            'discount_price' => (float) $dprice,
            'variant_id' => $this->orderVariant?->id,
            'bulk_item_id' => $this->orderBulkItem?->id,
            // 'quantity' => (int) $this->quantity,
            // 'bulk_items' => ProductBulkItemResource::collection(
            //     $this->bulkItems
            // ),
            // 'bulk_prices' => ProductBulkPriceResource::collection(
            //     $this->bulkPrices
            // ),
            'color' => $color,
            'size' => $size,
            // 'variants' => ProductVariantResource::collection($this->variants),
            'quantity' =>  (int) $this->quantity,
            'min_order_quantity' => (int) $this->product->min_order_quantity,
            // 'brand' => $this->brand?->name ?? null,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
