<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use App\Models\DeliveryAmountRule;
use App\Models\DeliveryStateCharge;
use App\Models\Shop;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliverySettingController extends Controller
{
    public function validateProvider(Request $request)
    {
        $shop = generaleSetting('shop');
        if (!$shop instanceof Shop) {
            throw ValidationException::withMessages([
                'shop' => 'Shop context is invalid.',
            ]);
        }

        $existingSetting = DeliverySetting::where('shop_id', $shop->id)->first();

        $this->validateDeliveryProviderCredentials($request, $shop, $existingSetting);

        return $this->json('Delivery provider credentials verified successfully');
    }

    /**
     * Get delivery settings for a shop
     */
    public function show(Request $request)
    {
        // $shopId = auth()->user()->shop_id ?? null;

        $shop = generaleSetting('shop');

        $setting = DeliverySetting::with([
            'amountRules',
            'stateCharges.state',
        ])->where('shop_id', $shop->id)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Delivery settings not found',
            ], 404);
        }

        $selectedIds = $setting?->selected_state_ids ?? [];

        // Legacy data compatibility: older records may have provider_api mode.
        // Delivery charge mode is now strictly amount_based/state_wise/manual.
        if ($setting && $setting->delivery_mode === 'provider_api') {
            $setting->delivery_mode = 'manual';
        }

        // return response()->json($setting);
        return $this->json('Delivery Settings', [
            'settings' => $setting,
            'days' => $shop->estimated_delivery_time,
            'selected_states' => State::whereIn('id', $selectedIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Get states
     */
    public function getStates(Request $request)
    {
        $shop = generaleSetting('shop');
        $setting = DeliverySetting::where('shop_id', $shop->id)->first();
        $selectedIds = $setting?->selected_state_ids ?? [];

        return $this->json('State List', [
            'states' => State::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'days' => $shop->estimated_delivery_time,
            'selected_states' => State::whereIn('id', $selectedIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'selected_state_ids' => $selectedIds,
        ]);
    }

    public function saveSelectedStates(Request $request)
    {
        $validated = $request->validate([
            'selected_state_ids' => ['required', 'array'],
            'days' => ['nullable', 'string'],
            'selected_state_ids.*' => ['integer', 'exists:states,id'],
        ]);

        $shop = generaleSetting('shop');

        $setting = DeliverySetting::updateOrCreate(
            ['shop_id' => strval($shop->id)],
            [
                'selected_state_ids' => $validated['selected_state_ids'],
            ]
        );

        if (!empty($validated['days'])) {
            $shop->update([
                'estimated_delivery_time' => $validated['days'],
            ]);
        }

        return $this->json('Selected states saved successfully', [
            'selected_state_ids' => $validated['selected_state_ids'],
        ]);
    }

    /**
     * Store or update delivery settings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_mode' => [
                'required',
                Rule::in(['amount_based', 'state_wise', 'manual']),
            ],
            'delivery_api_enabled' => ['nullable', 'boolean'],
            'delivery_provider' => ['nullable', Rule::in(['shiprocket', 'delhivery'])],
            'provider_api_key' => ['nullable', 'string', 'max:255'],
            'provider_api_secret' => ['nullable', 'string', 'max:255'],
            'update_when_shipped' => ['boolean'],

            'amount_rules' => ['array'],
            'amount_rules.*.min_amount' => [
                'required_if:delivery_mode,amount_based',
                'numeric',
                'min:0',
            ],
            'amount_rules.*.max_amount' => [
                'required_if:delivery_mode,amount_based',
                'numeric',
                'gt:amount_rules.*.min_amount',
            ],
            'amount_rules.*.charge' => [
                'required_if:delivery_mode,amount_based',
                'numeric',
                'min:0',
            ],

            'state_charges' => ['array'],
            'state_charges.*.state' => [
                'required_if:delivery_mode,state_wise',
                'integer',
                'exists:states,id',
            ],
            'state_charges.*.charge' => [
                'required_if:delivery_mode,state_wise',
                'numeric',
                'min:0',
            ],
        ]);

        $shop = generaleSetting('shop');
        if (!$shop instanceof Shop) {
            throw ValidationException::withMessages([
                'shop' => 'Shop context is invalid.',
            ]);
        }
        $existingSetting = DeliverySetting::where('shop_id', $shop->id)->first();
        $deliveryApiEnabled = array_key_exists('delivery_api_enabled', $validated)
            ? !empty($validated['delivery_api_enabled'])
            : (bool) ($existingSetting?->delivery_api_enabled ?? false);

        if ($deliveryApiEnabled) {
            $this->validateDeliveryProviderCredentials($request, $shop, $existingSetting, $validated);
        }

        DB::transaction(function () use ($validated, $shop, $existingSetting, $deliveryApiEnabled) {

            $providerApiKey = isset($validated['provider_api_key']) && $validated['provider_api_key'] !== null
                ? $validated['provider_api_key']
                : ($existingSetting?->provider_api_key);

            $providerApiSecret = isset($validated['provider_api_secret']) && $validated['provider_api_secret'] !== null
                ? $validated['provider_api_secret']
                : ($existingSetting?->provider_api_secret);

            $deliveryProvider = isset($validated['delivery_provider']) && $validated['delivery_provider'] !== null
                ? $validated['delivery_provider']
                : ($existingSetting?->delivery_provider);

            $setting = DeliverySetting::updateOrCreate(
                ['shop_id' => $shop->id],
                [
                    'delivery_mode' => $validated['delivery_mode'],
                    'delivery_api_enabled' => $deliveryApiEnabled,
                    'delivery_provider' => $deliveryProvider ?: null,
                    'provider_api_key' => $providerApiKey ?: null,
                    'provider_api_secret' => $providerApiSecret ?: null,
                    'update_when_shipped' => $validated['update_when_shipped'] ?? false,
                ]
            );

            // Clean old data
            DeliveryAmountRule::where('delivery_setting_id', $setting->id)->delete();
            DeliveryStateCharge::where('delivery_setting_id', $setting->id)->delete();

            // Amount based
            if ($validated['delivery_mode'] === 'amount_based') {
                foreach ($validated['amount_rules'] ?? [] as $rule) {
                    DeliveryAmountRule::create([
                        'delivery_setting_id' => $setting->id,
                        'min_amount' => $rule['min_amount'],
                        'max_amount' => $rule['max_amount'],
                        'charge'     => $rule['charge'],
                    ]);
                }
            }

            // State wise
            if ($validated['delivery_mode'] === 'state_wise') {
                foreach ($validated['state_charges'] ?? [] as $state) {
                    $stateModel = State::find($state['state']);

                    DeliveryStateCharge::create([
                        'delivery_setting_id' => $setting->id,
                        'state' => $stateModel->name,
                        'state_id' => $stateModel->id,
                        'charge' => $state['charge'],
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Delivery settings saved successfully',
            'delivery_mode' => $validated['delivery_mode'],
        ]);
    }

    private function validateDeliveryProviderCredentials(
        Request $request,
        Shop $shop,
        ?DeliverySetting $existingSetting = null,
        ?array $validated = null
    ): void {
        $deliveryApiEnabled = is_array($validated) && array_key_exists('delivery_api_enabled', $validated)
            ? !empty($validated['delivery_api_enabled'])
            : $request->boolean('delivery_api_enabled');

        if (!$deliveryApiEnabled) {
            return;
        }

        $provider = strtolower(trim((string) (
            (is_array($validated) ? ($validated['delivery_provider'] ?? null) : null)
            ?? $request->input('delivery_provider')
            ?? ($existingSetting?->delivery_provider ?? '')
        )));

        $apiKey = trim((string) (
            (is_array($validated) ? ($validated['provider_api_key'] ?? null) : null)
            ?? $request->input('provider_api_key')
            ?? ($existingSetting?->provider_api_key ?? '')
        ));

        $apiSecret = trim((string) (
            (is_array($validated) ? ($validated['provider_api_secret'] ?? null) : null)
            ?? $request->input('provider_api_secret')
            ?? ($existingSetting?->provider_api_secret ?? '')
        ));

        if ($provider === '') {
            throw ValidationException::withMessages([
                'delivery_provider' => 'Please select a delivery API provider.',
            ]);
        }

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'provider_api_key' => 'The provider API key field is required when an API provider is selected.',
            ]);
        }

        if ($provider === 'shiprocket') {
            if ($apiSecret === '') {
                throw ValidationException::withMessages([
                    'provider_api_secret' => 'The provider API secret field is required for Shiprocket.',
                ]);
            }

            try {
                $authResponse = Http::timeout(20)->acceptJson()->post('https://apiv2.shiprocket.in/v1/external/auth/login', [
                    'email' => $apiKey,
                    'password' => $apiSecret,
                ]);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'provider_api_key' => 'Unable to verify Shiprocket credentials right now. Please try again.',
                ]);
            }

            $token = (string) ($authResponse->json('token') ?? '');
            if (!$authResponse->successful() || $token === '') {
                throw ValidationException::withMessages([
                    'provider_api_key' => (string) ($authResponse->json('message')
                        ?? $authResponse->json('error')
                        ?? 'Invalid Shiprocket credentials. Please check API key and secret.'),
                ]);
            }

            return;
        }

        if ($provider === 'delhivery') {
            $delhiveryBaseUrl = rtrim((string) (data_get(config('services'), 'delhivery.base_url', 'https://track.delhivery.com') ?: 'https://track.delhivery.com'), '/');
            $originPin = trim((string) (($request->input('pincode') ?: ($shop->pincode ?? '110001'))));

            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => 'Token ' . $apiKey,
                    ])
                    ->get($delhiveryBaseUrl . '/api/kinko/v1/invoice/charges/.json', [
                        'md' => 'S',
                        'ss' => 'DTO',
                        'd_pin' => $originPin,
                        'o_pin' => $originPin,
                        'cgm' => 1000,
                        'pt' => 'Pre-paid',
                        'declared_value' => 1,
                    ]);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'provider_api_key' => 'Unable to verify Delhivery API key right now. Please try again.',
                ]);
            }

            if (in_array($response->status(), [401, 403], true)) {
                throw ValidationException::withMessages([
                    'provider_api_key' => 'Invalid Delhivery API key. Please check and try again.',
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'delivery_provider' => 'Selected delivery API provider is invalid.',
        ]);
    }
}
