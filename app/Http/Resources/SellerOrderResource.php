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
        $latestPayment = $this->payments?->sortByDesc('id')->first();
        $gatewayPaymentStatus = null;

        if ($latestPayment) {
            if (! empty($latestPayment->razorpay_refund_id)) {
                $gatewayPaymentStatus = 'Refunded';
            } elseif ($latestPayment->is_paid) {
                $gatewayPaymentStatus = 'Paid';
            } elseif (! empty($latestPayment->razorpay_payment_id)) {
                $gatewayPaymentStatus = 'Authorized';
            } elseif (! empty($latestPayment->cashfree_order_id)) {
                $gatewayPaymentStatus = 'Initiated';
            } else {
                $gatewayPaymentStatus = 'Pending';
            }
        }

        $paymentMethodValue = strtolower(trim((string) ($this->payment_method?->value ?? $this->payment_method)));
        $isCashOrder = in_array($paymentMethodValue, ['cash', 'cash payment'], true);

        $retryShipProvider = strtolower(trim((string) (($this->shop?->deliverySetting?->delivery_api_enabled ? ($this->api_provider ?: $this->shop?->deliverySetting?->delivery_provider) : '') ?: '')));
        $hasProviderOrderId = ! empty($this->provider_order_id) || ! empty($this->shiprocket_order_id);
        $hasProviderShipmentId = ! empty($this->provider_shipment_id) || ! empty($this->shiprocket_shipment_id);
        $hasProviderAwb = ! empty($this->provider_awb_code) || ! empty($this->shiprocket_awb_code);

        $apiProviderStatus = null;
        $apiProviderStatusLabel = null;
        if (in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)) {
            if ($hasProviderAwb) {
                $apiProviderStatus = 'awb_generated';
                $apiProviderStatusLabel = 'AWB Generated';
            } elseif ($hasProviderShipmentId) {
                $apiProviderStatus = 'shipment_created';
                $apiProviderStatusLabel = 'Shipment Created';
            } elseif ($hasProviderOrderId) {
                $apiProviderStatus = 'order_created';
                $apiProviderStatusLabel = 'Order Created';
            } else {
                $apiProviderStatus = 'not_created';
                $apiProviderStatusLabel = 'Not Created';
            }
        }

        $isManualDelivery = ($this->shop?->deliverySetting?->delivery_mode ?? null) === 'manual';
        $isTrackingUpdateLocked = in_array($this->order_status?->value, [OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value], true);
        $isApiProviderOrder =
            in_array($retryShipProvider, ['shiprocket', 'delhivery'], true);
        $hasShipmentCreated = $hasProviderOrderId || $hasProviderShipmentId || $hasProviderAwb;
        $isDeliveryChargeLocked = ((float) $this->delivery_charge) > 0 || $hasShipmentCreated;

        $isProviderOrderCreated = false;
        if ($retryShipProvider === 'shiprocket') {
            $isProviderOrderCreated = ! empty($this->provider_order_id) || ! empty($this->shiprocket_order_id);
        } elseif ($retryShipProvider === 'delhivery') {
            $isProviderOrderCreated = ! empty($this->provider_order_id);
        }

        $showRetryShipButton =
            $this->order_status?->value === OrderStatus::CONFIRM->value
            && in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)
            && ! $isProviderOrderCreated;

        $showConfirmShipButton =
            $this->order_status?->value === OrderStatus::PENDING->value
            && $isApiProviderOrder;

        $showCreateShipmentButton =
            in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)
            && ! in_array($this->order_status?->value, [OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value], true)
            && ! in_array($apiProviderStatus, ['shipment_created', 'awb_generated'], true);

        $showReadyToPaymentButton =
            ($this->order_status?->value === OrderStatus::PENDING->value)
            && ! $isCashOrder;

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
            'gateway_payment_method' => $latestPayment?->payment_method,
            'gateway_payment_status' => $gatewayPaymentStatus,
            'razorpay_order_id' => $latestPayment?->razorpay_order_id,
            'razorpay_payment_id' => $latestPayment?->razorpay_payment_id,
            'razorpay_refund_id' => $latestPayment?->razorpay_refund_id,
            'cashfree_order_id' => $latestPayment?->cashfree_order_id,
            'cf_order_id' => $latestPayment?->cf_order_id,
            'payment_session_id' => $latestPayment?->payment_session_id,
            'retry_ship_provider' => in_array($retryShipProvider, ['shiprocket', 'delhivery'], true) ? $retryShipProvider : null,
            'api_provider_status' => $apiProviderStatus,
            'api_provider_status_label' => $apiProviderStatusLabel,
            'can_create_shipment' => (bool) $showCreateShipmentButton,
            'can_retry_ship' => (bool) $showRetryShipButton,
            'can_confirm_ship' => (bool) $showConfirmShipButton,
            'can_ready_to_payment' => (bool) $showReadyToPaymentButton,
            'is_tracking_update_locked' => (bool) $isTrackingUpdateLocked,
            'is_manual_delivery' => (bool) $isManualDelivery,
            'can_update_delivery_charge' => (bool) (! $isDeliveryChargeLocked && ($isManualDelivery || ((float) $this->delivery_charge) == 0)),
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
            'payment_receipt_url' => route('shop.payment-slip', $this->id),
            'status_timeline' => $timeline,
        ];
    }
}
