<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\DeliverySetting;

class DeliverySettingRepository extends Repository
{
    public static function model()
    {
        return DeliverySetting::class;    
    }
}