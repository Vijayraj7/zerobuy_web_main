<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisementSetting extends Model
{
    use HasFactory;

    protected $fillable = ['daily_budget'];
}