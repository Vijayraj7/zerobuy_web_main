<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 */
class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'ads_type',
        'product_id',
        'start_date',
        'end_date',
        'daily_budget',
        'total_budget',
        'total_views',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    public function scopeByStatusName($query, $statusName)
    {
        $now = Carbon::now();

        return $query->where(function ($q) use ($now, $statusName) {
            if ($statusName === 'scheduled') {
                $q->where('start_date', '>', $now);
            } elseif ($statusName === 'completed') {
                $q->where('end_date', '<', $now);
            } elseif ($statusName === 'active') {
                $q->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
            }
        });
    }

    public function getStatusName()
    {
        $now = Carbon::now();

        if ($this->start_date && $this->start_date->gt($now)) {
            return 'scheduled';
        }

        if ($this->end_date && $this->end_date->lt($now)) {
            return 'completed';
        }

        return 'active';
    }
}
