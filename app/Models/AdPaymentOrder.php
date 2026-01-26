<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdPaymentOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adWallet(): BelongsTo
    {
        return $this->belongsTo(AdWallet::class);
    }
}