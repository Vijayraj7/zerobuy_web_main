<?php

namespace App\Services\Contracts;

use App\Models\DeliverySetting;

interface DeliveryRateProviderInterface
{
    public function getKey(): string;

    public function getCharge(float $totalAmount, $shop, ?int $stateId, DeliverySetting $setting): ?float;
}
