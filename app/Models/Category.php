<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $appends = ['thumbnail'];

    public function translations(): HasMany
    {
        return $this->hasMany(TranslateUtility::class);
    }

    /**
     * Retrieves the products associated with this instance.
     *
     * @return BelongsToMany The products associated with this instance.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'category_id')->where('is_active', 1);
    }

    // added by ancy
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }
    // added by ancy
    // public function subCategories()
    // {
    //     return $this->hasMany(SubCategory::class, 'category_id');
    // }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_categories');
    }

    /**
     * Scopes a query to only include active records.
     *
     * @param  mixed  $query  The query parameter.
     * @return mixed The return value.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Retrieves the associated media for this model.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Generates a thumbnail attribute for the media.
     *
     * @return Attribute The generated thumbnail attribute.
     */
    public function thumbnail(): Attribute
    {
        $thumbnail = asset('default/default.jpg');
        if ($this->media && Storage::exists($this->media->src)) {
            $thumbnail = Storage::url($this->media->src);
        }

        return Attribute::make(
            get: fn() => $thumbnail
        );
    }

    public function scopeSortByField($query, $sortBy, $sortOrder)
    {
        $allowedSortFields = [
            'id',
            'category_name',
            'business_name',
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }

        if ($sortBy === 'business_name') {
            $query->join('business_categories', 'categories.business_category_id', '=', 'business_categories.id')
                ->select('categories.*')
                ->orderBy('business_categories.name', $sortOrder);
        } elseif ($sortBy === 'category_name') {
            $query->orderBy('categories.name', $sortOrder);
        } else {
            $query->orderBy('categories.id', $sortOrder);
        }

        return $query;
    }
}
