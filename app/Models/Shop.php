<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Shop extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_online' => 'datetime',
        'online_payment_enabled' => 'boolean',
        'cash_on_delivery_enabled' => 'boolean',
    ];

    /**
     * Store payment config encrypted and return it as decrypted array.
     */
    public function onlinePaymentConfig(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                if (is_array($value)) {
                    $payload = $value['__enc'] ?? null;
                    if (is_string($payload) && $payload !== '') {
                        try {
                            $decrypted = Crypt::decryptString($payload);
                            $decoded = json_decode($decrypted, true);

                            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                        } catch (Throwable) {
                            return null;
                        }
                    }

                    return $value;
                }

                if (! is_string($value)) {
                    return null;
                }

                try {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $payload = $decoded['__enc'] ?? null;
                        if (is_string($payload) && $payload !== '') {
                            $decrypted = Crypt::decryptString($payload);
                            $innerDecoded = json_decode($decrypted, true);

                            return json_last_error() === JSON_ERROR_NONE ? $innerDecoded : null;
                        }

                        // Backward compatibility for older plain JSON records.
                        return $decoded;
                    }

                    // Last fallback for previously stored raw encrypted strings.
                    $decrypted = Crypt::decryptString($value);
                    $decoded = json_decode($decrypted, true);

                    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                } catch (Throwable) {
                    return null;
                }
            },
            set: function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                if (is_array($value) || is_object($value)) {
                    $json = json_encode($value);

                    if ($json === false) {
                        return null;
                    }

                    return json_encode([
                        '__enc' => Crypt::encryptString($json),
                    ]);
                }

                if (! is_string($value)) {
                    return null;
                }

                $trimmed = trim($value);
                if ($trimmed === '') {
                    return null;
                }

                json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return json_encode([
                        '__enc' => Crypt::encryptString($trimmed),
                    ]);
                }

                return json_encode([
                    '__enc' => Crypt::encryptString(json_encode($trimmed)),
                ]);
            }
        );
    }

    /**
     * Get the shop user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class);
    }

    /**
     * get emploees for this shop
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'shop_id');
    }

    /**
     * get withdraw model for this user.
     */
    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class, 'shop_id');
    }

    /**
     * Get the logo media for the Shop.
     */
    public function mediaLogo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    /**
     * Retrieve the media banner for this instance.
     */
    public function mediaBanner(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_id');
    }

    public function documentMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'shop_document');
    }

    /**
     * get all gallery images for this shop
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class, 'shop_id');
    }

    /**
     * Get the logo for the Shop as an attribute.
     */
    public function logo(): Attribute
    {
        $logo = asset('default/default.jpg');
        if ($this->mediaLogo && Storage::exists($this->mediaLogo->src)) {
            $logo = Storage::url($this->mediaLogo->src);
        }

        return Attribute::make(
            get: fn() => $logo
        );
    }

    /**
     * Get the banner for the Shop as an attribute.
     */
    public function banner(): Attribute
    {
        $banner = asset('default/default.jpg');
        if ($this->mediaBanner && Storage::exists($this->mediaBanner->src)) {
            $banner = Storage::url($this->mediaBanner->src);
        }

        return Attribute::make(
            get: fn() => $banner
        );
    }

        public function document(): Attribute
    {
        $document = asset('default/default.jpg');
        if ($this->documentMedia && Storage::exists($this->documentMedia->src)) {
            $document = Storage::url($this->documentMedia->src);
        }

        return Attribute::make(
            get: fn() => $document
        );
    }

    /**
     * Get all of the products for the Shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Retrieve the categories associated with the shop.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_categories');
    }

    /**
     * Retrieve the sub categories associated with the shop.
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    /**
     * get all of the brands for the shop.
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Get all of the coupons for the Shop.
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get all of the colors for the Shop.
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }

    /**
     * Get the sizes for the shop.
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class, 'shop_id');
    }

    /**
     * Get all of the units for the Shop.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get all of the orders for the Shop.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the banners for the Shop.
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'shop_id');
    }

    /**
     * Scope a query to only include active shops.
     *
     * @param  Builder  $builder  The query builder
     * @return mixed
     */
    public function scopeIsActive(Builder $builder)
    {
        return $builder->whereHas('user', function ($query) {
            $query->where('is_active', 1);
        })->whereHas('currentSubscription');
    }

    /**
     * Get all of the reviews for the Shop.
     *
     * @return HasMany.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'shop_id');
    }

    public function currentSubscription()
    {
        return $this->hasOne(ShopSubscription::class)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('remaining_sales')
                    ->orWhere('remaining_sales', '>', 0);
            })
            ->latest();
    }

    public function isOnline(int $minutes = 5): bool
    {
        if (! $this->last_online) {
            return false;
        }

        return Carbon::parse($this->last_online)->gt(now()->subMinutes($minutes));
    }

    /**
     * Calculates the average rating of the reviews.
     *
     * @return Attribute The average rating attribute.
     */
    public function averageRating(): Attribute
    {
        $avgRating = $this->reviews()->avg('rating');

        return new Attribute(
            get: fn() => (float) number_format($avgRating > 0 ? $avgRating : 5, 1, '.', ''),
        );
    }

    public function returnOrders(): HasMany
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function followers()
    {
        return $this->hasMany(ShopFollower::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function states()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function districts()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function businessCategories()
    {
        return $this->belongsToMany(BusinessCategory::class, 'shop_business_category');
    }

    public function deliverySetting()
    {
        return $this->hasOne(DeliverySetting::class, 'shop_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id');
    }

    public function adwallet()
    {
        return $this->hasOne(AdWallet::class, 'user_id', 'user_id');
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    /**
     * Get all of the certificates for the Shop.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
