<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryAmountRule extends Model
{
    protected $fillable = [
        'delivery_setting_id',
        'min_amount',
        'max_amount',
        'charge',
    ];
}
