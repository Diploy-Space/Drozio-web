<?php

namespace App\Models;

use App\Enums\BusinessModule;
use App\Enums\UserType;
use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\TaxModule\app\Models\Taxable;

/**
 * @property string $business_module
 * @property int $user_id
 * @property int $shop_id
 * @property string $added_by
 * @property string $name
 * @property string $code
 * @property string $slug
 * @property int $category_id
 * @property int $sub_category_id
 * @property int $sub_sub_category_id
 * @property int $brand_id
 * @property string $unit
 * @property string $digital_product_type
 * @property string $product_type
 * @property string $details
 * @property float $tax
 * @property string $tax_type
 * @property string $tax_model
 * @property float $unit_price
 * @property int $status
 * @property string $business_module
 * @property float $discount
 * @property int $current_stock
 * @property int $minimum_order_qty
 * @property int $request_status
 * @property int $featured_status
 * @property int $featured
 * @property int $flash_deal
 * @property int $seller_id
 * @property float $purchase_price
 * @property string $denied_note
 * @property float $shipping_cost
 * @property int $multiply_qty
 * @property float $temp_shipping_cost
 * @property string $thumbnail
 * @property string $thumbnail_storage_type
 * @property string $preview_file
 * @property string $preview_file_storage_type
 * @property string $digital_file_ready
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_image
 * @property int $is_shipping_cost_updated
 */
class Product extends Model
{
    use CacheManagerTrait, StorageTrait;

    protected $fillable = [
        'business_module',
        'user_id',
        'shop_id',
        'added_by',
        'name',
        'code',
        'slug',
        'category_ids',
        'category_id',
        'sub_category_id',
        'sub_sub_category_id',
        'brand_id',
        'unit',
        'product_type',
        'details',
        'colors',
        'choice_options',
        'unit_price',
        'tax',
        'tax_type',
        'tax_model',
        'discount',
        'discount_type',
        'attributes',
        'current_stock',
        'minimum_order_qty',
        'video_provider',
        'video_url',
        'video_file',
        'video_file_storage_type',
        'status',
        'featured_status',
        'request_status',
        'denied_note',
        'shipping_cost',
        'multiply_qty',
        'images',
        'thumbnail',
        'thumbnail_storage_type',
        'meta_title',
        'meta_description',
        'meta_image',
        'is_shipping_cost_updated',
        'temp_shipping_cost',
        'business_module',
    ];

    /** @var string[] */
    protected $casts = [
        'business_module' => 'string',
        'user_id' => 'integer',
        'shop_id' => 'integer',
        'added_by' => 'string',
        'name' => 'string',
        'code' => 'string',
        'slug' => 'string',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'sub_sub_category_id' => 'integer',
        'brand_id' => 'integer',
        'unit' => 'string',
        'product_type' => 'string',
        'details' => 'string',
        'tax' => 'float',
        'tax_type' => 'string',
        'tax_model' => 'string',
        'unit_price' => 'float',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'order_count' => 'integer',
        'status' => 'integer',
        'discount' => 'float',
        'current_stock' => 'integer',
        'minimum_order_qty' => 'integer',
        'request_status' => 'integer',
        'featured_status' => 'integer',
        'seller_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'denied_note' => 'string',
        'shipping_cost' => 'float',
        'multiply_qty' => 'integer',
        'temp_shipping_cost' => 'float',
        'thumbnail' => 'string',
        'meta_title' => 'string',
        'meta_description' => 'string',
        'meta_image' => 'string',
        'is_shipping_cost_updated' => 'integer',
        'thumbnail_storage_type' => 'string',
    ];

    protected $appends = ['is_shop_temporary_close', 'thumbnail_full_url', 'preview_file_full_url', 'color_images_full_url', 'meta_image_full_url', 'images_full_url', 'digital_file_ready_full_url'];

    protected $hidden = ['rating_avg', 'rating_count', 'order_count', 'retailDetail'];

    protected $with = ['retailDetail'];

    private const RETAIL_ATTRIBUTES = [
        'digital_product_type',
        'digital_file_ready',
        'digital_file_ready_storage_type',
        'digital_product_file_types',
        'digital_product_extensions',
        'preview_file',
        'preview_file_storage_type',
        'featured',
        'flash_deal',
        'color_image',
        'variation',
        'purchase_price',
    ];

