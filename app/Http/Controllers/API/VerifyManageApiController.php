<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VerifyManage;
use Illuminate\Support\Facades\Cache;

class VerifyManageApiController extends Controller
{
    public function show()
    {
        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
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
        ]);
    }
}
