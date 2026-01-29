<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnProductImage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }
}