    private const RETAIL_ATTRIBUTES_AFTER = 'code';

    private const RETAIL_BRAND_VISIBILITY_SETTING = 'product_brand';

    private const WHOLESALE_BRAND_VISIBILITY_SETTING = 'wholesale_show_brand';

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public static function isRetailBrandVisible(): bool
    {
        return (bool) getWebConfig(name: self::RETAIL_BRAND_VISIBILITY_SETTING);
    }

    public static function isWholesaleBrandVisible(): bool
    {
        return (bool) (getWebConfig(name: self::WHOLESALE_BRAND_VISIBILITY_SETTING) ?? self::isRetailBrandVisible());
    }

    public function scopeActive($query)
    {
        $retailBrandSetting = self::isRetailBrandVisible();
        $wholesaleBrandSetting = self::isWholesaleBrandVisible();
        $digitalProductSetting = getWebConfig(name: 'digital_product');
        $businessMode = getWebConfig(name: 'business_mode');
        $productType = $digitalProductSetting ? ['digital', 'physical'] : ['physical'];
        $businessModuleColumn = $query->qualifyColumn('business_module');

        return $query->when($businessMode == 'single', function ($query) {
            $query->where(['added_by' => 'admin']);
        })
            ->when(
                $retailBrandSetting === $wholesaleBrandSetting,
                fn ($query) => $this->applyBrandVisibility(query: $query, brandSetting: $retailBrandSetting, digitalProductSetting: $digitalProductSetting, productType: $productType),
                fn ($query) => $query->where(fn ($modules) => $modules
                    ->where(fn ($wholesale) => $this->applyBrandVisibility(
                        query: $wholesale->where($businessModuleColumn, BusinessModule::WHOLESALE),
                        brandSetting: $wholesaleBrandSetting,
                        digitalProductSetting: $digitalProductSetting,
                        productType: $productType,
                    ))
                    ->orWhere(fn ($otherModules) => $this->applyBrandVisibility(
                        query: $otherModules->where($businessModuleColumn, '!=', BusinessModule::WHOLESALE),
                        brandSetting: $retailBrandSetting,
                        digitalProductSetting: $digitalProductSetting,
                        productType: $productType,
                    ))),
            )
            ->where(['status' => 1])
            ->where(['request_status' => 1])
            ->SellerApproved()
            ->whereIn('product_type', $productType);
    }

