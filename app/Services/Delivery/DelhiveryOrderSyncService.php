<?php

namespace App\Services\Delivery;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DelhiveryOrderSyncService
{
    private ?string $lastSyncError = null;

    public function sync(Order $order): bool
    {
        $this->lastSyncError = null;
        $order->loadMissing(['shop.deliverySetting', 'address.stateData', 'address.districtData', 'customer.user', 'products', 'orderProducts.product', 'orderProducts.orderVariant', 'orderProducts.orderBulkItem']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'delhivery') {
            $this->lastSyncError = 'Invalid or missing Delhivery provider setting.';
            $this->saveAttemptJson('latest_manifest_request_response.json', $order, [
                'outcome' => 'skipped',
                'reason' => 'invalid_or_missing_delhivery_provider_setting',
            ]);
            return false;
        }

        $apiToken = $this->normalizeApiToken((string) ($setting->provider_api_key ?? ''));
        if ($apiToken === '') {
            $this->lastSyncError = 'Delhivery API token is missing.';
            $this->saveAttemptJson('latest_manifest_request_response.json', $order, [
                'outcome' => 'skipped',
                'reason' => 'missing_api_token',
            ]);
            return false;
        }

        $delhiveryBaseUrl = rtrim((string) (data_get(config('services'), 'delhivery.base_url', 'https://track.delhivery.com') ?: 'https://track.delhivery.com'), '/');
        $manifestEndpoint = $delhiveryBaseUrl . '/api/cmu/create.json';

        $address = $order->address;
        $customer = $order->customer?->user;
        $shop = $order->shop;

        $customerName = trim((string) ($address?->name ?? $customer?->name ?? 'Customer'));
        $customerPhone = trim((string) ($address?->phone ?? $customer?->phone ?? '9999999999'));
        $shippingAddress = trim((string) ($address?->address_line ?? ''));
        $shippingAddress2 = trim((string) ($address?->address_line2 ?? ''));
        if ($shippingAddress2 !== '') {
            $shippingAddress .= ', ' . $shippingAddress2;
        }

        $destinationPin = trim((string) ($address?->post_code ?? ''));
        $destinationCity = trim((string) ($address?->districtData?->name ?? $address?->district ?? ''));
        $destinationState = trim((string) ($address?->stateData?->name ?? $address?->state ?? ''));

        $pickupName = trim((string) (data_get(config('services'), 'delhivery.pickup_location', 'Primary') ?: 'Primary'));
        $pickupAddress = trim((string) ($shop?->address ?? ''));
        $pickupPin = trim((string) ($shop?->pincode ?? ''));
        $pickupCity = trim((string) ($shop?->district ?? ''));
        $pickupState = trim((string) ($shop?->state ?? ''));
        $pickupPhone = trim((string) ($shop?->phone_number ?? $shop?->user?->phone ?? '9999999999'));

        if (
            $customerName === '' ||
            $customerPhone === '' ||
            $shippingAddress === '' ||
            $destinationPin === '' ||
            $pickupName === '' ||
            $pickupAddress === '' ||
            $pickupPin === '' ||
            $pickupCity === '' ||
            $pickupState === ''
        ) {
            $this->lastSyncError = 'Missing mandatory address data for Delhivery shipment.';
            Log::warning('Delhivery sync skipped due to missing mandatory address data', [
                'order_id' => $order->id,
                'customer_name_present' => $customerName !== '',
                'customer_phone_present' => $customerPhone !== '',
                'shipping_address_present' => $shippingAddress !== '',
                'destination_pin_present' => $destinationPin !== '',
                'pickup_name_present' => $pickupName !== '',
                'pickup_address_present' => $pickupAddress !== '',
                'pickup_pin_present' => $pickupPin !== '',
                'pickup_city_present' => $pickupCity !== '',
                'pickup_state_present' => $pickupState !== '',
            ]);
            $this->saveAttemptJson('latest_manifest_request_response.json', $order, [
                'outcome' => 'skipped',
                'reason' => 'missing_mandatory_address_data',
                'diagnostics' => [
                    'customer_name_present' => $customerName !== '',
                    'customer_phone_present' => $customerPhone !== '',
                    'shipping_address_present' => $shippingAddress !== '',
                    'destination_pin_present' => $destinationPin !== '',
                    'pickup_name_present' => $pickupName !== '',
                    'pickup_address_present' => $pickupAddress !== '',
                    'pickup_pin_present' => $pickupPin !== '',
                    'pickup_city_present' => $pickupCity !== '',
                    'pickup_state_present' => $pickupState !== '',
                ],
            ]);
            return false;
        }

        $shipmentQuantity = (int) max(1, (int) $order->orderProducts->sum(fn ($orderProduct) => (int) ($orderProduct->quantity ?? 0)));
        $isCod = $order->payment_method === PaymentMethod::CASH;
        $packageMetrics = $this->resolveOrderPackageMetrics($order);
        $productsDescription = $order->orderProducts
            ->map(function ($orderProduct) {
                $name = trim((string) ($orderProduct->orderBulkItem?->name ?? $orderProduct->product_name ?? $orderProduct->product?->name ?? 'Item'));
                $quantity = max(1, (int) ($orderProduct->quantity ?? 1));
                return $name . ' x ' . $quantity;
            })
            ->filter(fn ($line) => $line !== '')
            ->implode(', ');

        $payload = [
            'pickup_location' => [
                // 'name' => $pickupName,
                'name' => 'Default Pickup Location',
            ],
            'shipments' => [
                [
                    'order' => (string) ($order->prefix . $order->order_code),
                    'name' => $customerName,
                    'add' => $shippingAddress,
                    'pin' => $destinationPin,
                    'city' => $destinationCity,
                    'state' => $destinationState,
                    'country' => 'India',
                    'phone' => $customerPhone,
                    'payment_mode' => $isCod ? 'COD' : 'Prepaid',
                    'total_amount' => (float) ($order->payable_amount ?? 0),
                    'cod_amount' => $isCod ? (float) ($order->payable_amount ?? 0) : 0,
                    'quantity' => (string) $shipmentQuantity,
                    'products_desc' => $productsDescription,
                    'return_name' => $pickupName,
                    'return_add' => $pickupAddress,
                    'return_city' => $pickupCity,
                    'return_state' => $pickupState,
                    'return_country' => 'India',
                    'return_phone' => $pickupPhone,
                    'return_pin' => $pickupPin,
                ],
            ],
        ];

        if (!empty($packageMetrics['weight_grams'])) {
            $payload['shipments'][0]['weight'] = (float) $packageMetrics['weight_grams'];
        }

        if (!empty($packageMetrics['length_cm'])) {
            $payload['shipments'][0]['shipment_length'] = (float) $packageMetrics['length_cm'];
        }

        if (!empty($packageMetrics['width_cm'])) {
            $payload['shipments'][0]['shipment_width'] = (float) $packageMetrics['width_cm'];
        }

        if (!empty($packageMetrics['height_cm'])) {
            $payload['shipments'][0]['shipment_height'] = (float) $packageMetrics['height_cm'];
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Token ' . $apiToken,
                    'Token' => $apiToken,
                ])
                ->asForm()
                ->post($manifestEndpoint, [
                    'format' => 'json',
                    'data' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                ]);

