<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order->prefix . $this->order->order_code,
            'reason' => $this->reason,
            'amount' => (float)$this->amount,
            'delivery_charge' => (float)$this->order->delivery_charge,
            'status' => $this->status,
            'product' => ReturnOrderProductResource::collection($this->returnProduct),
            'quantity' => $this->returnProduct?->sum('quantity'),
            'payment_status' => $this->payment_status ? 'Paid' : 'Unpaid',
            'reject_note' => $this->reject_note,
            'return_date' => $this->created_at->format('d-m-Y h:i A'),
            'return_address' => $this->return_address,
            'images' => $this->returnProductImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'image_path' => $image->image_path,
                ];
            }),
        ];
    }
}
