<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeVisibleForShop($query, int $shopId)
    {
        return $query->where(function ($q) use ($shopId) {
            $q->where('is_one_time_purchase', false)
                ->orWhereDoesntHave('subscriptions', function ($subQuery) use ($shopId) {
                    $subQuery->where('shop_id', $shopId);
                });
        });
    }

    public function canBePurchasedByShop(int $shopId): bool
    {
        if (! $this->is_one_time_purchase) {
            return true;
        }

        return ! $this->subscriptions()->where('shop_id', $shopId)->exists();
    }
}
