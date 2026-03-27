<?php

namespace App\Http\Resources;

use App\Models\ShopFollower;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class ShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shopStatus = $this->isOnline() ? 'Online' : 'Offline';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'contact_number' => $this->phone_number,
            'whatsapp_number' => $this->whatsapp_number,
            'whatsapp_order_enabled' => (bool) ($this->whatsapp_order_enabled ?? false),
            'admin_whatsapp_order_enabled' => (bool) (generaleSetting()?->whatsapp_order_enabled ?? false),
            'store_type' => $this->store_type ? $this->store_type : 'retail',
            'state' => $this->states ? $this->states->name : null,
            'district' => $this->districts ? $this->districts->name : null,
            'total_followers' => (int) ShopFollower::where('shop_id', $this->id)->count(),
            'total_products' => (int) $this->products()->isActive()->count(),
            'total_categories' => (int) $this->categories()->active()->count(),
            'rating' => (float) number_format($this->averageRating, 1, '.', ''),
            'shop_status' => (string) $shopStatus,
            'total_reviews' => (string) Number::abbreviate($this->reviews->count(), maxPrecision: 2),
            'return_policy' => $this->return_policy,
        ];
    }
}
