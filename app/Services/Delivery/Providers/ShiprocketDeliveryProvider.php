<?php

namespace App\Services\Delivery\Providers;

use App\Enums\PaymentMethod;
use App\Models\DeliverySetting;
use App\Services\Contracts\DeliveryRateProviderInterface;
use App\Services\Delivery\DeliveryPostcodeResolver;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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
            $this->writeLatestServiceabilityResponse(
                $serviceResponse,
                [
                    'shop_id' => $shop?->id,
                    'pickup_postcode' => $pickupPostcode,
                    'delivery_postcode' => $deliveryPostcode,
                    'weight' => $weight,
                    'cod' => $cod,
                    'declared_value' => $declaredValue,
                    'retry' => false,
                ]
            );

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
                $this->writeLatestServiceabilityResponse(
                    $serviceResponse,
                    [
                        'shop_id' => $shop?->id,
                        'pickup_postcode' => $pickupPostcode,
                        'delivery_postcode' => $deliveryPostcode,
                        'weight' => $weight,
                        'cod' => $cod,
                        'declared_value' => $declaredValue,
                        'retry' => true,
                    ]
                );
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

            $recommendedCourierCompanyId = $serviceResponse->json('data.recommended_courier_company_id')
                ?? $serviceResponse->json('recommended_courier_company_id')
                ?? $serviceResponse->json('data.shiprocket_recommended_courier_id')
                ?? $serviceResponse->json('shiprocket_recommended_courier_id');

            if ($recommendedCourierCompanyId !== null) {
                $recommendedFreight = collect($companies)
                    ->first(function ($company) use ($recommendedCourierCompanyId) {
                        if (!is_array($company)) {
                            return false;
                        }

                        $companyId = $company['courier_company_id'] ?? null;
                        if ((string) $companyId !== (string) $recommendedCourierCompanyId) {
                            return false;
                        }

                        return isset($company['freight_charge']) && is_numeric($company['freight_charge']);
                    });

                if (is_array($recommendedFreight) && isset($recommendedFreight['freight_charge'])) {
                    $freightCharge = (float) $recommendedFreight['freight_charge'];
                    $coverageCharges = (isset($recommendedFreight['coverage_charges']) && is_numeric($recommendedFreight['coverage_charges']))
                        ? (float) $recommendedFreight['coverage_charges']
                        : 0.0;
                    if ($freightCharge >= 0 && $coverageCharges >= 0) {
                        return $freightCharge + $coverageCharges;
                    }
                }
            }

            $charges = collect($companies)
                ->map(function ($company) {
                    if (!is_array($company)) {
                        return null;
                    }

                    if (isset($company['freight_charge']) && is_numeric($company['freight_charge'])) {
                        $freightCharge = (float) $company['freight_charge'];
                        $coverageCharges = (isset($company['coverage_charges']) && is_numeric($company['coverage_charges']))
                            ? (float) $company['coverage_charges']
                            : 0.0;

                        if ($freightCharge < 0 || $coverageCharges < 0) {
                            return null;
                        }

                        return $freightCharge + $coverageCharges;
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

    private function writeLatestServiceabilityResponse(HttpResponse $response, array $requestMeta = []): void
    {
        try {
            $filePath = public_path('shiprocket/latest_serviceability_response.json');
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
            Log::warning('Failed to write latest shiprocket serviceability response json', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
