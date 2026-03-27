<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\ShopCreateRequest;
use App\Models\DeliveryAmountRule;
use App\Models\Shop;
use App\Models\State;
use App\Models\District;
use App\Models\DeliverySetting;
use App\Models\DeliveryStateCharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Shop::class;
    }

    /**
     * new shop creation by request.
     */
    public static function storeByRequest(ShopCreateRequest $request): Shop
    {
        // create new user
        $user = UserRepository::registerNewUser($request);
        // assign role
        $user->assignRole('shop');
        // create wallet
        WalletRepository::storeByRequest($user);
        // shop logo
        $thumbnail = MediaRepository::storeByRequest($request->shop_logo, 'shops/logo', 'image');
        // shop banner
        $banner = null;
        if ($request->hasFile('shop_banner')) {
            $banner = MediaRepository::storeByRequest($request->shop_banner, 'shops/banner', 'image');
        }

        // shop document upload
        $shopDocumentMedia = null;
        if ($request->hasFile('shop_document')) {
            $shopDocumentMedia = MediaRepository::storeByRequest($request->shop_document, 'shops/documents', 'document');
        }

        $state = State::find($request->state_id);
        $district = District::find($request->district_id);

        do {
            $shopCode = strtoupper(Str::random(10));
        } while (Shop::query()->where('shop_code', $shopCode)->exists());

        $onlinePaymentEnabled = $request->boolean('online_payment_enabled');
        $cashOnDeliveryEnabled = $request->has('cash_on_delivery_enabled')
            ? $request->boolean('cash_on_delivery_enabled')
            : true;
        $onlinePaymentProvider = $request->online_payment_provider ?: null;
        $onlinePaymentConfig = null;
        $adminWhatsappOrderEnabled = (bool) (generaleSetting()?->whatsapp_order_enabled ?? false);
        $shopWhatsappOrderEnabled = $adminWhatsappOrderEnabled
            ? (bool) $request->boolean('whatsapp_order_enabled')
            : false;

        if (! $onlinePaymentEnabled && ! $cashOnDeliveryEnabled) {
            throw ValidationException::withMessages([
                'cash_on_delivery_enabled' => __('Either Cash on Delivery or Online Payment must be enabled.'),
            ]);
        }

        if ($onlinePaymentProvider === 'razorpay') {
            $onlinePaymentConfig = [
                'razorpay' => [
                    'key_id' => $request->razorpay_key_id,
                    'key_secret' => $request->razorpay_key_secret,
                ],
            ];
        } elseif ($onlinePaymentProvider === 'cashfree') {
            $onlinePaymentConfig = [
                'cashfree' => [
                    'app_id' => $request->cashfree_app_id,
                    'secret_key' => $request->cashfree_secret_key,
                ],
            ];
        }

        // create new shop and return
        $shop = self::create([
            'shop_code' => $shopCode,
            'user_id' => $user->id,
            'name' => $request->shop_name,
            'logo_id' => $thumbnail ? $thumbnail->id : null,
            'banner_id' => $banner ? $banner->id : null,
            'delivery_charge' => $request->delivery_charge ?? 0,
            'address' => $request->address,
            'description' => $request->description,
            'status' => true,

            'store_type' => $request->store_type,
            'whatsapp_number' => $request->whatsapp_number,
            'state_id' => $state->id,
            'district_id' => $district->id,
            'state' => $state->name,
            'district' => $district->name,
            'pincode' => $request->pincode,
            'min_order_amount' => $request->min_order_amount,
            'gst' => $request->gst_number,
            'gst_number' => $request->gst_number,
            'store_since' => $request->store_since,
            'return_policy' => $request->return_policy,
            'phone_number' => $request->phone,
            'terms_condition_status' => 1,
            'estimated_delivery_time' => $request->delivery_days,
            'online_payment_enabled' => $onlinePaymentEnabled,
            'cash_on_delivery_enabled' => $cashOnDeliveryEnabled,
            'whatsapp_order_enabled' => $shopWhatsappOrderEnabled,
            'online_payment_provider' => $onlinePaymentProvider,
            'online_payment_config' => $onlinePaymentConfig,
            'shop_document' => $shopDocumentMedia ? $shopDocumentMedia->id : null,
        ]);

        $shop->businessCategories()->sync($request->bussiness_categories_id);



        if ($request->has('delivery_mode')) {

            DB::transaction(function () use ($request, $shop) {

                $existingSetting = DeliverySetting::where('shop_id', $shop->id)->first();

                $deliveryApiEnabled = $request->has('delivery_api_enabled')
                    ? $request->boolean('delivery_api_enabled')
                    : (bool) ($existingSetting?->delivery_api_enabled ?? false);

                $providerApiKey = $request->filled('provider_api_key')
                    ? $request->provider_api_key
                    : ($existingSetting?->provider_api_key);

                $providerApiSecret = $request->filled('provider_api_secret')
                    ? $request->provider_api_secret
                    : ($existingSetting?->provider_api_secret);

                $deliveryProvider = $request->filled('delivery_provider')
                    ? $request->delivery_provider
                    : ($existingSetting?->delivery_provider);

                $setting = DeliverySetting::updateOrCreate(
                    ['shop_id' => $shop->id],
                    [
                        'delivery_mode' => $request->delivery_mode,
                        'delivery_api_enabled' => $deliveryApiEnabled,
                        'delivery_provider' => $deliveryProvider ?: null,
                        'provider_api_key' => $providerApiKey ?: null,
                        'provider_api_secret' => $providerApiSecret ?: null,
                        'update_when_shipped' => $request->update_when_shipped ?? false,
                    ]
                );

                // Clean old data
                DeliveryAmountRule::where('delivery_setting_id', $setting->id)->delete();
                DeliveryStateCharge::where('delivery_setting_id', $setting->id)->delete();

                // Amount based
                if ($request->delivery_mode === 'amount_based') {
                    foreach ($request->amount_rules ?? [] as $rule) {
                        DeliveryAmountRule::create([
                            'delivery_setting_id' => $setting->id,
                            'min_amount' => $rule['min_amount'],
                            'max_amount' => $rule['max_amount'],
                            'charge'     => $rule['charge'],
                        ]);
                    }
                }

                // State wise
                if ($request->delivery_mode === 'state_wise') {
                    foreach ($request->state_charges ?? [] as $state) {
                        if ($state['charge'] != null) {
                            $stateModel = State::find($state['state']);
                            DeliveryStateCharge::create([
                                'delivery_setting_id' => $setting->id,
                                'state' => $stateModel->name,
                                'state_id' => $stateModel->id,
                                'charge' => $state['charge'],
                            ]);
                        }
                    }
                }
            });
        }

        $stateIds = array_map('intval', $request->delivery_state_ids);
        DeliverySetting::updateOrCreate(
            ['shop_id' => $shop->id],
            ['selected_state_ids' => $stateIds]
        );

        return $shop;
    }

    /**
     * update shop by request.
     */
    public static function updateByRequest($shop, $request): Shop
    {
        $state = State::find($request->state_id);
        $district = District::findOrFail($request->district_id);

        // Update linked shop user fields from the same form payload.
        if ($shop->user) {
            $profileMedia = $shop->user->media;
            if ($request->hasFile('profile_photo')) {
                if ($profileMedia) {
                    $profileMedia = MediaRepository::updateByRequest(
                        $request->profile_photo,
                        'users/profile',
                        'image',
                        $profileMedia
                    );
                } else {
                    $profileMedia = MediaRepository::storeByRequest(
                        $request->profile_photo,
                        'users/profile',
                        'image'
                    );
                }
            }

            $userPayload = [
                'name' => $request->first_name ?? $shop->user->name,
                'phone' => $request->phone ?? $shop->user->phone,
                'email' => $request->filled('email') ? $request->email : $shop->user->email,
                'media_id' => $profileMedia ? $profileMedia->id : $shop->user->media_id,
            ];

            if ($request->filled('password')) {
                $userPayload['password'] = Hash::make($request->password);
            }

            $shop->user->update($userPayload);
        }

        // Only update images if files uploaded
        $thumbnail = $request->hasFile('shop_logo') ? self::updateLogo($shop, $request) : $shop->mediaLogo;
        $banner = $request->hasFile('shop_banner') ? self::updateBanner($shop, $request) : $shop->mediaBanner;

        // Handle shop document update
        $shopDocumentMedia = null;
        if ($request->hasFile('shop_document')) {
            $shopDocumentMedia = MediaRepository::storeByRequest($request->shop_document, 'shops/documents', 'document');
        }

        $onlinePaymentEnabled = $request->boolean('online_payment_enabled');
        $cashOnDeliveryEnabled = $request->has('cash_on_delivery_enabled')
            ? $request->boolean('cash_on_delivery_enabled')
            : (bool) ($shop->cash_on_delivery_enabled ?? true);
        $onlinePaymentProvider = $request->online_payment_provider ?: ($shop->online_payment_provider ?: null);
        $onlinePaymentConfig = $shop->online_payment_config;
        $adminWhatsappOrderEnabled = (bool) (generaleSetting()?->whatsapp_order_enabled ?? false);
        $shopWhatsappOrderEnabled = $request->has('whatsapp_order_enabled')
            ? (bool) $request->boolean('whatsapp_order_enabled')
            : (bool) ($shop->whatsapp_order_enabled ?? false);

        if (! $adminWhatsappOrderEnabled) {
            $shopWhatsappOrderEnabled = false;
        }

        if (! $onlinePaymentEnabled && ! $cashOnDeliveryEnabled) {
            throw ValidationException::withMessages([
                'cash_on_delivery_enabled' => __('Either Cash on Delivery or Online Payment must be enabled.'),
            ]);
        }

        if ($onlinePaymentProvider === 'razorpay') {
            $existingRazorpay = data_get($onlinePaymentConfig, 'razorpay', []);

            $onlinePaymentConfig = [
                'razorpay' => [
                    'key_id' => $request->filled('razorpay_key_id')
                        ? $request->razorpay_key_id
                        : ($existingRazorpay['key_id'] ?? null),
                    'key_secret' => $request->filled('razorpay_key_secret')
                        ? $request->razorpay_key_secret
                        : ($existingRazorpay['key_secret'] ?? null),
                ],
            ];
        } elseif ($onlinePaymentProvider === 'cashfree') {
            $existingCashfree = data_get($onlinePaymentConfig, 'cashfree', []);

            $onlinePaymentConfig = [
                'cashfree' => [
                    'app_id' => $request->filled('cashfree_app_id')
                        ? $request->cashfree_app_id
                        : ($existingCashfree['app_id'] ?? null),
                    'secret_key' => $request->filled('cashfree_secret_key')
                        ? $request->cashfree_secret_key
                        : ($existingCashfree['secret_key'] ?? null),
                ],
            ];
        }

        if (! $onlinePaymentProvider) {
            $onlinePaymentConfig = $shop->online_payment_config;
        }

        if (! $onlinePaymentEnabled) {
            $onlinePaymentProvider = null;
            $onlinePaymentConfig = null;
        }

        // Update shop
        self::update($shop, [
            'name' => $request->shop_name,
            'logo_id' => $thumbnail?->id ?? $shop->logo_id,
            'banner_id' => $banner?->id ?? $shop->banner_id,
            'address' => $request->address,
            'description' => $request->description,
            'store_type' => $request->store_type,
            'phone_number' => $request->phone,
            'whatsapp_number' => $request->whatsapp_number,
            'state_id' => $state->id,
            'district_id' => $district->id,
            'state' => $state->name,
            'district' => $district->name,
            'pincode' => $request->pincode,
            'min_order_amount' => $request->min_order_amount,
            'gst' => $request->gst_number,
            'gst_number' => $request->gst_number,
            'store_since' => $request->store_since,
            'return_policy' => $request->return_policy,
            'terms_condition_status' => 1,
            'estimated_delivery_time' => $request->delivery_days,
            'online_payment_enabled' => $onlinePaymentEnabled,
            'cash_on_delivery_enabled' => $cashOnDeliveryEnabled,
            'whatsapp_order_enabled' => $shopWhatsappOrderEnabled,
            'online_payment_provider' => $onlinePaymentProvider,
            'online_payment_config' => $onlinePaymentConfig,
            'shop_document' => $shopDocumentMedia ? $shopDocumentMedia->id : $shop->shop_document,
        ]);

        $shop->businessCategories()->sync($request->bussiness_categories_id);


        if ($request->has('delivery_mode')) {

            DB::transaction(function () use ($request, $shop) {

                $existingSetting = DeliverySetting::where('shop_id', $shop->id)->first();

                $deliveryApiEnabled = $request->has('delivery_api_enabled')
                    ? $request->boolean('delivery_api_enabled')
                    : (bool) ($existingSetting?->delivery_api_enabled ?? false);

                $providerApiKey = $request->filled('provider_api_key')
                    ? $request->provider_api_key
                    : ($existingSetting?->provider_api_key);

                $providerApiSecret = $request->filled('provider_api_secret')
                    ? $request->provider_api_secret
                    : ($existingSetting?->provider_api_secret);

                $deliveryProvider = $request->filled('delivery_provider')
                    ? $request->delivery_provider
                    : ($existingSetting?->delivery_provider);

                $setting = DeliverySetting::updateOrCreate(
                    ['shop_id' => $shop->id],
                    [
                        'delivery_mode' => $request->delivery_mode,
                        'delivery_api_enabled' => $deliveryApiEnabled,
                        'delivery_provider' => $deliveryProvider ?: null,
                        'provider_api_key' => $providerApiKey ?: null,
                        'provider_api_secret' => $providerApiSecret ?: null,
                        'update_when_shipped' => $request->update_when_shipped ?? false,
                    ]
                );

                // Clean old data
                DeliveryAmountRule::where('delivery_setting_id', $setting->id)->delete();
                DeliveryStateCharge::where('delivery_setting_id', $setting->id)->delete();

                // Amount based
                if ($request->delivery_mode === 'amount_based') {
                    foreach ($request->amount_rules ?? [] as $rule) {
                        DeliveryAmountRule::create([
                            'delivery_setting_id' => $setting->id,
                            'min_amount' => $rule['min_amount'],
                            'max_amount' => $rule['max_amount'],
                            'charge'     => $rule['charge'],
                        ]);
                    }
                }

                // State wise
                if ($request->delivery_mode === 'state_wise') {
                    foreach ($request->state_charges ?? [] as $state) {
                        if ($state['charge'] != null) {
                            $stateModel = State::find($state['state']);
                            DeliveryStateCharge::create([
                                'delivery_setting_id' => $setting->id,
                                'state' => $stateModel->name,
                                'state_id' => $stateModel->id,
                                'charge' => $state['charge'],
                            ]);
                        }
                    }
                }
            });
        }


        $stateIds = array_map('intval', $request->delivery_state_ids);
        DeliverySetting::updateOrCreate(
            ['shop_id' => $shop->id],
            ['selected_state_ids' => $stateIds]
        );
        return $shop;
    }


    public static function updateShopSetting($shop, $request): Shop
    {
        $openTime = $request->opening_time ? Carbon::parse($request->opening_time)->format('H:i:s') : $shop->opening_time;
        $closeTime = $request->closing_time ? Carbon::parse($request->closing_time)->format('H:i:s') : $shop->closing_time;

        $onlinePaymentEnabled = $request->has('online_payment_enabled')
            ? $request->boolean('online_payment_enabled')
            : (bool) ($shop->online_payment_enabled ?? false);
        $cashOnDeliveryEnabled = $request->has('cash_on_delivery_enabled')
            ? $request->boolean('cash_on_delivery_enabled')
            : (bool) ($shop->cash_on_delivery_enabled ?? true);

        if (! $onlinePaymentEnabled && ! $cashOnDeliveryEnabled) {
            throw ValidationException::withMessages([
                'cash_on_delivery_enabled' => __('Either Cash on Delivery or Online Payment must be enabled.'),
            ]);
        }

        $onlinePaymentProvider = $request->filled('online_payment_provider')
            ? $request->online_payment_provider
            : ($shop->online_payment_provider ?: null);
        $onlinePaymentConfig = $shop->online_payment_config;
        $adminWhatsappOrderEnabled = (bool) (generaleSetting()?->whatsapp_order_enabled ?? false);
        $shopWhatsappOrderEnabled = $request->has('whatsapp_order_enabled')
            ? (bool) $request->boolean('whatsapp_order_enabled')
            : (bool) ($shop->whatsapp_order_enabled ?? false);

        if (! $adminWhatsappOrderEnabled) {
            $shopWhatsappOrderEnabled = false;
        }

        if ($onlinePaymentProvider === 'razorpay') {
            $existingRazorpay = data_get($onlinePaymentConfig, 'razorpay', []);

            $onlinePaymentConfig = [
                'razorpay' => [
                    'key_id' => $request->filled('razorpay_key_id')
                        ? $request->razorpay_key_id
                        : ($existingRazorpay['key_id'] ?? null),
                    'key_secret' => $request->filled('razorpay_key_secret')
                        ? $request->razorpay_key_secret
                        : ($existingRazorpay['key_secret'] ?? null),
                ],
            ];
        } elseif ($onlinePaymentProvider === 'cashfree') {
            $existingCashfree = data_get($onlinePaymentConfig, 'cashfree', []);

            $onlinePaymentConfig = [
                'cashfree' => [
                    'app_id' => $request->filled('cashfree_app_id')
                        ? $request->cashfree_app_id
                        : ($existingCashfree['app_id'] ?? null),
                    'secret_key' => $request->filled('cashfree_secret_key')
                        ? $request->cashfree_secret_key
                        : ($existingCashfree['secret_key'] ?? null),
                ],
            ];
        }

        if (! $onlinePaymentProvider) {
            $onlinePaymentConfig = $shop->online_payment_config;
        }

        if (! $onlinePaymentEnabled) {
            $onlinePaymentProvider = null;
            $onlinePaymentConfig = null;
        }

        // Handle shop document update
        $shopDocumentMedia = null;
        if ($request->hasFile('shop_document')) {
            $shopDocumentMedia = MediaRepository::storeByRequest($request->shop_document, 'shops/documents', 'document');
        }

        // update shop
        self::update($shop, [
            'delivery_charge' => $request->delivery_charge ?? 0,
            'min_order_amount' => $request->min_order_amount ?? $shop->min_order_amount,
            'prefix' => $request->prefix ?? $shop->prefix,
            'opening_time' => $openTime,
            'closing_time' => $closeTime,
            'estimated_delivery_time' => $request->estimated_delivery_time ?? $shop->estimated_delivery_time,
            'off_day' => $request->off_day ? array_map(function ($value) {
                return strtolower($value);
            }, $request->off_day) : null,
            'online_payment_enabled' => $onlinePaymentEnabled,
            'cash_on_delivery_enabled' => $cashOnDeliveryEnabled,
            'whatsapp_order_enabled' => $shopWhatsappOrderEnabled,
            'online_payment_provider' => $onlinePaymentProvider,
            'online_payment_config' => $onlinePaymentConfig,
            'shop_document' => $shopDocumentMedia ? $shopDocumentMedia->id : $shop->shop_document,
        ]);

        return $shop;
    }

    public static function updateShopInfo($shop, $request): Shop
    {
        // shop logo
        $thumbnail = self::updateLogo($shop, $request);

        // shop banner
        $banner = self::updateBanner($shop, $request);

        // Handle shop document update
        $shopDocumentMedia = null;
        if ($request->hasFile('shop_document')) {
            $shopDocumentMedia = MediaRepository::storeByRequest($request->shop_document, 'shops/documents', 'document');
        }

        // update shop
        self::update($shop, [
            'name' => $request->name,
            'logo_id' => $thumbnail ? $thumbnail->id : null,
            'banner_id' => $banner ? $banner->id : null,
            'address' => $request->address,
            'description' => $request->description,
            'shop_document' => $shopDocumentMedia ? $shopDocumentMedia->id : $shop->shop_document,
        ]);

        return $shop;
    }

    public static function updatePaymentSetting($shop, $request): Shop
    {
        $deliveryMode = DeliverySetting::where('shop_id', $shop->id)->value('delivery_mode');

        $onlinePaymentEnabled = $request->has('online_payment_enabled')
            ? $request->boolean('online_payment_enabled')
            : (bool) ($shop->online_payment_enabled ?? false);
        $cashOnDeliveryEnabled = $request->has('cash_on_delivery_enabled')
            ? $request->boolean('cash_on_delivery_enabled')
            : (bool) ($shop->cash_on_delivery_enabled ?? true);

        if (! $onlinePaymentEnabled && ! $cashOnDeliveryEnabled) {
            throw ValidationException::withMessages([
                'cash_on_delivery_enabled' => __('Either Cash on Delivery or Online Payment must be enabled.'),
            ]);
        }

        $onlinePaymentProvider = $request->filled('online_payment_provider')
            ? $request->online_payment_provider
            : ($shop->online_payment_provider ?: null);
        $onlinePaymentConfig = $shop->online_payment_config;

        if ($onlinePaymentProvider === 'razorpay') {
            $existingRazorpay = data_get($onlinePaymentConfig, 'razorpay', []);

            $onlinePaymentConfig = [
                'razorpay' => [
                    'key_id' => $request->filled('razorpay_key_id')
                        ? $request->razorpay_key_id
                        : ($existingRazorpay['key_id'] ?? null),
                    'key_secret' => $request->filled('razorpay_key_secret')
                        ? $request->razorpay_key_secret
                        : ($existingRazorpay['key_secret'] ?? null),
                ],
            ];
        } elseif ($onlinePaymentProvider === 'cashfree') {
            $existingCashfree = data_get($onlinePaymentConfig, 'cashfree', []);

            $onlinePaymentConfig = [
                'cashfree' => [
                    'app_id' => $request->filled('cashfree_app_id')
                        ? $request->cashfree_app_id
                        : ($existingCashfree['app_id'] ?? null),
                    'secret_key' => $request->filled('cashfree_secret_key')
                        ? $request->cashfree_secret_key
                        : ($existingCashfree['secret_key'] ?? null),
                ],
            ];
        }

        if (! $onlinePaymentProvider) {
            $onlinePaymentConfig = $shop->online_payment_config;
        }

        if (! $onlinePaymentEnabled) {
            $onlinePaymentProvider = null;
            $onlinePaymentConfig = null;
        }

        // Handle shop document update
        $shopDocumentMedia = null;
        if ($request->hasFile('shop_document')) {
            $shopDocumentMedia = MediaRepository::storeByRequest($request->shop_document, 'shops/documents', 'document');
        }

        self::update($shop, [
            'online_payment_enabled' => $onlinePaymentEnabled,
            'cash_on_delivery_enabled' => $cashOnDeliveryEnabled,
            'online_payment_provider' => $onlinePaymentProvider,
            'online_payment_config' => $onlinePaymentConfig,
            'shop_document' => $shopDocumentMedia ? $shopDocumentMedia->id : $shop->shop_document,
        ]);

        return $shop;
    }

    /**
     * Update or create a logo for the shop.
     */
    private static function updateLogo($shop, $request)
    {
        $thumbnail = $shop?->mediaLogo;
        // if logo and thumbnail is not null
        if ($request->hasFile('shop_logo')) {
            // update logo from mediaRepository
            $thumbnail = MediaRepository::updateByRequest(
                $request->shop_logo,
                'shops/logo',
                'image',
                $thumbnail
            );
        }

        return $thumbnail;
    }

    /**
     * Update or create a banner for the shop.
     */
    private static function updateBanner($shop, $request)
    {
        $thumbnail = $shop?->mediaBanner;
        // if banner and thumbnail is not null
        if ($request->hasFile('shop_banner')) {
            // update banner from mediaRepository
            $thumbnail = MediaRepository::updateByRequest(
                $request->shop_banner,
                'shops/banner',
                'image',
                $thumbnail
            );
        }

        return $thumbnail;
    }
}
