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
}
