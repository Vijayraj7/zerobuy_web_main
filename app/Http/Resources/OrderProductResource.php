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

        $price = $this->pivot->price > 0 ? $this->pivot->price : ($this->discount_price > 0 ? $this->discount_price : $this->price);

        $isReturned = ReturnOrderDetail::where('product_id', $this->id)
            ->whereHas('returnOrder', function ($q) use ($request) {
                $q->where('order_id', $request->order_id);
            })
            ->exists();
        $isReturnable = ! $isReturned;

        $orderCreatedAt = $this->additional['order_created_at'] ?? null;
        if ($this->return_period == null) {
            $returnable = false;
        } else {
            $returnPeriod = (int) ($this->return_period);

            $returnable = false;

            if ($orderCreatedAt) {
                $lastReturnDate = $orderCreatedAt->copy()->addDays($returnPeriod);
                $returnable = now()->lessThanOrEqualTo($lastReturnDate);
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand?->name ?? null,
            'thumbnail' => $this->thumbnail,
            'price' => (float) $this->price,
            'd_price' => (float) ($this->discount_price > 0 ? $this->discount_price : $this->price),
            'discount_price' => (float) ($this->discount_price > 0 ? $price : 0),
            'order_qty' => (int) $this->pivot->quantity,
            'color' => $this->pivot->color ?? null,
            'size' => $this->pivot->size ?? null,
            'rating' => $review ? (float) $review->rating : null,
            'unit' => $this->pivot->unit ?? null,
            'is_returned' => $isReturnable,
            'return_period' => $this->return_period,
            'order_date' => $this->additional['order_created_at'],
            'is_returnable' => $returnable,
        ];
    }
}
