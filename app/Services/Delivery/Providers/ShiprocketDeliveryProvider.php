<?php

namespace App\Services\Delivery\Providers;

use App\Enums\PaymentMethod;
use App\Models\DeliverySetting;
use App\Services\Contracts\DeliveryRateProviderInterface;
use App\Services\Delivery\DeliveryPostcodeResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketDeliveryProvider implements DeliveryRateProviderInterface
{
    public function __construct(
        protected DeliveryPostcodeResolver $postcodeResolver
    ) {
    }

    public function getKey(): string
    {
        return 'shiprocket';
    }

    public function getCharge(float $totalAmount, $shop, ?int $stateId, DeliverySetting $setting): ?float
    {
        $pickupPostcode = trim((string) ($shop?->pincode ?? ''));
        $deliveryPostcode = $this->postcodeResolver->resolve($stateId);

        if (!$pickupPostcode || !$deliveryPostcode) {
            return null;
        }

        $apiUserEmail = trim((string) ($setting->provider_api_key ?? ''));
        $apiUserPassword = trim((string) ($setting->provider_api_secret ?? ''));

        if (!$apiUserEmail || !$apiUserPassword) {
            return null;
        }

        $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
        $tokenCacheKey = 'shiprocket.token.shop.' . ($shop?->id ?? 'na') . '.' . md5($apiUserEmail);

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
            return null;
        }

        $weight = (float) request()->input('weight', 0.5);
        if ($weight <= 0) {
            $weight = 0.5;
        }

        $rawPaymentMethod = trim((string) request()->input('payment_method', ''));
        $normalizedPaymentMethod = strtolower($rawPaymentMethod);
        $slugPaymentMethod = (string) preg_replace('/[^a-z0-9]+/', '_', $normalizedPaymentMethod);

        $cod = 0;
        if (request()->boolean('is_cod')) {
            $cod = 1;
        } elseif ($rawPaymentMethod === '' && !request()->has('is_cod')) {
            $cod = 1;
        } elseif (in_array($normalizedPaymentMethod, [
            'cod',
            'cash_on_delivery',
            'cash',
            strtolower(PaymentMethod::CASH->value),
            strtolower(PaymentMethod::CASH->name),
        ], true) || in_array($slugPaymentMethod, [
            'cod',
            'cash_on_delivery',
            'cash',
            'cash_payment',
        ], true)) {
            $cod = 1;
        }

        $declaredValue = (float) $totalAmount;
        if ($declaredValue <= 0) {
            $declaredValue = 1;
        }

        try {
            $callServiceability = function (string $bearerToken) use (
                $baseUrl,
                $pickupPostcode,
                $deliveryPostcode,
                $weight,
                $cod,
                $declaredValue
            ) {
                return Http::timeout(20)
                    ->acceptJson()
                    ->withToken($bearerToken)
                    ->get($baseUrl . '/courier/serviceability/', [
                        'pickup_postcode' => $pickupPostcode,
                        'delivery_postcode' => $deliveryPostcode,
                        'weight' => $weight,
                        'cod' => $cod,
                        'declared_value' => $declaredValue,
                    ]);
            };

            $serviceResponse = $callServiceability($token);

            if ($serviceResponse->status() === 401) {
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
                    return null;
                }

                $serviceResponse = $callServiceability($newToken);
            }

            if (!$serviceResponse->successful()) {
                return null;
            }

            $companies = (array) $serviceResponse->json('data.available_courier_companies', []);
            if (empty($companies)) {
                $companies = (array) $serviceResponse->json('available_courier_companies', []);
            }

            if (empty($companies)) {
                return null;
            }

            $charges = collect($companies)
                ->map(function ($company) {
                    if (!is_array($company)) {
                        return null;
                    }

                    $possibleKeys = ['rate', 'freight_charge', 'courier_charge', 'total_charge', 'shipping_charge'];
                    foreach ($possibleKeys as $key) {
                        if (isset($company[$key]) && is_numeric($company[$key])) {
                            return (float) $company[$key];
                        }
                    }

                    return null;
                })
                ->filter(fn ($value) => $value !== null)
                ->values();

            if ($charges->isEmpty()) {
                return null;
            }

            return (float) $charges->min();
        } catch (\Throwable $e) {
            Log::warning('Shiprocket delivery charge fetch failed', [
                'shop_id' => $shop?->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
