<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\AdTransaction;

class AdTransactionRepository extends Repository
{
    public static function model()
    {
        return AdTransaction::class;    
    }
}