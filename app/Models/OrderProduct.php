<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderProduct extends Pivot
{
    protected $table = 'order_products';

    protected $guarded = [];

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function shopOrder(): HasOne
    {
        return $this->hasOne(ShopOrder::class, 'shop_order_id');
    }
}
