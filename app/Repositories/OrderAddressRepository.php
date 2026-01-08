<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\OrderAddress;

class OrderAddressRepository extends Repository
{
    public static function model()
    {
        return OrderAddress::class;    
    }
}