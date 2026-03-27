<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopCreateRequest;
use App\Http\Requests\ShopPasswordResetRequest;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shop;
use App\Models\State;
use App\Models\Page;
use App\Repositories\ShopRepository;
use Illuminate\Support\Facades\Hash;
use App\Models\BusinessCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Enums\OrderStatus;
use App\Models\DeliverySetting;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFollower;
use Carbon\Carbon;
use DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api;

class ShopController extends Controller
{
    public function create()
    {
        $states = State::orderBy('name')->get();

        $sellerTerms = Page::where('slug', 'seller-terms-of-service')->where('is_active', 1)->first();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', [
            'states' => $states,
            'businessCategories' => $businessCategories,
            'sellerTerms' => $sellerTerms,
            'formAction' => route('shop.shop.store'),
        ]);
    }

    public function store(ShopCreateRequest $request)
    {
        if ($request->terms_condition_status != 1) {
            return response()->json(['status' => 'terms_required']);
        }

        $this->validateDeliveryProviderCredentials($request);
        $this->validatePaymentProviderCredentials($request);

        ShopRepository::storeByRequest($request);
        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop created successfully',
            'redirect' => route('shop.profile.index')
        ]);
    }

    public function update(Request $request, Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update the shop in demo mode');
        }
        $this->validateDeliveryProviderCredentials($request, $shop);
        $this->validatePaymentProviderCredentials($request, $shop);

        // dd($request->all());
        ShopRepository::updateByRequest($shop, $request);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop updated successfully',
            'redirect' => route('shop.profile.index')
        ]);
    }

    public function validatePaymentProvider(Request $request)
    {
        $shop = null;
        $shopId = $request->input('shop_id');
        if ($shopId !== null && $shopId !== '') {
            $shop = Shop::find($shopId);
        }

        $this->validatePaymentProviderCredentials($request, $shop);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment credentials verified successfully.',
        ]);
    }

    public function validateDeliveryProvider(Request $request)
    {
        $shop = null;
        $shopId = $request->input('shop_id');
        if ($shopId !== null && $shopId !== '') {
            $shop = Shop::find($shopId);
        }

        $this->validateDeliveryProviderCredentials($request, $shop);

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery API credentials verified successfully.',
        ]);
    }

    private function validateDeliveryProviderCredentials(Request $request, ?Shop $shop = null): void
    {
        if (!$request->boolean('delivery_api_enabled')) {
            return;
        }

        $provider = strtolower(trim((string) $request->input('delivery_provider', '')));
        $apiKey = trim((string) $request->input('provider_api_key', ''));
        $apiSecret = trim((string) $request->input('provider_api_secret', ''));

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

            $baseUrl = 'https://apiv2.shiprocket.in/v1/external';

            try {
                $authResponse = Http::timeout(20)->acceptJson()->post($baseUrl . '/auth/login', [
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
                $errorMessage = (string) (
                    $authResponse->json('message')
                    ?? $authResponse->json('error')
                    ?? 'Invalid Shiprocket credentials. Please check API key and secret.'
                );

                throw ValidationException::withMessages([
                    'provider_api_key' => $errorMessage,
                ]);
            }

            return;
        }

        if ($provider === 'delhivery') {
            $delhiveryBaseUrl = rtrim((string) (data_get(config('services'), 'delhivery.base_url', 'https://track.delhivery.com') ?: 'https://track.delhivery.com'), '/');
            $originPin = trim((string) ($request->input('pincode') ?: ($shop?->pincode ?? '110001')));

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

    private function validatePaymentProviderCredentials(Request $request, ?Shop $shop = null): void
    {
        $onlinePaymentEnabled = $request->has('online_payment_enabled')
            ? $request->boolean('online_payment_enabled')
            : (bool) ($shop?->online_payment_enabled ?? false);

        if (! $onlinePaymentEnabled) {
            return;
        }

        $provider = strtolower(trim((string) ($request->input('online_payment_provider') ?: ($shop?->online_payment_provider ?? ''))));
        $existingConfig = is_array($shop?->online_payment_config) ? $shop->online_payment_config : [];

        if ($provider === '') {
            throw ValidationException::withMessages([
                'online_payment_provider' => 'Please select an online payment provider.',
            ]);
        }

        if ($provider === 'razorpay') {
            $keyId = trim((string) ($request->input('razorpay_key_id') ?: data_get($existingConfig, 'razorpay.key_id', '')));
            $keySecret = trim((string) ($request->input('razorpay_key_secret') ?: data_get($existingConfig, 'razorpay.key_secret', '')));

            if ($keyId === '') {
                throw ValidationException::withMessages([
                    'razorpay_key_id' => 'The Razorpay key ID field is required when Razorpay is selected.',
                ]);
            }

            if ($keySecret === '') {
                throw ValidationException::withMessages([
                    'razorpay_key_secret' => 'The Razorpay key secret field is required when Razorpay is selected.',
                ]);
            }

            try {
                $api = new Api($keyId, $keySecret);
                $api->payment->all(['count' => 1]);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'razorpay_key_id' => 'Invalid Razorpay credentials. Please check key ID and key secret.',
                ]);
            }

            return;
        }

        if ($provider === 'cashfree') {
            $appId = trim((string) ($request->input('cashfree_app_id') ?: data_get($existingConfig, 'cashfree.app_id', '')));
            $secretKey = trim((string) ($request->input('cashfree_secret_key') ?: data_get($existingConfig, 'cashfree.secret_key', '')));

            if ($appId === '') {
                throw ValidationException::withMessages([
                    'cashfree_app_id' => 'The Cashfree app ID field is required when Cashfree is selected.',
                ]);
            }

            if ($secretKey === '') {
                throw ValidationException::withMessages([
                    'cashfree_secret_key' => 'The Cashfree secret key field is required when Cashfree is selected.',
                ]);
            }

            $isSandbox = str_starts_with(strtoupper($appId), 'TEST');
            $baseUrl = $isSandbox ? 'https://sandbox.cashfree.com/pg' : 'https://api.cashfree.com/pg';

            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders([
                        'x-client-id' => $appId,
                        'x-client-secret' => $secretKey,
                        'x-api-version' => '2023-08-01',
                    ])
                    ->get($baseUrl . '/orders', [
                        'limit' => 1,
                    ]);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'cashfree_app_id' => 'Unable to verify Cashfree credentials right now. Please try again.',
                ]);
            }

            if (in_array($response->status(), [401, 403], true)) {
                throw ValidationException::withMessages([
                    'cashfree_app_id' => 'Invalid Cashfree credentials. Please check app ID and secret key.',
                ]);
            }

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'cashfree_app_id' => 'Unable to verify Cashfree credentials right now. Please try again.',
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'online_payment_provider' => 'Selected online payment provider is invalid.',
        ]);
    }
}
