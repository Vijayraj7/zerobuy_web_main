<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentSettingUpdateRequest;
use App\Http\Requests\ShopInfoUpdateRequest;
use App\Http\Requests\ShopSettingUpdateRequest;
use App\Http\Requests\ShopUserUpdateRequest;
use App\Http\Resources\SellerUserResource;
use App\Http\Resources\ShopDetailsResource;
use App\Repositories\ShopRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api;

class UserController extends Controller
{
    /**
     * show user details.
     */
    public function show()
    {
        $user = auth()->user();

        return $this->json('user details', [
            'user' => SellerUserResource::make($user),
        ]);
    }

    public function getShop()
    {
        // $user = auth()->user();
        $shop = generaleSetting('shop');

        return $this->json('shop details', [
            'shop' => ShopDetailsResource::make($shop),
        ]);
    }


    /**
     * update user profile.
     */
    public function updateProfile(ShopUserUpdateRequest $request)
    {
        // update shop user
        UserRepository::updateByRequest($request, auth()->user());

        return $this->json('Profile is updated successfully', [
            'user' => SellerUserResource::make(auth()->user()),
        ]);
    }

    /**
     * update shop info.
     */
    public function shopUpdate(ShopInfoUpdateRequest $request)
    {
        /** @var Shop $shop */
        $shop = generaleSetting('shop');

        // update shop user
        ShopRepository::updateShopInfo($shop, $request);

        return $this->json('shop info is updated successfully', [
            'user' => SellerUserResource::make(auth()->user()),
        ]);
    }

    /**
     * update shop info.
     */
    public function shopSettingUpdate(ShopSettingUpdateRequest $request)
    {
        /** @var App\Models\Shop $shop */
        $shop = generaleSetting('shop');

        // update shop user
        ShopRepository::updateShopSetting($shop, $request);

        return $this->json('shop setting is updated successfully', [
            'user' => SellerUserResource::make(auth()->user()),
        ]);
    }

    /**
     * update payment setting.
     */
    public function validatePaymentSetting(PaymentSettingUpdateRequest $request)
    {
        /** @var App\Models\Shop $shop */
        $shop = generaleSetting('shop');

        $this->validatePaymentProviderCredentials($request, $shop);

        return $this->json('Payment credentials verified successfully');
    }

    /**
     * update payment setting.
     */
    public function paymentSettingUpdate(PaymentSettingUpdateRequest $request)
    {
        /** @var App\Models\Shop $shop */
        $shop = generaleSetting('shop');

        $this->validatePaymentProviderCredentials($request, $shop);

        ShopRepository::updatePaymentSetting($shop, $request);

        return $this->json('payment setting is updated successfully', [
            'user' => SellerUserResource::make(auth()->user()),
        ]);
    }

    private function validatePaymentProviderCredentials(PaymentSettingUpdateRequest $request, $shop): void
    {
        $onlinePaymentEnabled = $request->has('online_payment_enabled')
            ? $request->boolean('online_payment_enabled')
            : (bool) ($shop->online_payment_enabled ?? false);

        if (! $onlinePaymentEnabled) {
            return;
        }

        $provider = trim((string) ($request->input('online_payment_provider') ?: ($shop->online_payment_provider ?: '')));
        $provider = strtolower($provider);
        $existingConfig = is_array($shop->online_payment_config) ? $shop->online_payment_config : [];

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

    /**
     * update profile.
     */
    public function update(ShopInfoUpdateRequest $request)
    {
        /** @var Shop $shop */
        $shop = generaleSetting('shop');

        // update shop
        $shop = ShopRepository::updateByRequest($shop, $request);

        return $this->json('Profile is updated successfully', [
            'user' => SellerUserResource::make($shop->user),
        ]);
    }
}
