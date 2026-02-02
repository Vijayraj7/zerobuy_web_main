<?php

namespace App\Http\Resources;

use App\Models\ShopFollower;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ShopDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentTime = Carbon::now();

        $openingTime = $this->opening_time;
        $closingTime = $this->closing_time;

        // Parse opening and closing times using Carbon
        $openingTime = Carbon::parse($openingTime)->format('H:i');
        $closingTime = Carbon::parse($closingTime)->format('H:i');

        $shopStatus = 'Offline';
        // Check if the current time is between opening and closing times
        if ($currentTime->between($openingTime, $closingTime)) {
            $shopStatus = 'Online';
        }
        
        // Check if user is logged in and is a customer
        $isFollowed = false;
        $user = Auth::guard('api')->user();
        
        if ($user && $user->customer) {
            $isFollowed = ShopFollower::where([
                'shop_id' => $this->id,
                'customer_id' => $user->customer->id,
            ])->exists();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'state' => $this->states ? $this->states->name : null,
            'state_id' => $this->states ? (int)$this->states->id : null,
            'store_type' => $this->store_type,
            'phone' => $this->phone_number,
            'whatsapp' => $this->whatsapp_number,
            'min_amount' => $this->min_order_amount,
            'store_since' => $this->store_since,
            'return_policy' => $this->return_policy,
            'address' => $this->address,
            'pincode' => $this->pincode,
            'district' => $this->districts ? $this->districts->name : null,
            'district_id' => $this->districts ? (int)$this->districts->id : null,
            'is_followed' => (bool) $isFollowed,
            'followers' => (int) ShopFollower::where('shop_id', $this->id)->count(),
            'total_products' => (int) $this->products()->isActive()->count(),
            'total_categories' => (int) $this->categories()->active()->count(),
            'rating' => (float) number_format($this->averageRating, 1, '.', ''),
            'total_reviews' => (int) $this->reviews->count(),
            'shop_status' => (string) $shopStatus,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'is_verified' => $this->is_verified == 1 ? true : false,
            'is_branded' => $this->is_branded == 1 ? true : false,
            'banners' => BannerResource::collection($this->banners()->where('status', 1)->get()),
            'certificate_thumbnail' => $this->certificates()->active()->first()?->thumbnail ?? null,
            'estimated_delivery_time' => (string) ($this->estimated_delivery_time ?? '2-3 days'),
            'delivery_charge' => (float) getDeliveryCharge(1),
        ];
    }
}
