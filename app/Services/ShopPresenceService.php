<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;

class ShopPresenceService
{
    public static function markOnline(?User $user): void
    {
        $shop = $user?->shop;
        if (! $shop) {
            return;
        }

        $shop->update([
            'last_online' => now(),
        ]);
    }

    public static function touch(?User $user): void
    {
        $shop = $user?->shop;
        if (! $shop) {
            return;
        }

        $lastOnline = $shop->last_online ? Carbon::parse($shop->last_online) : null;

        if (! $lastOnline || $lastOnline->lt(now()->subMinute())) {
            $shop->update([
                'last_online' => now(),
            ]);
        }
    }

    public static function markOffline(?User $user): void
    {
        $shop = $user?->shop;
        if (! $shop) {
            return;
        }

        $shop->update([
            'last_online' => null,
        ]);
    }

    public static function isOnline(?Shop $shop, int $minutes = 5): bool
    {
        if (! $shop?->last_online) {
            return false;
        }

        return Carbon::parse($shop->last_online)->gt(now()->subMinutes($minutes));
    }
}
