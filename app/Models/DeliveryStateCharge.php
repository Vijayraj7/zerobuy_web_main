<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStateCharge extends Model
{
    protected $fillable = [
        'delivery_setting_id',
        'state_id',
        'state',
        'charge',
    ];
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
