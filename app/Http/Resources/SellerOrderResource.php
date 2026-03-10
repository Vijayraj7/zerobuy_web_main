<?php

namespace App\Http\Resources;

use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
{
    private const LEGACY_CANCELLED_BY_CUSTOMER = 'Cancelled by Customer';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $estimateDays = $this->shop->estimated_delivery_time ?? '2-4 days';

        $statusTimelines = $this->statusTimelines
            ?->sortBy('changed_at')
            ?->keyBy('status') ?? collect();

        $orderedStatuses = [
            OrderStatus::PENDING->value,
            OrderStatus::READY_TO_PAYMENT->value,
            OrderStatus::PAYMENT_SUCCESSFUL->value,
            OrderStatus::CONFIRM->value,
            OrderStatus::SHIPPED->value,
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELLED->value,
            OrderStatus::CANCELLED_BY_CUSTOMER->value,
            self::LEGACY_CANCELLED_BY_CUSTOMER,
        ];

        $currentStatus = $this->order_status?->value ?? OrderStatus::PENDING->value;
        $maxIndex = array_search($currentStatus, $orderedStatuses, true);
        if ($maxIndex === false) {
            $maxIndex = 0;
        }

        if (
            $currentStatus === OrderStatus::CANCELLED->value
            || $currentStatus === OrderStatus::CANCELLED_BY_CUSTOMER->value
            || $currentStatus === self::LEGACY_CANCELLED_BY_CUSTOMER
        ) {
            $maxIndex = array_search(OrderStatus::CANCELLED->value, $orderedStatuses, true);
            if (
                $currentStatus === OrderStatus::CANCELLED_BY_CUSTOMER->value
                || $currentStatus === self::LEGACY_CANCELLED_BY_CUSTOMER
            ) {
                $maxIndex = array_search(OrderStatus::CANCELLED_BY_CUSTOMER->value, $orderedStatuses, true);
            }
        }

        $timeline = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $status = $orderedStatuses[$i];
            $changedAt = null;

            if ($status === OrderStatus::PENDING->value) {
                $changedAt = $this->created_at;
            } elseif ($statusTimelines->has($status)) {
                $changedAt = $statusTimelines->get($status)?->changed_at;
            }

            $timeline[] = [
                'status' => $status,
                'changed_at' => $changedAt ? Carbon::parse($changedAt)->format('d M, Y h:i A') : null,
            ];
        }

        return [
            'id' => $this->id,
            'order_code' => (string) '#' . $this->prefix . '' . $this->order_code,
            'api_provider' => $this->api_provider,
            'amount' => (float) number_format($this->payable_amount, 2, '.', ''),
            'order_status' => $this->order_status->value,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method->value == PaymentMethod::CASH->value ? 'Cash' : 'Online',
            'estimated_delivery_date' => (string) $estimateDays,
            'track_url' => $this->track_url,
            'gst' => $this->gst,
            'order_date' => $this->created_at ? Carbon::parse($this->created_at)->format('d M, Y') : null,
            'pickup_date' => $this->pickup_date
                ? Carbon::parse($this->pickup_date)->format('d M, Y h:i A')
                : ($this->created_at ? Carbon::parse($this->created_at)->format('d M, Y h:i A') : null),
            'delivery_date' => $this->delivery_date ? Carbon::parse($this->delivery_date)->format('d M, Y') : null,
            'order_placed' => Carbon::parse($this->created_at)->format('d M, Y'),
            'delivery_mode' => $this->shop->deliverySetting?->delivery_mode,
            'delivery_charge' => (float) number_format($this->delivery_charge, 2, '.', ''),
            'user' => [
                'name' => $this->customer->user->name,
                'phone' => $this->customer->user->phone,
                'profile_photo' => $this->customer->user->thumbnail,
                'address' => AddressResource::make($this->address),
            ],
            'products' => SellerOrderProductResource::collection($this->orderProducts),
            'rider' => $this->driverOrder ? OrderRiderResource::make($this->driverOrder) : null,
            'invoice_url' => route('shop.download-invoice', $this->id),
            'status_timeline' => $timeline,
        ];
    }
}
