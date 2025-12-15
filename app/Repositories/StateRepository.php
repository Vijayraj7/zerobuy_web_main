<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\State;

class StateRepository extends Repository
{
    public static function model()
    {
        return State::class;    
    }
}