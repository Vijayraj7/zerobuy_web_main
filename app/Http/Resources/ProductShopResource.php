<?php

namespace App\Http\Resources;

use App\Models\ShopFollower;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastOnline = $this->last_online >= now() ? true : false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'state' => $this->states->name,
            'district' => $this->districts->name,
            'is_followed' => (bool) $this->is_followed,
            'followers' => (int) ShopFollower::where('shop_id', $this->id)->count(),
            'total_products' => (int) $this->products()->isActive()->count(),
            'total_categories' => (int) $this->categories()->active()->count(),
            'rating' => (float) number_format($this->averageRating, 1, '.', ''),
            'total_reviews' => (int) $this->reviews->count(),
            // 'shop_status' => (string) $shopStatus,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'store_type' => $this->store_type ?? 'retail',
            'is_verified' => $this->is_verified == 1 ? true : false,
            'is_branded' => $this->is_branded == 1 ? true : false,
            'banners' => BannerResource::collection($this->banners()->where('status', 1)->get()),
            'estimated_delivery_time' => (string) ($this->estimated_delivery_time ?? '2-3 days'),
            'delivery_charge' => (float) getDeliveryCharge(1),
            'last_online' => $lastOnline
        ];
    }
}
