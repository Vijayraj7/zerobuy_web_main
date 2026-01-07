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

        $state = State::find($request->state_id);
        $district = District::find($request->district_id);

        // create new shop and return
        $shop = self::create([
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
        ]);

        $shop->businessCategories()->sync($request->bussiness_categories_id);

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

        // Update user only if fields provided
        if ($request->filled(['first_name', 'email', 'password'])) {
            UserRepository::updateByRequest($request, $shop->user);
        }
        UserRepository::updateByRequest($request, $shop->user);

        // Only update images if files uploaded
        $thumbnail = $request->hasFile('shop_logo') ? self::updateLogo($shop, $request) : $shop->logo;
        $banner = $request->hasFile('shop_banner') ? self::updateBanner($shop, $request) : $shop->banner;

        // Update shop
        self::update($shop, [
            'name' => $request->shop_name,
            'logo_id' => $thumbnail?->id ?? $shop->logo_id,
            'banner_id' => $banner?->id ?? $shop->banner_id,
            'address' => $request->address,
            'description' => $request->description,
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
            'terms_condition_status' => 1,
            'estimated_delivery_time' => $request->delivery_days,
        ]);

        $shop->businessCategories()->sync($request->bussiness_categories_id);


        DB::transaction(function () use ($request, $shop) {

            $setting = DeliverySetting::updateOrCreate(
                ['shop_id' => $shop->id],
                [
                    'delivery_mode' => $request->delivery_mode,
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
                        // dd($stateModel);
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

        ]);

        return $shop;
    }

    public static function updateShopInfo($shop, $request): Shop
    {
        // shop logo
        $thumbnail = self::updateLogo($shop, $request);

        // shop banner
        $banner = self::updateBanner($shop, $request);

        // update shop
        self::update($shop, [
            'name' => $request->name,
            'logo_id' => $thumbnail ? $thumbnail->id : null,
            'banner_id' => $banner ? $banner->id : null,
            'address' => $request->address,
            'description' => $request->description,
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
