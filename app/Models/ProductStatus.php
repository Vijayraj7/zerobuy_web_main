<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'shop_id',
        'product_id',
        'message',
        'is_active',
        'started_at',
        'expired_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active'  => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expired_at && $this->expired_at->lte(now());
    } 
}