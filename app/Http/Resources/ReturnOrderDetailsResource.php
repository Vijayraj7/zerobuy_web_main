<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Enums\ReturnOderStatus;

class ReturnOrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusTimelines = $this->statusTimelines?->sortBy('changed_at') ?? collect();
        $statusTimelinesByStatus = $statusTimelines->keyBy('status');

        $pendingChangedAt = $statusTimelinesByStatus->get(ReturnOderStatus::PENDING->value)?->changed_at
            ?? $this->created_at;

        $timeline = [
            [
                'status' => ReturnOderStatus::PENDING->value,
                'changed_at' => $pendingChangedAt ? Carbon::parse($pendingChangedAt)->format('d M, Y h:i A') : null,
            ],
        ];

        $orderedStatuses = [
            ReturnOderStatus::APPROVED->value,
            ReturnOderStatus::COMPLETED->value,
            ReturnOderStatus::REJECTED->value,
            ReturnOderStatus::CANCELLED->value,
        ];

        foreach ($orderedStatuses as $status) {
            $entry = $statusTimelinesByStatus->get($status);
            if (!$entry) {
                continue;
            }

            $timeline[] = [
                'status' => $status,
                'changed_at' => $entry->changed_at ? Carbon::parse($entry->changed_at)->format('d M, Y h:i A') : null,
            ];
        }

        // dd($this->returnProduct);
        return [

            'id' => $this->id,
            'return_code' => $this->return_code,
            'orderid' => $this->order_id,
            'order_id' => $this->order->prefix . $this->order->order_code,
            'reason' => $this->reason,
            'amount' => (float)$this->amount,
            'status' => $this->status,
            'payment_status' => $this->payment_status ? 'Paid' : 'Unpaid',
            'shop_id' => $this->shop->id ?? null,
            'shop_name' => $this->shop->name ?? '',
            'shop_logo' => $this->shop->logo ?? '',
            'shop_rating' => (float) number_format($this->shop?->averageRating, 1, '.', ''),
            'shop_district' => $this->shop->districts->name ?? '',
            'shop_state' => $this->shop->states->name ?? '',
            'reject_note' => $this->reject_note,
            'return_date' => $this->created_at->format('d-m-Y h:i A'),
            'return_address' => $this->return_address,
            'bank_name' => $this->bank_name,
            'bank_account_holder_name' => $this->bank_account_holder_name,
            'bank_account_number' => $this->bank_account_number,
            'ifsc' => $this->ifsc,
            'upi_id' => $this->upi_id,
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
            'status_timeline' => $timeline,
        ];
    }
}
