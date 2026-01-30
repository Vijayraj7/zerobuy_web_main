<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function returnProduct()
    {
        return $this->hasmany(ReturnOrderDetail::class);
    }

    public function returnProductImages()
    {
        return $this->hasMany(ReturnProductImage::class);
    }

    public function statusTimelines(): HasMany
    {
        return $this->hasMany(ReturnOrderStatusTimeline::class);
    }
}
