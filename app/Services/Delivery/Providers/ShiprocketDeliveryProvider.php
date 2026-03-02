<?php

namespace App\Services\Delivery\Providers;

use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\DeliverySetting;
use App\Services\Contracts\DeliveryRateProviderInterface;
use App\Services\Delivery\DeliveryPostcodeResolver;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
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

        $cartMetrics = $this->resolveCartPackageMetrics($shop);
        $weight = max(0.001, ((float) ($cartMetrics['weight_grams'] ?? 500)) / 1000);
        $lengthValue = $cartMetrics['length'];
        $widthValue = $cartMetrics['width'];
        $heightValue = $cartMetrics['height'];

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
                $declaredValue,
                $lengthValue,
                $widthValue,
                $heightValue
            ) {
                $query = [
                    'pickup_postcode' => $pickupPostcode,
                    'delivery_postcode' => $deliveryPostcode,
                    'weight' => $weight,
                    'cod' => $cod,
                    'declared_value' => $declaredValue,
                ];

                if ($lengthValue !== null) {
                    $query['length'] = $lengthValue;
                }

                if ($widthValue !== null) {
                    $query['breadth'] = $widthValue;
                }

                if ($heightValue !== null) {
                    $query['height'] = $heightValue;
                }

                return Http::timeout(20)
                    ->acceptJson()
                    ->withToken($bearerToken)
                    ->get($baseUrl . '/courier/serviceability/', $query);
            };

            $serviceResponse = $callServiceability($token);
            $this->writeLatestServiceabilityResponse(
                $serviceResponse,
                [
                    'shop_id' => $shop?->id,
                    'pickup_postcode' => $pickupPostcode,
                    'delivery_postcode' => $deliveryPostcode,
                    'weight' => $weight,
                    'length' => $lengthValue,
                    'breadth' => $widthValue,
                    'height' => $heightValue,
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
                        'length' => $lengthValue,
                        'breadth' => $widthValue,
                        'height' => $heightValue,
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

    private function resolveCartPackageMetrics($shop): array
    {
        $weightInGrams = 0;
        $length = null;
        $width = null;
        $height = null;

        $customerId = Auth::user()?->customer?->id;
        if (!$customerId || !$shop?->id) {
            return [
                'weight_grams' => 500,
                'length' => null,
                'width' => null,
                'height' => null,
            ];
        }

        $isBuyNow = request()->boolean('is_buy_now', false);

        $carts = Cart::query()
            ->with([
                'product:id,weight,length,width,height',
                'variant:id,weight,length,width,height',
                'bulkItem:id,weight,length,width,height',
            ])
            ->where('customer_id', $customerId)
            ->where('shop_id', $shop->id)
            ->where('is_buy_now', $isBuyNow)
            ->get();

        foreach ($carts as $cart) {
            $quantity = max(1, (int) ($cart->quantity ?? 1));

            $unitWeight = $this->firstPositiveInt([
                $cart->variant?->weight,
                $cart->bulkItem?->weight,
                $cart->product?->weight,
            ]);

            if ($unitWeight !== null) {
                $weightInGrams += $unitWeight * $quantity;
            }

            $itemLength = $this->firstPositiveFloat([
                $cart->variant?->length,
                $cart->bulkItem?->length,
                $cart->product?->length,
            ]);
            $itemWidth = $this->firstPositiveFloat([
                $cart->variant?->width,
                $cart->bulkItem?->width,
                $cart->product?->width,
            ]);
            $itemHeight = $this->firstPositiveFloat([
                $cart->variant?->height,
                $cart->bulkItem?->height,
                $cart->product?->height,
            ]);

            if ($itemLength !== null) {
                $length = $length === null ? $itemLength : max($length, $itemLength);
            }
            if ($itemWidth !== null) {
                $width = $width === null ? $itemWidth : max($width, $itemWidth);
            }
            if ($itemHeight !== null) {
                $height = $height === null ? $itemHeight : max($height, $itemHeight);
            }
        }

        return [
            'weight_grams' => $weightInGrams > 0 ? $weightInGrams : 500,
            'length' => $length,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function firstPositiveInt(array $values): ?int
    {
        foreach ($values as $value) {
            if (is_numeric($value) && (float) $value > 0) {
                return (int) round((float) $value);
            }
        }

        return null;
    }

    private function firstPositiveFloat(array $values): ?float
    {
        foreach ($values as $value) {
            if (is_numeric($value) && (float) $value > 0) {
                return (float) $value;
            }
        }

        return null;
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
