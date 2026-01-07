<?php

namespace App\Enums;

enum ReturnOderStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case COMPLETED = 'Completed';
    case REJECTED = 'Rejected';
    case CANCELLED = 'Cancelled';

    case DAMAGED = 'Damaged';
    case MISMATCH = 'Mismatch';


    public static function visibleStatuses(): array
    {
        return array_filter(self::cases(), function ($status) {
            return !in_array($status, [
                self::DAMAGED,
                self::MISMATCH
            ]);
        });
    }

}
