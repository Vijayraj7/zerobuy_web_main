<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'phone',
        'customer_id',
        'address_type',
        'area',
        'road_no',
        'flat_no',
        'house_no',
        'address_line',
        'address_line2',
        'post_code',
        'latitude',
        'longitude',
        'is_default',
        'state',
        'state_id',
        'district_id',
    ];
}
