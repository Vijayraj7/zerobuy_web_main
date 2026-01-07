<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\OrderBulkItem;

class OrderBulkItemRepository extends Repository
{
    public static function model()
    {
        return OrderBulkItem::class;    
    }
}