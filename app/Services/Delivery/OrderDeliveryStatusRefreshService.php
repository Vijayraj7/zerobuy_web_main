<?php

namespace App\Services\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderDeliveryStatusRefreshService
{
    // Minimum seconds between API status refreshes for the same order
    private const THROTTLE_SECONDS = 300; // 5 minutes

    public function __construct(
        protected ShiprocketOrderSyncService $shiprocketService,
        protected DelhiveryOrderSyncService $delhiveryService,
    ) {
    }

    /**
     * Refresh the delivery status from the provider API for the given order.
     * Only fires if the order is using a provider API, has already been created
     * with the provider, and was not recently refreshed (throttle).
     */
    public function refreshIfEligible(Order $order): void
    {
        try {
            $order->loadMissing(['shop.deliverySetting']);

            $setting = $order->shop?->deliverySetting;

            if (!$setting || $setting->delivery_mode !== 'provider_api') {
                return;
            }

            $provider = strtolower(trim((string) ($order->api_provider ?: $setting->delivery_provider ?: '')));

            if (!in_array($provider, ['shiprocket', 'delhivery'], true)) {
                return;
            }

            // Only refresh for orders that are in a shippable/trackable state
            $currentStatus = (string) ($order->order_status?->value ?? '');
            if (in_array($currentStatus, ['Pending', 'Cancelled', 'Delivered'], true)) {
                return;
            }

            // Ensure the shipment has actually been created with the provider
            if ($provider === 'shiprocket') {
                $hasProviderRecord = !empty($order->provider_order_id)
                    || !empty($order->shiprocket_order_id)
                    || !empty($order->provider_shipment_id)
                    || !empty($order->shiprocket_shipment_id);
            } else {
                $hasProviderRecord = !empty($order->provider_order_id)
                    || !empty($order->provider_shipment_id)
                    || !empty($order->provider_awb_code);
            }

            if (!$hasProviderRecord) {
                return;
            }

            // Throttle: skip if refreshed within the last THROTTLE_SECONDS
            $cacheKey = 'delivery_status_refresh.order.' . $order->id;
            if (Cache::has($cacheKey)) {
                return;
            }

            Cache::put($cacheKey, true, now()->addSeconds(self::THROTTLE_SECONDS));

            if ($provider === 'shiprocket') {
                $this->shiprocketService->refreshCurrentStatus($order);
            } elseif ($provider === 'delhivery') {
                $this->delhiveryService->refreshCurrentStatus($order);
            }
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryStatusRefreshService exception', [
                'order_id' => $order->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
