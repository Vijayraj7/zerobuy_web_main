<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\DeliveryStateCharge;

class DeliveryStateChargeRepository extends Repository
{
    public static function model()
    {
        return DeliveryStateCharge::class;    
    }
}