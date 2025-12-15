<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductItemDetail extends Model
{
    use HasFactory; 
    protected $table = 'product_item_details';
    protected $fillable = [
        'product_id',
        'item_name',
        'item_value',
    ];
}