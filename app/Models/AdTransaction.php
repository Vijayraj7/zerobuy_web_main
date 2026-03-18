<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            if (empty($transaction->ad_transaction_id)) {
                $transaction->ad_transaction_id = self::generateAdTransactionCode();
            }
        });
    }

    public static function generateAdTransactionCode(): string
    {
        do {
            $code = 'TXN' . strtoupper(Str::random(10));
        } while (self::query()->where('ad_transaction_id', $code)->exists());

        return $code;
    }
}