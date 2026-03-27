<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GeneraleSetting;
use App\Models\VerifyManage;
use Illuminate\Support\Facades\Cache;

class VerifyManageApiController extends Controller
{
    public function show()
    {
        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });
        $generaleSetting = Cache::rememberForever('generale_setting', function () {
            return GeneraleSetting::first();
        });

        $phoneMinLength = $verifyManage?->phone_min_length > 0 ? $verifyManage?->phone_min_length : 9;
        $phoneMaxLength = $verifyManage?->phone_max_length > 0 ? $verifyManage?->phone_max_length : 16;

        return $this->json('Verify manage', [
            'register_otp_verify' => (bool) ($verifyManage?->register_otp ?? false),
            'register_otp_type' => $verifyManage?->register_otp_type ?? 'email',
            'forgot_otp_type' => $verifyManage?->forgot_otp_type ?? 'email',
            'order_place_account_verify' => (bool) ($verifyManage?->order_place_account_verify ?? false),
            'phone_required' => (bool) ($verifyManage?->phone_required ?? true),
            'phone_min_length' => (int) $phoneMinLength,
            'phone_max_length' => (int) $phoneMaxLength,
            'user_android_min_build' => (int) ($generaleSetting?->user_android_min_build ?? 1),
            'seller_android_min_build' => (int) ($generaleSetting?->seller_android_min_build ?? 1),
            'google_playstore_url' => $generaleSetting?->google_playstore_url,
            'app_store_url' => $generaleSetting?->app_store_url,
            'app_versions' => [
                'user' => [
                    'android_min_build' => (int) ($generaleSetting?->user_android_min_build ?? 1),
                    'android_store_url' => $generaleSetting?->google_playstore_url,
                ],
                'seller' => [
                    'android_min_build' => (int) ($generaleSetting?->seller_android_min_build ?? 1),
                    'android_store_url' => $generaleSetting?->google_playstore_url,
                ],
            ],
        ]);
    }
}
