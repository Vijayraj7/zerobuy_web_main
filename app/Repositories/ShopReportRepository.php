<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\ShopReport;

class ShopReportRepository extends Repository
{
    public static function model()
    {
        return ShopReport::class;    
    }
}