    private function applyBrandVisibility(Builder $query, bool $brandSetting, mixed $digitalProductSetting, array $productType): Builder
    {
        return $query->when($brandSetting && ! in_array('digital', $productType), function ($query) {
            $query->where(function ($query) {
                $query->whereHas('brand', function ($query) {
                    $query->where('status', 1);
                })->orWhere(function ($query) {
                    $query->whereNull('brand_id')->where('status', 1);
                });
            });
        })
            ->when($brandSetting && $digitalProductSetting, function ($query) {
                $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where(['product_type' => 'digital', 'status' => 1]);
                    })->orWhere(function ($query) {
                        $query->where(['product_type' => 'physical', 'status' => 1])->where(function ($query2) {
                            $query2->whereNull('brand_id')->orWhere(function ($query3) {
                                $query3->whereNotNull('brand_id')->whereHas('brand', function ($brandQuery) {
                                    $brandQuery->where('status', 1);
                                });
                            });
                        });
                    });
                });
            })
            ->when(! $brandSetting, function ($query) {
                $query->whereNull('brand_id')->where('status', 1);
            });
    }

    public function scopeSellerApproved($query): void
    {
        $query->whereHas('seller', function ($query) {
            $query->where(['status' => 'approved']);
        })->orWhere(function ($query) {
            $query->where(['added_by' => 'admin', 'status' => 1]);
        });
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function clearanceSale(): HasOne
    {
        return $this->hasOne(StockClearanceProduct::class, 'product_id');
    }

    public function retailDetail(): HasOne
    {
        return $this->hasOne(ProductRetail::class, 'product_id');
    }

    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        $retail = [];
        foreach (self::RETAIL_ATTRIBUTES as $attribute) {
            $retail[$attribute] = $this->retailAttribute($attribute);
        }

        $position = array_search(self::RETAIL_ATTRIBUTES_AFTER, array_keys($attributes), true);

        if ($position === false) {
            return $attributes + $retail;
        }

        return array_slice($attributes, 0, $position + 1, true)
            + $retail
            + array_slice($attributes, $position + 1, null, true);
    }

    public function getAttribute($key): mixed
    {
        if (in_array($key, self::RETAIL_ATTRIBUTES, true)) {
            return $this->retailAttribute($key);
        }

        return parent::getAttribute($key);
    }

    private function retailAttribute(string $key): mixed
    {
        $value = array_key_exists($key, $this->attributes)
            ? $this->attributes[$key]
            : $this->retailDetail?->{$key};

        return match ($key) {
            'featured', 'flash_deal' => $value === null ? null : (int) $value,
            'color_image' => $value ?? '',
            'purchase_price' => is_array($value) ? $value : (float) ($value ?? 0),
            default => $value,
        };
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function reviewsByCustomer(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id')->where('customer_id', auth('customer')->id())->whereNotNull('product_id')->whereNull('delivery_man_id');
    }

    public function digitalProductAuthors(): HasMany
    {
        return $this->hasMany(DigitalProductAuthor::class, 'product_id');
    }

    public function digitalProductPublishingHouse(): HasMany
    {
        return $this->hasMany(DigitalProductPublishingHouse::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function refundRequest(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'product_id', 'id');
    }

    public function scopeStatus($query): Builder
    {
        return $query->where('featured_status', 1);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'seller_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'user_id');
    }

    public function hasVerifiedShopBadge(): bool
    {
        if ($this->added_by === UserType::ADMIN) {
            return isInHouseShopVerified();
        }

        return $this->added_by === UserType::SELLER && (bool) $this->seller?->hasVerifiedBadge();
    }

    public function getIsShopTemporaryCloseAttribute($value): int
    {
        $inHouseTemporaryClose = Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS) ?? 0;
        if ($this->added_by == 'admin') {
            return $inHouseTemporaryClose ?? 0;
        } elseif ($this->added_by == 'seller') {
            return Cache::remember('product-shop-close-'.$this->id, 3600, function () {
                return $this?->seller?->shop?->temporary_close ?? 0;
            });
        }

        return 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function subSubCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_sub_category_id');
    }

    public function rating(): HasMany
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->whereNull('delivery_man_id')
            ->groupBy('product_id');
    }

    protected array $shapedCardCache = [];

    public function getOverallRatingAttribute(): array
    {
        if (isset($this->shapedCardCache['overall_rating'])) {
            return $this->shapedCardCache['overall_rating'];
        }

        if ($this->relationLoaded('reviews')) {
            return $this->shapedCardCache['overall_rating'] = getOverallRating($this->reviews);
        }

        $count = $this->total_reviews;
        $average = $this->reviews_avg_rating ?? ($count ? $this->reviews()->avg('rating') : 0);

        return $this->shapedCardCache['overall_rating'] = [$count ? number_format((float) $average, 2) : 0, $count];
    }

    public function getTotalReviewsAttribute(): int
    {
        return $this->shapedCardCache['total_reviews'] ??= (int) ($this->relationLoaded('reviews')
            ? $this->reviews->count()
            : ($this->reviews_count ?? $this->reviews()->count()));
    }

    public function getDiscountValueAttribute()
    {
        return $this->shapedCardCache['discount_value'] ??= getProductPriceByType(product: $this, type: 'discount', result: 'value');
    }

    public function getDiscountLabelAttribute()
    {
        return $this->shapedCardCache['discount_label'] ??= getProductPriceByType(product: $this, type: 'discount', result: 'string');
    }

    public function getDiscountedUnitPriceLabelAttribute()
    {
        return $this->shapedCardCache['discounted_unit_price_label'] ??= getProductPriceByType(product: $this, type: 'discounted_unit_price', result: 'string');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }

    public function seoInfo(): BelongsTo
    {
        return $this->belongsTo(ProductSeo::class, 'id', 'product_id');
    }

    public function seoMeta(): HasOne
    {
        return $this->hasOne(ProductSeo::class, 'product_id');
    }

    public function orderDelivered(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'product_id')
            ->where('delivery_status', 'delivered');

    }

    public function wishList(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    public function digitalVariation(): HasMany
    {
        return $this->hasMany(DigitalProductVariation::class, 'product_id');
    }

    public function tags(): BelongsToMany
    {
        if (strpos(url()->current(), '/api')) {
            return $this->belongsToMany(Tag::class)->limit(5);
        }

        return $this->belongsToMany(Tag::class);
    }

    public function taxVats(): MorphMany
    {
        return $this->morphMany(Taxable::class, 'taxable')
            ->where('tax_for', 'tax_for_orders');
    }

    public function flashDealProducts(): HasMany
    {
        return $this->hasMany(FlashDealProduct::class);
    }

    public function scopeFlashDeal($query, $flashDealID)
    {
        return $query->whereHas('flashDealProducts.flashDeal', function ($query) use ($flashDealID) {
            return $query->where('id', $flashDealID);
        });
    }

    public function scopeFeaturedDeal($query, $featuredDealID)
    {
        return $query->whereHas('flashDealProducts.featureDeal', function ($query) use ($featuredDealID) {
            return $query->where('id', $featuredDealID);
        });
    }

    public function compareList(): HasMany
    {
        return $this->hasMany(ProductCompare::class);
    }

    public function getNameAttribute($name): ?string
    {
        $segment = request()->segment(1);
        if (in_array($segment, ['admin', 'vendor', 'seller'], true)) {
            return $name;
        }

        return $this->relationLoaded('translations') ? ($this->translations[0]->value ?? $name) : $name;
    }

    public function getDetailsAttribute($detail): ?string
    {
        $segment = request()->segment(1);
        if (in_array($segment, ['admin', 'vendor', 'seller'], true)) {
            return $detail;
        }

        return $this->relationLoaded('translations') ? ($this->translations[1]->value ?? $detail) : $detail;
    }

    public function getThumbnailFullUrlAttribute(): string|null|array
    {
        $value = $this->thumbnail;

        return $this->storageLink('product/thumbnail', $value, $this->thumbnail_storage_type ?? 'public');
    }

    public function getPreviewFileFullUrlAttribute(): string|null|array
    {
        $value = $this->retailAttribute('preview_file');

        return $this->storageLink('product/preview', $value, $this->retailAttribute('preview_file_storage_type') ?? 'public');
    }

    public function getMetaImageFullUrlAttribute(): array
    {
        $value = $this->meta_image;

        return $this->storageLink('product/meta', $value, 'public');
    }

    public function getDigitalFileReadyFullUrlAttribute(): array
    {
        $value = $this->retailAttribute('digital_file_ready');

        return $this->storageLink('product/digital-product', $value, $this->retailAttribute('digital_file_ready_storage_type') ?? 'public');
    }

    public function getColorImagesFullUrlAttribute(): array
    {
        $images = [];
        $colorImage = $this->retailAttribute('color_image');
        $value = is_array($colorImage) ? $colorImage : json_decode($colorImage);
        if ($value) {
            foreach ($value as $item) {
                $item = (array) $item;
                $images[] = [
                    'color' => $item['color'],
                    'image_name' => $this->storageLink('product', $item['image_name'], $item['storage'] ?? 'public'),
                ];
            }
        }

        return $images;
    }

    public function getImagesFullUrlAttribute(): array
    {
        $images = [];
        $value = is_array($this->images) ? $this->images : json_decode($this->images);
        if ($value) {
            foreach ($value as $item) {
                $item = isset($item->image_name) ? (array) $item : ['image_name' => $item, 'storage' => 'public'];
                $images[] = $this->storageLink('product', $item['image_name'], $item['storage'] ?? 'public');
            }
        }

        return $images;
    }

    public function getVideoThumbnailUrlAttribute(): ?string
    {
        $youtubeVideoIdPattern = '/(?:youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|live\/|v\/|watch\?(?:.*&)?v=)|youtu\.be\/)([\w-]{11})/';

        if (! is_string($this->video_url) || ! preg_match($youtubeVideoIdPattern, $this->video_url, $matches)) {
            return null;
        }

        return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'products');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'products');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }, 'reviews' => function ($query) {
                $segment = request()->segment(1);
                $query->whereNull('delivery_man_id')->when(! in_array($segment, ['admin', 'vendor', 'seller'], true), function ($query) {
                    return $query->active();
                });
            }]);
        });
    }
}
