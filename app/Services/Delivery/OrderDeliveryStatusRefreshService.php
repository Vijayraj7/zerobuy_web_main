<?php

namespace App\Services\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderDeliveryStatusRefreshService
{
    public function __construct(
        protected ShiprocketOrderSyncService $shiprocketService,
        protected DelhiveryOrderSyncService $delhiveryService,
    ) {
    }

    /**
     * Refresh the delivery status from the provider API for the given order.
     * Fires whenever a provider shipment record exists for the order.
     * The API toggle state is intentionally ignored here - if a shipment was
     * already created with the provider we still need to track it.
     */
    public function refreshIfEligible(Order $order): void
    {
        try {
            $order->loadMissing(['shop.deliverySetting']);

            $setting = $order->shop?->deliverySetting;

            // Detect provider from the order's own shipment data first, then fall back
            // to the delivery setting (regardless of whether the toggle is ON or OFF).
            $provider = strtolower(trim((string) ($order->api_provider ?: '')));

            if (empty($provider)) {
                // Legacy / fallback: detect from shiprocket-specific columns
                if (!empty($order->shiprocket_order_id) || !empty($order->shiprocket_shipment_id)) {
                    $provider = 'shiprocket';
                } elseif ($setting && in_array(strtolower((string) ($setting->delivery_provider ?? '')), ['shiprocket', 'delhivery'], true)) {
                    $provider = strtolower((string) $setting->delivery_provider);
                }
            }

            if (!in_array($provider, ['shiprocket', 'delhivery'], true)) {
                return;
            }

            // Only refresh if a shipment has actually been created with the provider
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

            if ($provider === 'shiprocket') {
                if (empty($order->provider_awb_code) && empty($order->shiprocket_awb_code)) {
                    $this->shiprocketService->refreshAwbAndTrackUrl($order);
                    $order->refresh();
                }

                $this->shiprocketService->refreshCurrentStatus($order);
            } elseif ($provider === 'delhivery') {
                $this->delhiveryService->refreshCurrentStatus($order);
            }

            // Persist track_url from AWB if the DB value is still empty.
            // This works regardless of the API toggle state — no API call needed.
            $order->refresh();
            if (empty($order->track_url)) {
                if ($provider === 'shiprocket') {
                    $awb = $order->provider_awb_code ?: ($order->shiprocket_awb_code ?? null);
                    if (!empty($awb)) {
                        $order->update(['track_url' => 'https://shiprocket.co/tracking/' . $awb]);
                    }
                } elseif ($provider === 'delhivery') {
                    $awb = $order->provider_awb_code;
                    if (!empty($awb)) {
                        $order->update(['track_url' => 'https://www.delhivery.com/track/package/' . $awb]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OrderDeliveryStatusRefreshService exception', [
                'order_id' => $order->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
