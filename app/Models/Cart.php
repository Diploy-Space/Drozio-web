<?php

namespace App\Models;

use App\Enums\BusinessModule;
use App\Enums\UserType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id Primary
 * @property int $customer_id
 * @property string $cart_group_id
 * @property int $product_id
 * @property string $product_type
 * @property string $digital_product_type
 * @property string $color
 * @property array $choices
 * @property array $variations
 * @property array $variant
 * @property int $quantity
 * @property float $price
 * @property float $tax
 * @property int $is_checked
 * @property float $discount
 * @property string $tax_model
 * @property string $slug
 * @property string $name
 * @property string $thumbnail
 * @property int $seller_id
 * @property string $seller_is
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $shop_info
 * @property float $shipping_cost
 * @property string $shipping_type
 * @property int $is_guest
 */
class Cart extends Model
{
    protected $casts = [
        'id' => 'integer',
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
        'tax' => 'float',
        'is_checked' => 'integer',
        'discount' => 'float',
        'seller_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shipping_cost' => 'float',
        'is_guest' => 'integer',
    ];

    protected $fillable = [
        'customer_id',
        'cart_group_id',
        'product_id',
        'product_type',
        'digital_product_type',
        'color',
        'choices',
        'variations',
        'variant',
        'quantity',
        'price',
        'tax',
        'discount',
        'tax_model',
        'is_checked',
        'slug',
        'name',
        'thumbnail',
        'seller_id',
        'seller_is',
        'shop_info',
        'shipping_cost',
        'shipping_type',
        'is_guest',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function cartShipping(): HasOne
    {
        return $this->hasOne(CartShipping::class, 'cart_group_id', 'cart_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->where('status', 1);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function hasVerifiedShopBadge(): bool
    {
        if ($this->seller_is === UserType::ADMIN) {
            return isInHouseShopVerified();
        }

        return (bool) $this->seller?->hasVerifiedBadge();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'seller_id', 'seller_id');
    }

    public function allProducts(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
