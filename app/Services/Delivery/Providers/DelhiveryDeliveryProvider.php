<?php

namespace App\Services\Delivery\Providers;

use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\DeliverySetting;
use App\Services\Contracts\DeliveryRateProviderInterface;
use App\Services\Delivery\DeliveryPostcodeResolver;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DelhiveryDeliveryProvider implements DeliveryRateProviderInterface
{
    public function __construct(
        protected DeliveryPostcodeResolver $postcodeResolver
    ) {}

    public function getKey(): string
    {
        return 'delhivery';
    }

    public function getCharge(float $totalAmount, $shop, ?int $stateId, DeliverySetting $setting): ?float
    {
        $pickupPostcode = trim((string) ($shop?->pincode ?? ''));
        $deliveryPostcode = $this->postcodeResolver->resolve($stateId);

        if (!$pickupPostcode || !$deliveryPostcode) {
            return null;
        }

        $apiToken = trim((string) ($setting->provider_api_key ?? ''));
        if (!$apiToken) {
            return null;
        }

        $cartMetrics = $this->resolveCartPackageMetrics($shop);
        $chargeableWeightInGrams = max(1, (int) ($cartMetrics['weight_grams'] ?? 500));
        $lengthValue = $cartMetrics['length'];
        $widthValue = $cartMetrics['width'];
        $heightValue = $cartMetrics['height'];

        $rawPaymentMethod = trim((string) request()->input('payment_method', ''));
        $normalizedPaymentMethod = strtolower($rawPaymentMethod);
        $slugPaymentMethod = (string) preg_replace('/[^a-z0-9]+/', '_', $normalizedPaymentMethod);

        $isCod = false;
        if (request()->boolean('is_cod')) {
            $isCod = true;
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
            $isCod = true;
        }

        $deliveryMode = strtoupper((string) request()->input('delhivery_mode', 'S'));
        if (!in_array($deliveryMode, ['S', 'E'], true)) {
            $deliveryMode = 'S';
        }

        $shipmentSubtypes = ['DTO'];

        try {
            $lastResponse = null;

            foreach ($shipmentSubtypes as $shipmentSubtype) {
                $query = [
                    'md' => $deliveryMode,
                    'ss' => $shipmentSubtype,
                    'd_pin' => $deliveryPostcode,
                    'o_pin' => $pickupPostcode,
                    'cgm' => $chargeableWeightInGrams,
                    'pt' => $isCod ? 'COD' : 'Pre-paid',
                    'declared_value' => max(1, (float) $totalAmount),
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

                $delhiveryBaseUrl = rtrim((string) (data_get(config('services'), 'delhivery.base_url', 'https://track.delhivery.com') ?: 'https://track.delhivery.com'), '/');
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => 'Token ' . $apiToken,
                    ])
                    ->get($delhiveryBaseUrl . '/api/kinko/v1/invoice/charges/.json', $query);

                $lastResponse = $response;

                $this->writeLatestInvoiceResponse(
                    $response,
                    [
                        'shop_id' => $shop?->id,
                        'pickup_postcode' => $pickupPostcode,
                        'delivery_postcode' => $deliveryPostcode,
                        'delivery_mode' => $deliveryMode,
                        'shipment_subtype' => $shipmentSubtype,
                        'chargeable_weight_grams' => $chargeableWeightInGrams,
                        'length' => $lengthValue,
                        'breadth' => $widthValue,
                        'height' => $heightValue,
                    ]
                );

                if (!$response->successful()) {
                    continue;
                }

                $charge = $this->extractChargeValue($response->json());
                if ($charge !== null) {
                    return $charge;
                }
            }

            if ($lastResponse) {
                Log::warning('Delhivery invoice API did not return charge for any ss value', [
                    'shop_id' => $shop?->id,
                    'status' => $lastResponse->status(),
                    'body' => $lastResponse->json() ?? $lastResponse->body(),
                ]);
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Delhivery delivery charge fetch failed', [
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

    private function extractChargeValue(mixed $payload): ?float
    {
        if (!is_array($payload)) {
            return null;
        }

        $rows = array_is_list($payload) ? $payload : [$payload];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (array_key_exists('total_amount', $row) && is_numeric($row['total_amount'])) {
                return (float) $row['total_amount'];
            }

            if (array_key_exists('gross_amount', $row) && is_numeric($row['gross_amount'])) {
                return (float) $row['gross_amount'];
            }
        }

        $candidatePaths = [
            'data.total_amount',
            'data.total',
            '0.total_amount',
            '0.gross_amount',
            'total_amount',
            'total',
            'amount',
            'charges.total',
            'charge.total',
            'data.freight_charge',
            'freight_charge',
            'freight',
        ];

        foreach ($candidatePaths as $path) {
            $value = data_get($payload, $path);
            if (is_numeric($value) && (float) $value >= 0) {
                return (float) $value;
            }
        }

        $queue = [$payload];

        while (!empty($queue)) {
            $node = array_shift($queue);

            if (!is_array($node)) {
                continue;
            }

            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $queue[] = $value;
                    continue;
                }

                if (!is_numeric($value)) {
                    continue;
                }

                $lowerKey = strtolower((string) $key);
                if (!str_contains($lowerKey, 'charge') && !str_contains($lowerKey, 'amount') && !str_contains($lowerKey, 'total')) {
                    continue;
                }

                if (str_contains($lowerKey, 'gross') || str_contains($lowerKey, 'total_amount') || str_contains($lowerKey, 'total')) {
                    $parsed = (float) $value;
                    if ($parsed >= 0) {
                        return $parsed;
                    }
                }
            }
        }

        return null;
    }

    private function writeLatestInvoiceResponse(HttpResponse $response, array $requestMeta = []): void
    {
        try {
            $data = [
                'captured_at' => now()->toDateTimeString(),
                'request' => $requestMeta,
                'response' => [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->json() ?? $response->body(),
                ],
            ];

            $this->saveResponseAsJson($data);
        } catch (\Throwable $e) {
            Log::warning('Failed to write latest delhivery invoice response json', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function saveResponseAsJson(array $data, string $fileName = 'latest_invoice_response.json'): void
    {
        $filePath = public_path('delhivery/' . ltrim($fileName, '/'));
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
