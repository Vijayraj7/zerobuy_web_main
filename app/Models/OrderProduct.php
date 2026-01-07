<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function orderVariant(): BelongsTo
    {
        return $this->belongsTo(OrderVariant::class, 'order_variants_id');
    }

    public function orderBulkItem(): BelongsTo
    {
        return $this->belongsTo(OrderBulkItem::class, 'order_bulk_items_id');
    }
}
