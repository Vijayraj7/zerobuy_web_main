<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\StateModel;

class StateModelRepository extends Repository
{
    public static function model()
    {
        return StateModel::class;    
    }
}