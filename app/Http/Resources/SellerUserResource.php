<?php

namespace App\Http\Resources;

use App\Models\Certificate;
use App\Models\ShopFollower;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shop = generaleSetting('shop', $this);
        $openingTime = $shop->opening_time ? Carbon::parse($shop->opening_time)->format('H:i') : null;
        $closingTime = $shop->closing_time ? Carbon::parse($shop->closing_time)->format('H:i') : null;
        $shopStatus = $shop?->isOnline() ? 'Online' : 'Offline';

        $offDay = $shop->off_day ?? [];
        $offDay = ! empty($offDay) ? (is_array($offDay) ? $offDay : json_decode($offDay)) : [];

        return [
            'id' => $this->id,
            'first_name' => $this->name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'profile_photo' => $this->thumbnail,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'is_active' => (bool) $this->is_active,
            'shop_status' => (string) $shopStatus,
            'shop' => [
                'id' => $shop->id,
                'state' => $shop->states ? $shop->states->name : null,
                'state_id' => $shop->states ? (int)$shop->states->id : null,
                'store_type' => $shop->store_type,
                'phone' => $shop->phone_number,
                'whatsapp' => $shop->whatsapp_number,
                'min_amount' => $shop->min_order_amount,
                'store_since' => $shop->store_since,
                'return_policy' => $shop->return_policy,
                'address' => $shop->address,
                'pincode' => $shop->pincode,
                'district' => $shop->districts ? $shop->districts->name : null,
                'district_id' => $shop->districts ? (int)$shop->districts->id : null,
                'name' => $shop->name,
                'logo' => $shop->logo,
                'banner' => $shop->banner,
                // 'state' => $shop->state,
                'district' => $shop->district,
                // 'address' => $shop->address,
                'gst' => $shop->gst_number,
                'open_time' => $openingTime,
                'close_time' => $closingTime,
                'off_day' => $offDay,
                'certificate' => $shop->certificates()->active()->first()?->thumbnail,
                'prefix' => $shop->prefix ?? 'ORD-',
                'cash_on_delivery_enabled' => (bool) ($shop->cash_on_delivery_enabled ?? true),
                'online_payment_enabled' => (bool) ($shop->online_payment_enabled ?? false),
                'online_payment_provider' => $shop->online_payment_provider,
                'razorpay_key_id' => data_get($shop->online_payment_config, 'razorpay.key_id'),
                'razorpay_key_secret' => data_get($shop->online_payment_config, 'razorpay.key_secret'),
                'cashfree_app_id' => data_get($shop->online_payment_config, 'cashfree.app_id'),
                'cashfree_secret_key' => data_get($shop->online_payment_config, 'cashfree.secret_key'),
                'followers' => (int) ShopFollower::where('shop_id', $shop->id)->count(),
                'estimated_delivery_time' => $shop->estimated_delivery_time,
                'min_order_amount' => (float) $shop->min_order_amount ?? 0,
                'shop_status' => $shopStatus,
                'last_online' => $shop->last_online,
                'total_products' => (int) $shop->products->count(),
                'total_categories' => (int) $shop->categories->count(),
                'rating' => (float) number_format($shop->averageRating, 1, '.', ''),
                'total_reviews' => (int) $shop->reviews->count(),
                'description' => $shop->description,
                'is_verified' => $shop->is_verified == 1 ? true : false,
                'is_branded' => $shop->is_branded == 1 ? true : false,
            ],
            'banners' => BannerResource::collection($shop->banners),
        ];
    }
}
