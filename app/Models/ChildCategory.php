<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ChildCategory extends Model
{
    protected $fillable = [
        'business_category_id',
        'category_id',
        'sub_category_id',
        'media_id',
        'name',
        'slug',
        'status'
    ];

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(function () {
            if ($this->media && Storage::exists($this->media->src)) {
                return Storage::url($this->media->src);
            }
            return asset('default/default.jpg');
        });
    }

    // Scope for sorting
    public function scopeSortByField($query, $sortBy, $sortOrder)
    {
        $allowedSortFields = [
            'id',
            'child_name',
            'business_name',
            'category_name',
            'sub_category_name',
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }

        if ($sortBy === 'business_name') {
            $query->join('business_categories', 'child_categories.business_category_id', '=', 'business_categories.id')
                  ->select('child_categories.*')
                  ->orderBy('business_categories.name', $sortOrder);
        } elseif ($sortBy === 'category_name') {
            $query->join('categories', 'child_categories.category_id', '=', 'categories.id')
                  ->select('child_categories.*')
                  ->orderBy('categories.name', $sortOrder);
        } elseif ($sortBy === 'sub_category_name') {
            $query->join('sub_categories', 'child_categories.sub_category_id', '=', 'sub_categories.id')
                  ->select('child_categories.*')
                  ->orderBy('sub_categories.name', $sortOrder);
        } elseif ($sortBy === 'child_name') {
            $query->orderBy('child_categories.name', $sortOrder);
        } else {
            $query->orderBy('child_categories.id', $sortOrder);
        }

        return $query;
    }
}
