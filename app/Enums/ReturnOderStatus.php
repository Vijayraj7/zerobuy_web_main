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
}
