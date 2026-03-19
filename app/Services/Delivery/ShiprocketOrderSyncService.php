<?php

namespace App\Services\Delivery;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketOrderSyncService
{
    private ?string $lastSyncError = null;

    public function sync(Order $order): bool
    {
        $this->lastSyncError = null;
        $order->loadMissing(['shop.deliverySetting', 'address.stateData', 'address.districtData', 'customer.user', 'products', 'orderProducts.product', 'orderProducts.orderVariant', 'orderProducts.orderBulkItem']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            $this->lastSyncError = 'Invalid or missing Shiprocket provider setting.';
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            $this->lastSyncError = 'Shiprocket API credentials are missing.';
            return false;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($order->shop_id ?? 'na') . '.' . md5($apiUserEmail);

        $token = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
            $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                'email' => $apiUserEmail,
                'password' => $apiUserPassword,
            ]);

            if (!$authResponse->successful()) {
                return null;
            }

            return $authResponse->json('token');
        });

        if (!$token) {
            $this->lastSyncError = 'Unable to get Shiprocket access token.';
            return false;
        }

        $orderAddress = $order->address;
        $customer = $order->customer?->user;
        $orderDate = optional($order->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');

        $items = $order->orderProducts->map(function ($orderProduct) {
            $product = $orderProduct->product;
            $orderBulkItem = $orderProduct->orderBulkItem;
            $quantity = max(1, (int) ($orderProduct->quantity ?? 1));
            $name = (string) ($orderBulkItem?->name ?? $orderProduct->product_name ?? $product?->name ?? ('Item #' . $orderProduct->id));
            $sku = (string) ($product?->sku ?? ('ITEM-' . ($orderProduct->product_id ?? $orderProduct->id)));
            if ($orderProduct->id) {
                $sku .= '-' . $orderProduct->id;
            }

            return [
                'name' => $name,
                'sku' => $sku,
                'units' => $quantity,
                'selling_price' => (float) ($orderProduct->price ?? 0),
                'discount' => 0,
                'tax' => 0,
                'hsn' => '',
            ];
        })->values()->toArray();

        if (empty($items)) {
            $this->lastSyncError = 'Order items are empty.';
            return false;
        }

        $packageMetrics = $this->resolveOrderPackageMetrics($order);
        $weightKg = !empty($packageMetrics['weight_grams'])
            ? round(((float) $packageMetrics['weight_grams']) / 1000, 3)
            : null;

        $payload = [
            'order_id' => $order->prefix . $order->order_code,
            'order_date' => $orderDate,
            'pickup_location' => (string) (config('services.shiprocket.pickup_location') ?: 'Primary'),
            'channel_id' => '',
            'comment' => (string) ($order->instruction ?? ''),
            'billing_customer_name' => (string) ($orderAddress->name ?? 'Customer'),
            'billing_last_name' => '',
            'billing_address' => (string) ($orderAddress->address_line ?? ''),
            'billing_address_2' => (string) ($orderAddress->address_line2 ?? ''),
            'billing_city' => (string) ($orderAddress->districtData?->name ?? $orderAddress->district ?? ''),
            'billing_pincode' => (string) ($orderAddress->post_code ?? ''),
            'billing_state' => (string) ($orderAddress->stateData?->name ?? $orderAddress->state ?? ''),
            'billing_country' => 'India',
            'billing_email' => (string) ($customer->email ?? 'no-reply@example.com'),
            'billing_phone' => (string) ($orderAddress->phone ?? $customer->phone ?? '9999999999'),
            'shipping_is_billing' => true,
            'shipping_customer_name' => (string) ($orderAddress->name ?? 'Customer'),
            'shipping_last_name' => '',
            'shipping_address' => (string) ($orderAddress->address_line ?? ''),
            'shipping_address_2' => (string) ($orderAddress->address_line2 ?? ''),
            'shipping_city' => (string) ($orderAddress->districtData?->name ?? $orderAddress->district ?? ''),
            'shipping_pincode' => (string) ($orderAddress->post_code ?? ''),
            'shipping_country' => 'India',
            'shipping_state' => (string) ($orderAddress->stateData?->name ?? $orderAddress->state ?? ''),
            'shipping_email' => (string) ($customer->email ?? 'no-reply@example.com'),
            'shipping_phone' => (string) ($orderAddress->phone ?? $customer->phone ?? '9999999999'),
            'order_items' => $items,
            'payment_method' => $this->resolveShiprocketPaymentMethod($order),
            'shipping_charges' => (float) ($order->delivery_charge ?? 0),
            'giftwrap_charges' => 0,
            'transaction_charges' => 0,
            'total_discount' => (float) ($order->coupon_discount ?? 0),
            'sub_total' => (float) ($order->total_amount ?? 0),
            'length' => 10,
            'breadth' => 10,
            'height' => 10,
            'weight' => 0.5,
        ];

        if (!empty($packageMetrics['length_cm'])) {
            $payload['length'] = (float) $packageMetrics['length_cm'];
        }

        if (!empty($packageMetrics['width_cm'])) {
            $payload['breadth'] = (float) $packageMetrics['width_cm'];
        }

        if (!empty($packageMetrics['height_cm'])) {
            $payload['height'] = (float) $packageMetrics['height_cm'];
        }

        if (!empty($weightKg) && $weightKg > 0) {
            $payload['weight'] = $weightKg;
        }

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->withToken($token)
                ->post($baseUrl . '/orders/create/adhoc', $payload);

            $this->writeLatestOrderCreateResponse($response, [
                'order_id' => $order->id,
                'order_code' => $order->prefix . $order->order_code,
                'retry' => false,
            ]);

            if ($response->status() === 401) {
                Cache::forget($tokenCacheKey);
                $this->lastSyncError = 'Shiprocket token is unauthorized or expired.';
                return false;
            }

            if (!$response->successful()) {
                Log::warning('Shiprocket order create failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->lastSyncError = $this->extractShiprocketApiMessage($response) ?? ('Shiprocket order create failed with status ' . $response->status() . '.');
                return false;
            }

            $shiprocketOrderId = $response->json('order_id')
                ?? $response->json('data.order_id')
                ?? $response->json('data.order_ids.0')
                ?? $response->json('order_ids.0')
                ?? $response->json('data.order_details.order_id')
                ?? $response->json('data.order_details.channel_order_id');

            $shipmentId = $response->json('shipment_id')
                ?? $response->json('data.shipment_id')
                ?? $response->json('data.shipment_ids.0')
                ?? $response->json('shipment_ids.0')
                ?? $response->json('data.order_details.shipment_id')
                ?? $response->json('data.shipment_details.shipment_id');

            $awbCode = $response->json('awb_code')
                ?? $response->json('data.awb_code')
                ?? $response->json('data.order_details.awb_code')
                ?? $response->json('data.shipment_details.awb_code');

            $trackUrl = $response->json('track_url')
                ?? $response->json('data.track_url')
                ?? $response->json('tracking_data.track_url')
                ?? $response->json('data.shipment_details.track_url');

            if (!$shiprocketOrderId) {
                $suggestedPickup = $response->json('data.data.0.pickup_location');

                if ($suggestedPickup && $suggestedPickup !== ($payload['pickup_location'] ?? null)) {
                    $payload['pickup_location'] = (string) $suggestedPickup;

                    $retryResponse = Http::timeout(25)
                        ->acceptJson()
                        ->withToken($token)
                        ->post($baseUrl . '/orders/create/adhoc', $payload);

                    $this->writeLatestOrderCreateResponse($retryResponse, [
                        'order_id' => $order->id,
                        'order_code' => $order->prefix . $order->order_code,
                        'retry' => true,
                        'pickup_location' => $payload['pickup_location'] ?? null,
                    ]);

                    if ($retryResponse->successful()) {
                        $response = $retryResponse;

                        $shiprocketOrderId = $response->json('order_id')
                            ?? $response->json('data.order_id')
                            ?? $response->json('data.order_ids.0')
                            ?? $response->json('order_ids.0')
                            ?? $response->json('data.order_details.order_id')
                            ?? $response->json('data.order_details.channel_order_id');

                        $shipmentId = $response->json('shipment_id')
                            ?? $response->json('data.shipment_id')
                            ?? $response->json('data.shipment_ids.0')
                            ?? $response->json('shipment_ids.0')
                            ?? $response->json('data.order_details.shipment_id')
                            ?? $response->json('data.shipment_details.shipment_id');

                        $awbCode = $response->json('awb_code')
                            ?? $response->json('data.awb_code')
                            ?? $response->json('data.order_details.awb_code')
                            ?? $response->json('data.shipment_details.awb_code');

                        $trackUrl = $response->json('track_url')
                            ?? $response->json('data.track_url')
                            ?? $response->json('tracking_data.track_url')
                            ?? $response->json('data.shipment_details.track_url');
                    }
                }
            }

            if (!$shiprocketOrderId) {
                Log::warning('Shiprocket create response missing order id', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'pickup_location' => $payload['pickup_location'] ?? null,
                ]);
                $this->lastSyncError = $this->extractShiprocketApiMessage($response) ?? 'Shiprocket response missing order id.';
                return false;
            }

            $order->update([
                'api_provider' => 'shiprocket',
                'provider_order_id' => $shiprocketOrderId ? (string) $shiprocketOrderId : $order->provider_order_id,
                'provider_shipment_id' => $shipmentId ? (string) $shipmentId : $order->provider_shipment_id,
                'provider_awb_code' => $awbCode ? (string) $awbCode : $order->provider_awb_code,
                'shiprocket_order_id' => $shiprocketOrderId ? (string) $shiprocketOrderId : $order->shiprocket_order_id,
                'shiprocket_shipment_id' => $shipmentId ? (string) $shipmentId : $order->shiprocket_shipment_id,
                'shiprocket_awb_code' => $awbCode ? (string) $awbCode : $order->shiprocket_awb_code,
                'track_url' => $trackUrl ? (string) $trackUrl : $order->track_url,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Shiprocket order sync exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            $this->lastSyncError = $e->getMessage();
            return false;
        }
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    private function resolveShiprocketPaymentMethod(Order $order): string
    {
        return $order->payment_method === PaymentMethod::CASH ? 'COD' : 'Prepaid';
    }

    public function requestPickup(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            return false;
        }

        $shipmentId = (string) ($order->shiprocket_shipment_id ?? '');
        if ($shipmentId === '') {
            Log::warning('Shiprocket pickup skipped: missing shipment id', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($order->shop_id ?? 'na') . '.' . md5($apiUserEmail);

        $token = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
            $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                'email' => $apiUserEmail,
                'password' => $apiUserPassword,
            ]);

            if (!$authResponse->successful()) {
                return null;
            }

            return $authResponse->json('token');
        });

        if (!$token) {
            return false;
        }

        $callPickupApi = function (string $bearerToken) use ($baseUrl, $shipmentId) {
            return Http::timeout(25)
                ->acceptJson()
                ->withToken($bearerToken)
                ->post($baseUrl . '/courier/generate/pickup', [
                    'shipment_id' => [(int) $shipmentId],
                ]);
        };

        try {
            $response = $callPickupApi($token);

            if ($response->status() === 401) {
                Cache::forget($tokenCacheKey);

                $newToken = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
                    $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                        'email' => $apiUserEmail,
                        'password' => $apiUserPassword,
                    ]);

                    if (!$authResponse->successful()) {
                        return null;
                    }

                    return $authResponse->json('token');
                });

                if (!$newToken) {
                    return false;
                }

                $response = $callPickupApi($newToken);
            }

            if (!$response->successful()) {
                Log::warning('Shiprocket pickup request failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $awbCode = $response->json('awb_code')
                ?? $response->json('data.awb_code')
                ?? $response->json('response.data.awb_code')
                ?? $response->json('data.data.0.awb_code');

            if ($awbCode && empty($order->shiprocket_awb_code)) {
                $order->update([
                    'api_provider' => 'shiprocket',
                    'provider_awb_code' => (string) $awbCode,
                    'shiprocket_awb_code' => (string) $awbCode,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Shiprocket pickup request exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refreshTrackingUrl(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            return false;
        }

        $shipmentId = (string) ($order->shiprocket_shipment_id ?? '');
        if ($shipmentId === '') {
            return false;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($order->shop_id ?? 'na') . '.' . md5($apiUserEmail);

        $token = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
            $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                'email' => $apiUserEmail,
                'password' => $apiUserPassword,
            ]);

            if (!$authResponse->successful()) {
                return null;
            }

            return $authResponse->json('token');
        });

        if (!$token) {
            return false;
        }

        $callTrackingApi = function (string $bearerToken) use ($baseUrl, $shipmentId) {
            return Http::timeout(25)
                ->acceptJson()
                ->withToken($bearerToken)
                ->get($baseUrl . '/courier/track/shipment/' . $shipmentId);
        };

        try {
            $response = $callTrackingApi($token);

            if ($response->status() === 401) {
                Cache::forget($tokenCacheKey);

                $newToken = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
                    $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                        'email' => $apiUserEmail,
                        'password' => $apiUserPassword,
                    ]);

                    if (!$authResponse->successful()) {
                        return null;
                    }

                    return $authResponse->json('token');
                });

                if (!$newToken) {
                    return false;
                }

                $response = $callTrackingApi($newToken);
            }

            if (!$response->successful()) {
                return false;
            }

            $trackUrl = $response->json('tracking_data.track_url')
                ?? $response->json('data.tracking_data.track_url')
                ?? $response->json('tracking_data.shipment_track.0')
                ?? $response->json('data.tracking_data.shipment_track.0');

            if ($trackUrl) {
                $order->update([
                    'track_url' => (string) $trackUrl,
                ]);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('Shiprocket tracking fetch exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refreshAwbAndTrackUrl(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            return false;
        }

        $shipmentId = trim((string) ($order->shiprocket_shipment_id ?? ''));
        $shiprocketOrderId = trim((string) ($order->shiprocket_order_id ?? ''));

        if ($shipmentId === '' && $shiprocketOrderId === '') {
            return false;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($order->shop_id ?? 'na') . '.' . md5($apiUserEmail);

        $token = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
            $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                'email' => $apiUserEmail,
                'password' => $apiUserPassword,
            ]);

            if (!$authResponse->successful()) {
                return null;
            }

            return $authResponse->json('token');
        });

        if (!$token) {
            return false;
        }

        $awbCode = null;

        try {
            if ($shipmentId !== '') {
                $trackResponse = Http::timeout(25)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($baseUrl . '/courier/track/shipment/' . $shipmentId);

                if ($trackResponse->status() === 401) {
                    Cache::forget($tokenCacheKey);
                    return false;
                }

                if ($trackResponse->successful()) {
                    $awbCode = $trackResponse->json('awb_code')
                        ?? $trackResponse->json('data.awb_code')
                        ?? $trackResponse->json('tracking_data.awb_code')
                        ?? $trackResponse->json('data.tracking_data.awb_code')
                        ?? $trackResponse->json($shipmentId . '.tracking_data.awb_code')
                        ?? $trackResponse->json('data.' . $shipmentId . '.tracking_data.awb_code')
                        ?? $trackResponse->json('tracking_data.shipment_track.0.awb_code')
                        ?? $trackResponse->json('data.tracking_data.shipment_track.0.awb_code')
                        ?? $trackResponse->json($shipmentId . '.tracking_data.shipment_track.0.awb_code')
                        ?? $trackResponse->json('data.' . $shipmentId . '.tracking_data.shipment_track.0.awb_code');
                }
            }

            if (!$awbCode && $shiprocketOrderId !== '') {
                $orderResponse = Http::timeout(25)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($baseUrl . '/orders/show/' . $shiprocketOrderId);

                if ($orderResponse->status() === 401) {
                    Cache::forget($tokenCacheKey);
                    return false;
                }

                if ($orderResponse->successful()) {
                    $awbCode = $orderResponse->json('awb_code')
                        ?? $orderResponse->json('data.awb_code')
                        ?? $orderResponse->json('data.shipments.awb')
                        ?? $orderResponse->json('data.shipments.number')
                        ?? $orderResponse->json('data.awb_data.awb')
                        ?? $orderResponse->json('data.shipment_details.awb_code')
                        ?? $orderResponse->json('data.shipment.awb_code')
                        ?? $orderResponse->json('data.shipment_data.awb_code')
                        ?? $orderResponse->json('data.0.awb_code')
                        ?? $orderResponse->json('data.0.shipment_details.awb_code');
                }
            }

            $awbCode = $awbCode ? trim((string) $awbCode) : '';

            if ($awbCode === '') {
                return false;
            }

            $order->update([
                'api_provider' => 'shiprocket',
                'provider_awb_code' => $awbCode,
                'shiprocket_awb_code' => $awbCode,
                'track_url' => 'https://shiprocket.co/tracking/' . $awbCode,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Shiprocket AWB refresh exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refreshCurrentStatus(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            return false;
        }

        $shipmentId = trim((string) ($order->shiprocket_shipment_id ?? ''));
        $shiprocketOrderId = trim((string) ($order->shiprocket_order_id ?? ''));

        if ($shipmentId === '' && $shiprocketOrderId === '') {
            return false;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($order->shop_id ?? 'na') . '.' . md5($apiUserEmail);

        $token = Cache::remember($tokenCacheKey, now()->addMinutes(50), function () use ($baseUrl, $apiUserEmail, $apiUserPassword) {
            $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
                'email' => $apiUserEmail,
                'password' => $apiUserPassword,
            ]);

            if (!$authResponse->successful()) {
                return null;
            }

            return $authResponse->json('token');
        });

        if (!$token) {
            return false;
        }

        try {
            $providerStatus = null;

            if ($shiprocketOrderId !== '') {
                $orderResponse = Http::timeout(25)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($baseUrl . '/orders/show/' . $shiprocketOrderId);

                $this->writeLatestStatusRefreshResponse($orderResponse, [
                    'order_id' => $order->id,
                    'order_code' => $order->prefix . $order->order_code,
                    'source' => 'orders_show',
                    'shiprocket_order_id' => $shiprocketOrderId,
                ]);

                if ($orderResponse->status() === 401) {
                    Cache::forget($tokenCacheKey);
                    return false;
                }

                if ($orderResponse->successful()) {
                    $providerStatus = $orderResponse->json('data.current_status')
                        ?? $orderResponse->json('data.status')
                        ?? $orderResponse->json('current_status')
                        ?? $orderResponse->json('status')
                        ?? $orderResponse->json('data.shipments.status');
                }
            }

            if (!$providerStatus && $shipmentId !== '') {
                $trackResponse = Http::timeout(25)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($baseUrl . '/courier/track/shipment/' . $shipmentId);

                $this->writeLatestStatusRefreshResponse($trackResponse, [
                    'order_id' => $order->id,
                    'order_code' => $order->prefix . $order->order_code,
                    'source' => 'courier_track',
                    'shipment_id' => $shipmentId,
                ]);

                if ($trackResponse->status() === 401) {
                    Cache::forget($tokenCacheKey);
                    return false;
                }

                if ($trackResponse->successful()) {
                    $providerStatus = $trackResponse->json('current_status')
                        ?? $trackResponse->json('shipment_status')
                        ?? $trackResponse->json('tracking_data.current_status')
                        ?? $trackResponse->json('data.tracking_data.current_status')
                        ?? $trackResponse->json($shipmentId . '.tracking_data.shipment_track.0.activity')
                        ?? $trackResponse->json('data.' . $shipmentId . '.tracking_data.shipment_track.0.activity');
                }
            }

            $mappedStatus = $this->mapProviderStatusToOrderStatus($providerStatus);

            if (!$mappedStatus) {
                return false;
            }

            if (!in_array($mappedStatus->value, [
                OrderStatus::CANCELLED->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::DELIVERED->value,
            ], true)) {
                return false;
            }

            // Extract track URL from the API response if available
            $trackUrl = null;
            if ($shiprocketOrderId !== '' && isset($orderResponse)) {
                $trackUrl = $orderResponse->json('data.track_url')
                    ?? $orderResponse->json('data.shipment_details.track_url')
                    ?? $orderResponse->json('track_url');
            } elseif ($shipmentId !== '' && isset($trackResponse)) {
                $trackUrl = $trackResponse->json('track_url')
                    ?? $trackResponse->json('data.track_url')
                    ?? $trackResponse->json('tracking_data.track_url')
                    ?? $trackResponse->json('data.tracking_data.track_url');
            }

            $updateData = ['order_status' => $mappedStatus->value];
            if (!empty($trackUrl) && $trackUrl !== $order->track_url) {
                $updateData['track_url'] = (string) $trackUrl;
            }

            if ($order->order_status?->value !== $mappedStatus->value || $updateData['track_url'] !== $order->track_url) {
                $order->update($updateData);
            }

            OrderStatusTimeline::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'status' => $mappedStatus->value,
                ],
                [
                    'changed_at' => now(),
                ]
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Shiprocket status refresh exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function mapProviderStatusToOrderStatus(?string $status): ?OrderStatus
    {
        if (!$status) {
            return null;
        }

        $normalized = strtoupper(trim((string) $status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        if (str_contains($normalized, 'DELIVERED')) {
            return OrderStatus::DELIVERED;
        }

        if (str_contains($normalized, 'CANCEL') || str_contains($normalized, 'RTO') || str_contains($normalized, 'LOST') || str_contains($normalized, 'UNDELIVER') || str_contains($normalized, 'RETURN')) {
            return OrderStatus::CANCELLED;
        }

        if (str_contains($normalized, 'SHIPPED')
            || str_contains($normalized, 'IN_TRANSIT')
            || str_contains($normalized, 'OUT_FOR_DELIVERY')
            || str_contains($normalized, 'PICKED')
            || str_contains($normalized, 'PICKUP')
            || str_contains($normalized, 'MANIFEST')
            || str_contains($normalized, 'AWB')
            || str_contains($normalized, 'DISPATCH')) {
            return OrderStatus::SHIPPED;
        }

        return null;
    }

    private function extractShiprocketApiMessage($response): ?string
    {
        $message = $response->json('message')
            ?? $response->json('error')
            ?? $response->json('errors.0')
            ?? $response->json('data.message')
            ?? $response->json('data.errors.0');

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return null;
    }

    private function writeLatestStatusRefreshResponse($response, array $requestMeta = []): void
    {
        try {
            $filePath = public_path('shiprocket/latest_status_refresh_response.json');
            File::ensureDirectoryExists(dirname($filePath));

            $data = [
                'captured_at' => now()->toDateTimeString(),
                'request' => $requestMeta,
                'response' => [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->json() ?? $response->body(),
                ],
            ];

            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Failed to write latest shiprocket status refresh response json', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function writeLatestOrderCreateResponse($response, array $requestMeta = []): void
    {
        try {
            $filePath = public_path('shiprocket/latest_order_create_response.json');
            File::ensureDirectoryExists(dirname($filePath));

            $data = [
                'captured_at' => now()->toDateTimeString(),
                'request' => $requestMeta,
                'response' => [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->json() ?? $response->body(),
                ],
            ];

            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Failed to write latest shiprocket order create response json', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveOrderPackageMetrics(Order $order): array
    {
        $totalWeightGrams = 0.0;
        $lengthValues = [];
        $widthValues = [];
        $heightValues = [];

        foreach ($order->orderProducts as $orderProduct) {
            $product = $orderProduct->product;
            $orderVariant = $orderProduct->orderVariant;
            $orderBulkItem = $orderProduct->orderBulkItem;

            $quantity = max(1, (int) ($orderProduct->quantity ?? 1));

            $weight = is_numeric($orderProduct->weight ?? null)
                ? (float) $orderProduct->weight
                : (is_numeric($orderVariant?->weight ?? null)
                    ? (float) $orderVariant->weight
                    : (is_numeric($orderBulkItem?->weight ?? null)
                        ? (float) $orderBulkItem->weight
                        : (is_numeric($product?->weight ?? null) ? (float) $product->weight : null)));
            if ($weight !== null && $weight > 0) {
                $totalWeightGrams += ($weight * $quantity);
            }

            $length = is_numeric($orderProduct->length ?? null)
                ? (float) $orderProduct->length
                : (is_numeric($orderVariant?->length ?? null)
                    ? (float) $orderVariant->length
                    : (is_numeric($orderBulkItem?->length ?? null)
                        ? (float) $orderBulkItem->length
                        : (is_numeric($product?->length ?? null) ? (float) $product->length : null)));
            if ($length !== null && $length > 0) {
                $lengthValues[] = $length;
            }

            $width = is_numeric($orderProduct->width ?? null)
                ? (float) $orderProduct->width
                : (is_numeric($orderVariant?->width ?? null)
                    ? (float) $orderVariant->width
                    : (is_numeric($orderBulkItem?->width ?? null)
                        ? (float) $orderBulkItem->width
                        : (is_numeric($product?->width ?? null) ? (float) $product->width : null)));
            if ($width !== null && $width > 0) {
                $widthValues[] = $width;
            }

            $height = is_numeric($orderProduct->height ?? null)
                ? (float) $orderProduct->height
                : (is_numeric($orderVariant?->height ?? null)
                    ? (float) $orderVariant->height
                    : (is_numeric($orderBulkItem?->height ?? null)
                        ? (float) $orderBulkItem->height
                        : (is_numeric($product?->height ?? null) ? (float) $product->height : null)));
            if ($height !== null && $height > 0) {
                $heightValues[] = $height;
            }
        }

        return [
            'weight_grams' => $totalWeightGrams > 0 ? round($totalWeightGrams, 2) : null,
            'length_cm' => !empty($lengthValues) ? max($lengthValues) : null,
            'width_cm' => !empty($widthValues) ? max($widthValues) : null,
            'height_cm' => !empty($heightValues) ? max($heightValues) : null,
        ];
    }
}
