<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class UserPresenceService
{
    public static function markOnline(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->update([
            'last_online' => now(),
        ]);
    }

    public static function touch(?User $user): void
    {
        if (! $user) {
            return;
        }

        $lastOnline = $user->last_online ? Carbon::parse($user->last_online) : null;

        if (! $lastOnline || $lastOnline->lt(now()->subMinute())) {
            $user->update([
                'last_online' => now(),
            ]);
        }
    }

    public static function markOffline(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->update([
            'last_online' => null,
        ]);
    }
}
