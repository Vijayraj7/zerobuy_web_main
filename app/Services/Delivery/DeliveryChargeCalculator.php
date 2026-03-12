<?php

namespace App\Services\Delivery;

use App\Models\DeliverySetting;
use App\Models\DeliveryStateCharge;
use App\Services\Delivery\Providers\DeliveryProviderManager;

class DeliveryChargeCalculator
{
    public function __construct(
        protected DeliveryProviderManager $providerManager
    ) {
    }

    public function calculate(float $totalAmount, $shop, ?int $stateId): ?float
    {
        $deliveryCharge = 0.00;

        $setting = DeliverySetting::query()
            ->with('amountRules')
            ->where('shop_id', $shop?->id)
            ->first();

        if (!$setting) {
            return $deliveryCharge;
        }

        if ($setting->delivery_mode === 'state_wise') {
            if ($stateId !== null) {
                $stateDelivery = DeliveryStateCharge::query()
                    ->where('delivery_setting_id', $setting->id)
                    ->where('state_id', $stateId)
                    ->first();

                $deliveryCharge = $stateDelivery ? (float) $stateDelivery->charge : null;
            }
        } elseif ($setting->delivery_mode === 'manual') {
            $deliveryCharge = 0.00;
        } elseif ($setting->delivery_mode === 'amount_based') {
            if ($setting->amountRules->count() > 0) {
                $amountCharge = $setting->amountRules
                    ->where('min_amount', '<=', $totalAmount)
                    ->where('max_amount', '>=', $totalAmount)
                    ->first();

                if ($amountCharge) {
                    $deliveryCharge = (float) $amountCharge->charge;
                } else {
                    $lastRule = $setting->amountRules->sortByDesc('max_amount')->first();
                    $deliveryCharge = $lastRule ? (float) $lastRule->charge : 0.00;
                }
            }
        } elseif ($setting->delivery_mode === 'provider_api') {
            return 0;
            // $provider = $this->providerManager->resolve($setting->delivery_provider);
            // if (!$provider) {
            //     return null;
            // }

            // $providerCharge = $provider->getCharge($totalAmount, $shop, $stateId, $setting);
            // if ($providerCharge === null) {
            //     return null;
            // }

            // $deliveryCharge = $providerCharge;
        }

        if (!in_array((string) $stateId, array_map('strval', $setting->selected_state_ids ?? []), true)) {
            return null;
        }

        return $deliveryCharge;
    }
}
