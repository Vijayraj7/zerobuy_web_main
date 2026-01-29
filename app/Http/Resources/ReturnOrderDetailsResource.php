<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnOrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd($this->returnProduct);
        return [

            'id' => $this->id,
            'orderid' => $this->order_id,
            'order_id' => $this->order->prefix . $this->order->order_code,
            'reason' => $this->reason,
            'amount' => (float)$this->amount,
            'status' => $this->status,
            'payment_status' => $this->payment_status ? 'Paid' : 'Unpaid',
            'shop_name' => $this->shop->name ?? '',
            'shop_logo' => $this->shop->logo ?? '',
            'shop_rating' => (float) number_format($this->shop?->averageRating, 1, '.', ''),
            'shop_district' => $this->shop->districts->name ?? '',
            'shop_state' => $this->shop->states->name ?? '',
            'reject_note' => $this->reject_note,
            'return_date' => $this->created_at->format('d-m-Y h:i A'),
            'return_address' => $this->return_address,
            'customer_name' => $this->customer->user->name ?? '',
            'customer_phone' => $this->customer->user->phone ?? '',
            'customer_email' => $this->customer->user->email ?? '',
            'gst' => $this->order->gst ?? '',
            'address_name' => $this->order->address->name ?? '',
            'address_phone' => $this->order->address->phone ?? '',
            'address_line' => $this->order->address->address_line ?? '',
            'address_line2' => $this->order->address->address_line2 ?? '',
            'address_type' => $this->order->address->address_type ?? '',
            'address_district' => $this->order->address->districtData->name ?? '',
            'address_state' => $this->order->address->stateData->name ?? '',
            'address_postcode' => $this->order->address->post_code ?? '',
            'return_order_products' => ReturnOrderProductResource::collection($this->returnProduct),
            'images' => $this->returnProductImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'image_path' => $image->image_path,
                ];
            }),
            'return_product_images' => $this->returnProductImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'image_path' => $image->image_path,
                ];
            }),
        ];
    }
}
