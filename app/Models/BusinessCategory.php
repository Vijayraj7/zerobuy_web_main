<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BusinessCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'status', 'media_id'];

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'business_category_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function thumbnail(): Attribute
    {
        $thumbnail = asset('default/default.jpg');
        if ($this->media && Storage::exists($this->media->src)) {
            $thumbnail = Storage::url($this->media->src);
        }

        return Attribute::make(
            get: fn () => $thumbnail
        );
    }
    // public function shops(): BelongsToMany
    // {
    //     return $this->belongsToMany(Shop::class, 'shop_categories');
    // }
    // public function products(): BelongsToMany
    // {
    //     return $this->belongsToMany(Product::class, 'product_business_categories');
    // }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_business_category');
    }
}
