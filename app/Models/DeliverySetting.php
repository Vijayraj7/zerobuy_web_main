<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = [
        'shop_id',
        'delivery_mode',
        'update_when_shipped',
        'selected_state_ids',
    ];

    protected $casts = [
        'selected_state_ids' => 'array',
    ];

    public function amountRules()
    {
        return $this->hasMany(DeliveryAmountRule::class);
    }

    public function stateCharges()
    {
        return $this->hasMany(DeliveryStateCharge::class);
    }
}
