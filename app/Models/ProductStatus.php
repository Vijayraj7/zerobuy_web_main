<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'expired_at',
        'views',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active'  => 'boolean',
        'views' => 'integer',
    ];

    /**
     * Get the shop that owns the product status.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the product that owns the product status.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            });
    }

    /**
     * Check if the status is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_at && $this->expired_at->lte(now());
    }

    /**
     * Check if the status is currently active and not expired.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && !$this->is_expired;
    }
}