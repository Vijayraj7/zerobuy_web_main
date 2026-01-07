<?php

namespace App\Http\Resources;

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

        $review = $this->reviews()->where('customer_id', auth()->user()->customer?->id)->where('product_id', $this->id)->where('order_id', $request->order_id)->first();

        $price = $this->price > 0 ? $this->price : ($this->discount_price > 0 ? $this->discount_price : $this->price);

        $isReturned = ReturnOrderDetail::where('product_id', $this->id)
            ->whereHas('returnOrder', function ($q) use ($request) {
                $q->where('order_id', $request->order_id);
            })
            ->exists();
        $isReturnable = ! $isReturned;

        $orderCreatedAt = $this?->order?->created_at;
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


        $pname = $this->name;
        $dprice = 0;
        $mprice = (float) number_format((float) $this->price, 2, '.', '');
        $color = $this->color ?? null;
        $size = $this->size ?? null;
        if ($this->orderVariant) {
            $dprice =  (float) number_format($this->orderVariant->price, 2, '.', '');
            $color =  $this->orderVariant->color_name;
            $size =  $this->orderVariant->size_name;
        } else if ($this->orderBulkItem) {
            $pname = $this->orderBulkItem->name;
            $mprice = (float) number_format($this->orderBulkItem->mrp, 2, '.', '');
            $dprice =  (float) number_format($this->orderBulkItem->selling_price, 2, '.', '');
        } else {
            $dprice = (float) number_format($this->discount_price, 2, '.', '');
        }

        return [
            'id' => $this->id,
            'name' => $pname,
            'brand' => $this->brand?->name ?? null,
            'thumbnail' => $this->thumbnail,
            'price' => (float) $this->price,
            'd_price' => (float) ($dprice > 0 ? $dprice : $mprice),
            'discount_price' => (float) ($dprice > 0 ? $dprice : 0),
            'order_qty' => (int) $this->quantity,
            'color' => $color,
            'size' => $size,
            'rating' => $review ? (float) $review->rating : null,
            'unit' => $this->unit ?? null,
            'is_returned' => $isReturnable,
            'return_period' => $returnPeriod,
            'last_return_date' => $lastreturndate,
            'order_date' => $this->order?->created_at,
            'is_returnable' => $returnable && $isReturnable,
        ];
    }
}
