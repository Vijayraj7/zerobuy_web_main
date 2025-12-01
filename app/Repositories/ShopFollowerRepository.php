<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\ShopFollower;

class ShopFollowerRepository extends Repository
{
    public static function model()
    {
        return ShopFollower::class;    
    }
}