<?php

namespace App\Http\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paymentMethod = $this->payment_method->value;
        $latestPayment = $this->payments?->sortByDesc('id')->first();
        $gatewayPaymentStatus = null;

        if ($latestPayment) {
            if (! empty($latestPayment->razorpay_refund_id)) {
                $gatewayPaymentStatus = 'Refunded';
            } elseif ($latestPayment->is_paid) {
                $gatewayPaymentStatus = 'Paid';
            } elseif (! empty($latestPayment->razorpay_payment_id)) {
                $gatewayPaymentStatus = 'Authorized';
            } else {
                $gatewayPaymentStatus = 'Pending';
            }
        }

        if ($this->payment_status->value == PaymentStatus::PENDING->value && $paymentMethod != PaymentMethod::CASH->value) {
            $paymentMethod = PaymentMethod::ONLINE->value;
        }

        $estimateDelivery = $this->shop?->estimated_delivery_time ?? '2-3 days';

        $is_returned = false;

        if ($this->created_at && $this->order_status->value === 'Delivered') {
            $is_returned = $this->created_at->copy()
                ->addDays($generaleSetting?->return_order_within_days ?? 3)
                ->isFuture();
        }

        return [
            'id' => $this->id,
            'order_code' => (string) '#' . $this->order_code,
            'api_provider' => $this->api_provider,
            'order_status' => $this->order_status->value,
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at,
            'placed_at' => $this->created_at->format('d M, Y h:i A'),
            'estimated_delivery_date' => (string) $estimateDelivery,
            'payment_method' => $paymentMethod,
            'payment_status' => $this->payment_status->value,
            'gateway_payment_method' => $latestPayment?->payment_method,
            'gateway_payment_status' => $gatewayPaymentStatus,
            'razorpay_order_id' => $latestPayment?->razorpay_order_id,
            'razorpay_payment_id' => $latestPayment?->razorpay_payment_id,
            'razorpay_refund_id' => $latestPayment?->razorpay_refund_id,
            'total_amount' => (float) number_format($this->total_amount, 2, '.', ''),
            'tax_amount' => (float) number_format($this->tax_amount, 2, '.', ''),
            'discount' => (float) number_format($this->discount, 2, '.', ''),
            'coupon_discount' => (float) number_format($this->coupon_discount, 2, '.', ''),
            'payable_amount' => (float) number_format($this->payable_amount, 2, '.', ''),
            'quantity' => (int) $this->products->sum('pivot.quantity'),
            'delivery_mode' => $this->shop->deliverySetting?->delivery_mode ?? 'manual',
            'delivery_charge' => (float) number_format(($this->delivery_charge ?? 0), 2, '.', ''),
            'admin_whatsapp_order_enabled' => (bool) (generaleSetting()?->whatsapp_order_enabled ?? false),
            'shop' => ShopResource::make($this->shop),
            'products' => OrderProductResource::collection($this->products),
            'invoice_url' => route('shop.download-invoice', $this->id),
            'payment_receipt_url' => route('shop.payment-slip', $this->id),
            'address' => AddressResource::make($this->address),
            'all_vat_taxes' => $this->vatTaxes,
            'track_url' => $this->track_url,
            'gst' => $this->gst,
            'return_order_within_days' => $generaleSetting?->return_order_within_days ?? 3,
            'last_return_date' => $this->created_at
                ? $this->created_at->copy()->addDays($generaleSetting?->return_order_within_days ?? 3)->format('d M, Y h:i A')
                : null,
            'is_returnable' => $is_returned,
            'order_status_timelines' => $this->statusTimelines->map(function ($timeline) {
                return [
                    'status' => $timeline->status,
                    'changed_at' => $timeline->changed_at?->format('d M, Y h:i A'),
                    'changed_at_iso' => $timeline->changed_at?->toIso8601String(),
                    'changed_at_unix' => $timeline->changed_at?->getTimestamp(),
                ];
            }),
        ];
    }
}
