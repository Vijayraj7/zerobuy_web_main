<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = [
        'shop_id',
        'delivery_mode',
        'delivery_provider',
        'provider_api_key',
        'provider_api_secret',
        'update_when_shipped',
        'selected_state_ids',
    ];

    protected $casts = [
        'selected_state_ids' => 'array',
        'provider_api_key' => 'encrypted',
        'provider_api_secret' => 'encrypted',
    ];

    public function amountRules()
    {
        return $this->hasMany(DeliveryAmountRule::class, 'delivery_setting_id');
    }

    public function stateCharges()
    {
        return $this->hasMany(DeliveryStateCharge::class);
    }
}
