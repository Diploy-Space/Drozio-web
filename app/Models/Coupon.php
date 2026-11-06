<?php

namespace App\Models;

use App\Enums\BusinessModule;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $added_by
 * @property string $business_module
 * @property string $coupon_type
 * @property string $coupon_bearer
 * @property int $seller_id
 * @property int $customer_id
 * @property string $title
 * @property string $code
 * @property DateTime $start_date
 * @property DateTime $expire_date
 * @property float $min_purchase
 * @property float $max_discount
 * @property float $discount
 * @property string $discount_type
 * @property int $limit
 */
class Coupon extends Model
{
    protected $fillable = [
        'added_by',
        'business_module',
        'status',
        'coupon_type',
        'coupon_bearer',
        'customer_id',
        'seller_id',
        'title',
        'code',
        'start_date',
        'expire_date',
        'min_purchase',
        'max_discount',
        'discount',
        'discount_type',
        'limit',
    ];

    protected $casts = [
        'id' => 'integer',
        'added_by' => 'string',
        'business_module' => 'string',
        'coupon_type' => 'string',
        'coupon_bearer' => 'string',
        'seller_id' => 'integer',
        'customer_id' => 'integer',
        'title' => 'string',
        'code' => 'string',
        'start_date' => 'datetime',
        'expire_date' => 'datetime',
        'min_purchase' => 'float',
        'max_discount' => 'float',
        'discount' => 'float',
        'discount_type' => 'string',
        'limit' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $businessMode = getWebConfig(name: 'business_mode');

        return $query->when($businessMode == 'single', function ($query) {
            $query->where(['added_by' => 'admin']);
        })->where(['status' => 1]);
    }

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function order(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_code', 'code');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function hasVerifiedShopBadge(): bool
    {
        if ($this->seller_id === null) {
            return isInHouseShopVerified();
        }

        return (bool) $this->seller?->hasVerifiedBadge();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'shops');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'shops');
        });
    }
}
