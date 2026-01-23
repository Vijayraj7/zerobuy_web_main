<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class PromotionalNotification extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $fillable = [
        'send_to',
        'business_category_id',
        'notification_option_type',
        'notification_option_link',
        'product_id',
        'store_id',
        'shop_id',
        'media_id',
        'message',
        'last_sent_at',
    ];

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

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    } 

    public function optionName()
    {
        if (!$this->notification_option_type || !$this->notification_option_link) {
            return null;
        }

        return match ($this->notification_option_type) {

            'sub_category' => SubCategory::where('id', $this->notification_option_link)->value('name'),

            'child_category' => ChildCategory::where('id', $this->notification_option_link)->value('name'),

            'product' => Product::where('id', $this->notification_option_link)->value('name'),

            'shop' => Shop::where('id', $this->notification_option_link)->value('name'),

            default => null,
        };
    }


}