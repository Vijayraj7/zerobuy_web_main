<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\AdPaymentOrder;

class AdPaymentOrderRepository extends Repository
{
    public static function model()
    {
        return AdPaymentOrder::class;    
    }
}