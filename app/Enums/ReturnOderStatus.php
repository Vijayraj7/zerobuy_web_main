<?php

namespace App\Enums;

enum ReturnOderStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case COMPLETED = 'Completed';
    case DAMAGED = 'Damaged';
    case MISMATCH = 'Mismatch';
    case REJECTED = 'Rejected';
}
