<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBulkPrice extends Model
{
    use HasFactory;
    protected $fillable = ['product_id','min_qty','max_qty','price'];
}