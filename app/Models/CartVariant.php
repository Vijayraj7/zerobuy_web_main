<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartVariant extends Model
{
    protected $fillable = [
        'cart_id',
        'product_variants_id',
        'quantity',
        'price',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variants_id');
    }
}
