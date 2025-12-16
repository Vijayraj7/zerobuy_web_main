<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBulkItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'name',
        'quantity',
        'moq',
        'mrp',
        'selling_price'
    ];
}