<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'Pending';
    case CONFIRM = 'Confirm';
    // case PROCESSING = 'Processing';
    // case PICKUP = 'Pickup';
    case SHIPPED = 'Shipped';
    case DELIVERED = 'Delivered';
    case CANCELLED = 'Cancelled';
    case CANCELLED_BY_CUSTOMER = 'User Cancelled';
}
