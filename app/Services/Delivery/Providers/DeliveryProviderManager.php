<?php

namespace App\Services\Delivery\Providers;

use App\Services\Contracts\DeliveryRateProviderInterface;

class DeliveryProviderManager
{
    protected array $providers = [];

    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            if ($provider instanceof DeliveryRateProviderInterface) {
                $this->providers[$provider->getKey()] = $provider;
            }
        }
    }

    public function resolve(?string $providerKey): ?DeliveryRateProviderInterface
    {
        if (!$providerKey) {
            return null;
        }

        return $this->providers[$providerKey] ?? null;
    }
}
