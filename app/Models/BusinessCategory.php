<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class BusinessCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }
    public function categories()
    {
        return $this->hasMany(Category::class, 'business_category_id');
    }
    // public function shops(): BelongsToMany
    // {
    //     return $this->belongsToMany(Shop::class, 'shop_categories');
    // }
    // public function products(): BelongsToMany
    // {
    //     return $this->belongsToMany(Product::class, 'product_business_categories');
    // }
}
