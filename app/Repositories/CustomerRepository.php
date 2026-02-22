<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\Customer;
use App\Models\User;

class CustomerRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Customer::class;
    }

    /**
     * Store customer by request.
     *
     * @param  User  $user  The user object
     */
    public static function storeByRequest(User $user, ?int $businessCategoryId = null): Customer
    {
        return self::create([
            'user_id' => $user->id,
            'selected_business_category_id' => $businessCategoryId,
        ]);
    }

    public static function updateSelectedBusinessCategory(User $user, ?int $businessCategoryId): void
    {
        if (! $user->customer) {
            return;
        }

        $user->customer()->update([
            'selected_business_category_id' => $businessCategoryId,
        ]);
    }
}
