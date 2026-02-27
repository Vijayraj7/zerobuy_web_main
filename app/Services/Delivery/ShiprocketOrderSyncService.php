<?php

namespace App\Services\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketOrderSyncService
{
    public function sync(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting', 'address.stateData', 'address.districtData', 'customer.user', 'products']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'shiprocket') {
            return false;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
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

        $orderAddress = $order->address;
        $customer = $order->customer?->user;
        $orderDate = optional($order->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');

        $items = $order->products->map(function ($product) {
            return [
                'name' => $product->pivot->product_name ?? $product->name,
                'sku' => (string) ($product->sku ?? ('SKU-' . $product->id)),
                'units' => (int) ($product->pivot->quantity ?? 1),
                'selling_price' => (float) ($product->pivot->price ?? 0),
                'discount' => 0,
                'tax' => 0,
                'hsn' => '',
            ];
        })->values()->toArray();

        if (empty($items)) {
            return false;
        }

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
            'payment_method' => $order->payment_method?->name === 'CASH' ? 'COD' : 'Prepaid',
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

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->withToken($token)
                ->post($baseUrl . '/orders/create/adhoc', $payload);

            if ($response->status() === 401) {
                Cache::forget($tokenCacheKey);
                return false;
            }

            if (!$response->successful()) {
                Log::warning('Shiprocket order create failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
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
                return false;
            }

            $order->update([
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
            return false;
        }
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
}
