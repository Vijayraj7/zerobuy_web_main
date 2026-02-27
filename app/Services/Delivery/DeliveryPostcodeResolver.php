<?php

namespace App\Services\Delivery;

use App\Models\Address;

class DeliveryPostcodeResolver
{
    public function resolve(?int $stateId): ?string
    {
        $postCode = trim((string) request()->input('post_code', request()->input('pincode', '')));

        if ($postCode !== '') {
            return $postCode;
        }

        $addressId = request()->input('address_id');
        if ($addressId) {
            $address = Address::query()->find($addressId);
            if ($address && !empty($address->post_code)) {
                $addressPostCode = trim((string) ($address->post_code ?? ''));
                if ($addressPostCode !== '') {
                    return $addressPostCode;
                }
            }
        }

        // if ($stateId) {
        //     $latestAddressInState = Address::query()
        //         ->where('state_id', $stateId)
        //         ->whereNotNull('post_code')
        //         ->latest('id')
        //         ->first();

        //     if ($latestAddressInState && !empty($latestAddressInState->post_code)) {
        //         return trim((string) $latestAddressInState->post_code);
        //     }
        // }

        return null;
    }
}
