<?php

namespace App\Http\Resources;

use App\Models\OrderBulkItem;
use App\Models\OrderVariant;
use Illuminate\Http\Request;
use App\Models\ReturnOrderDetail;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load('brand', 'reviews');
        // $this->pivot->loadMissing(['orderVariant', 'orderBulkItem']);

        $review = $this->reviews()->where('customer_id', auth()->user()->customer?->id)->where('product_id', $this->id)->where('order_id', $request->order_id)->first();

        $price = $this->pivot->price > 0 ? $this->pivot->price : ($this->discount_price > 0 ? $this->discount_price : $this->price);

        $isReturned = ReturnOrderDetail::where('product_id', $this->id)
            ->whereHas('returnOrder', function ($q) use ($request) {
                $q->where('order_id', $request->order_id);
            })
            ->exists();
        $isReturnable = ! $isReturned;

        $orderCreatedAt = $this->pivot?->order?->created_at;
        $returnable = false;

        $returnPeriod = !empty($this->return_period)
            ? (int) $this->return_period
            : null;

        $lastreturndate = $orderCreatedAt
            ->copy()
            ->addDays((int) ($this->return_period ?? 0));

        if ($orderCreatedAt && $returnPeriod != null) {
            if ($returnPeriod != 0) {
                $lastReturnDate = $orderCreatedAt
                    ->copy()
                    ->addDays((int) $this->return_period);

                $returnable = now()->lessThanOrEqualTo($lastReturnDate);
            }
        }


        // $pivot = $this;

        $pname  = $this->name;
        $dprice = 0;
        $mprice = (float) number_format((float) $this->price, 2, '.', '');

        $color = $pivot->color ?? null;
        $size  = $pivot->size ?? null;

        if ($this->order_variants_id) {
            $variant = OrderVariant::where('id', $this->order_variants_id)->first();

            if ($variant) {
                $dprice = (float) number_format($variant->price, 2, '.', '');
                $color  = $variant->color_name;
                $size   = $variant->size_name;
            }
        } elseif ($this->order_bulk_items_id) {
            $bulk = OrderBulkItem::where('id', $this->order_bulk_items_id)->first();

            if ($bulk) {
                $pname  = $bulk->name;
                $mprice = (float) number_format($bulk->mrp, 2, '.', '');
                $dprice = (float) number_format($bulk->selling_price, 2, '.', '');
            }
        } else {
            $dprice = (float) number_format($this->discount_price, 2, '.', '');
        }


        return [
            'id' => $this->id,
            'v_id' => $this->order_variants_id,
            'b_id' => $this->order_bulk_items_id,
            'vv_id' => $this->pivot->order_variants_id,
            'bb_id' => $this->pivot->order_bulk_items_id,
            'name' => $pname,
            'brand' => $this->brand?->name ?? null,
            'thumbnail' => $this->thumbnail,
            'price' => (float) $this->price,
            'd_price' => (float) ($dprice > 0 ? $dprice : $mprice),
            'discount_price' => (float) ($dprice > 0 ? $dprice : 0),
            'order_qty' => (int) $this->pivot->quantity,
            'color' => $color,
            'size' => $size,
            'rating' => $review ? (float) $review->rating : null,
            'unit' => $this->pivot->unit ?? null,
            'is_returned' => $isReturnable,
            'return_period' => $returnPeriod,
            'last_return_date' => $lastreturndate,
            'order_date' => $this->pivot->order?->created_at,
            'is_returnable' => $returnable && $isReturnable,
        ];
    }
}