            $this->writeLatestManifestResponse($order, [
                'outcome' => $response->successful() ? 'http_success' : 'http_fail',
                'endpoint' => $manifestEndpoint,
                'form' => [
                    'format' => 'json',
                    'data' => $payload,
                ],
                'response' => [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->json() ?? $response->body(),
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('Delhivery order sync failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->lastSyncError = $response->json('message')
                    ?? $response->json('rmk')
                    ?? ('Delhivery request failed with status ' . $response->status() . '.');
                return false;
            }

            $uploadWbn = (string) ($response->json('upload_wbn') ?? '');
            $packages = (array) ($response->json('packages') ?? []);
            $firstPackage = is_array($packages) && isset($packages[0]) && is_array($packages[0]) ? $packages[0] : [];
            $packageStatus = strtoupper(trim((string) ($firstPackage['status'] ?? '')));
            $waybill = trim((string) ($firstPackage['waybill'] ?? ''));

            $apiSuccess = $response->json('success');
            if ($apiSuccess === false) {
                Log::warning('Delhivery order sync api success=false', [
                    'order_id' => $order->id,
                    'upload_wbn' => $uploadWbn,
                    'response' => $response->json(),
                ]);
                $this->lastSyncError = (string) (
                    $firstPackage['remarks'][0]
                    ?? $response->json('rmk')
                    ?? 'Delhivery returned success=false for shipment creation.'
                );
                $this->writeLatestManifestResponse($order, [
                    'outcome' => 'business_fail',
                    'reason' => 'api_success_false',
                    'endpoint' => $manifestEndpoint,
                    'form' => [
                        'format' => 'json',
                        'data' => $payload,
                    ],
                    'response' => [
                        'status' => $response->status(),
                        'successful' => $response->successful(),
                        'body' => $response->json() ?? $response->body(),
                    ],
                ]);
                return false;
            }

            if ($packageStatus === 'FAIL') {
                Log::warning('Delhivery order sync package failed', [
                    'order_id' => $order->id,
                    'upload_wbn' => $uploadWbn,
                    'response' => $response->json(),
                ]);
                $this->lastSyncError = (string) (
                    $firstPackage['remarks'][0]
                    ?? $response->json('rmk')
                    ?? 'Delhivery package status is FAIL.'
                );
                $this->writeLatestManifestResponse($order, [
                    'outcome' => 'business_fail',
                    'reason' => 'package_status_fail',
                    'endpoint' => $manifestEndpoint,
                    'form' => [
                        'format' => 'json',
                        'data' => $payload,
                    ],
                    'response' => [
                        'status' => $response->status(),
                        'successful' => $response->successful(),
                        'body' => $response->json() ?? $response->body(),
                    ],
                ]);
                return false;
            }

            $trackUrl = $waybill !== ''
                ? 'https://www.delhivery.com/track/package/' . $waybill
                : $order->track_url;

            $order->update([
                'api_provider' => 'delhivery',
                'provider_order_id' => $uploadWbn !== '' ? $uploadWbn : $order->provider_order_id,
                'provider_shipment_id' => $waybill !== '' ? $waybill : $order->provider_shipment_id,
                'provider_awb_code' => $waybill !== '' ? $waybill : $order->provider_awb_code,
                'track_url' => $trackUrl,
            ]);

            $this->writeLatestManifestResponse($order, [
                'outcome' => 'success',
                'result' => [
                    'upload_wbn' => $uploadWbn,
                    'waybill' => $waybill,
                    'track_url' => $trackUrl,
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Delhivery order sync exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            $this->lastSyncError = $e->getMessage();

            $this->saveAttemptJson('latest_manifest_request_response.json', $order, [
                'outcome' => 'exception',
                'reason' => 'exception_while_requesting_manifest',
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ]);

            return false;
        }
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function requestPickup(Order $order): bool
    {
        $order->loadMissing(['shop.deliverySetting']);

        $setting = $order->shop?->deliverySetting;

        if (!$setting || $setting->delivery_mode !== 'provider_api' || $setting->delivery_provider !== 'delhivery') {
            $this->saveAttemptJson('latest_pickup_request_response.json', $order, [
                'outcome' => 'skipped',
                'reason' => 'invalid_or_missing_delhivery_provider_setting',
            ]);
            return false;
        }

        $apiToken = $this->normalizeApiToken((string) ($setting->provider_api_key ?? ''));
        if ($apiToken === '') {
            $this->saveAttemptJson('latest_pickup_request_response.json', $order, [
                'outcome' => 'skipped',
                'reason' => 'missing_api_token',
            ]);
            return false;
        }

        $delhiveryBaseUrl = rtrim((string) (data_get(config('services'), 'delhivery.base_url', 'https://track.delhivery.com') ?: 'https://track.delhivery.com'), '/');
        $pickupEndpoint = $delhiveryBaseUrl . '/fm/request/new/';

        $pickupLocation = trim((string) (data_get(config('services'), 'delhivery.pickup_location', 'Primary') ?: 'Primary'));

        $payload = [
            'pickup_time' => now()->format('H:i:s'),
            'pickup_date' => now()->format('Y-m-d'),
            'pickup_location' => $pickupLocation,
            'expected_package_count' => max(1, (int) $order->products->sum(fn ($product) => (int) ($product->pivot->quantity ?? 0))),
        ];

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Token ' . $apiToken,
                    'Token' => $apiToken,
                ])
                ->post($pickupEndpoint, $payload);

            $this->writeLatestPickupResponse($order, [
                'outcome' => $response->successful() ? 'http_success' : 'http_fail',
                'endpoint' => $pickupEndpoint,
                'payload' => $payload,
                'response' => [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->json() ?? $response->body(),
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('Delhivery pickup request failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $this->writeLatestPickupResponse($order, [
                'outcome' => 'success',
                'result' => [
                    'pickup_location' => $pickupLocation,
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Delhivery pickup request exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            $this->saveAttemptJson('latest_pickup_request_response.json', $order, [
                'outcome' => 'exception',
                'reason' => 'exception_while_requesting_pickup',
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ]);
            return false;
        }
    }

    private function writeLatestManifestResponse(Order $order, array $data): void
    {
        $this->saveAttemptJson('latest_manifest_request_response.json', $order, $data);
    }

    private function writeLatestPickupResponse(Order $order, array $data): void
    {
        $this->saveAttemptJson('latest_pickup_request_response.json', $order, $data);
    }

    private function saveAttemptJson(string $fileName, Order $order, array $data): void
    {
        $this->writeDebugJson($fileName, [
            'captured_at' => now()->toDateTimeString(),
            'order_id' => $order->id,
            'order_code' => $order->prefix . $order->order_code,
            ...$data,
        ]);
    }

    private function writeDebugJson(string $fileName, array $data): void
    {
        try {
            $filePath = public_path('delhivery/' . ltrim($fileName, '/'));
            File::ensureDirectoryExists(dirname($filePath));
            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Failed to write delhivery debug json', [
                'file' => $fileName,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeApiToken(string $token): string
    {
        $normalized = trim($token);
        $normalized = preg_replace('/^token\s+/i', '', $normalized) ?? $normalized;

        return trim($normalized);
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
