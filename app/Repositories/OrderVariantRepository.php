<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\OrderVariant;

class OrderVariantRepository extends Repository
{
    public static function model()
    {
        return OrderVariant::class;    
    }
}