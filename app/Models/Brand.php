<?php

namespace App\Models;

use App\Enums\BusinessModule;
use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\App;

/**
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $status
 * @property string $business_module
 */
class Brand extends Model
{
    use CacheManagerTrait, StorageTrait;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'image_storage_type',
        'image_alt_text',
        'status',
        'business_module',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'image' => 'string',
        'image_storage_type' => 'string',
        'image_alt_text' => 'string',
        'status' => 'integer',
        'business_module' => 'string',
        'brand_products_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function brandProducts(): HasMany
    {
        return $this->hasMany(Product::class)->active();
    }

    public function brandAllProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function brandProductOrderDetails(): HasManyThrough
    {
        return $this->hasManyThrough(OrderDetail::class, Product::class, 'brand_id', 'product_id');
    }

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne('App\Models\SeoMetaInfo', 'seoable');
    }

    public function getNameAttribute($name): ?string
    {
        $segment = request()->segment(1);

        if (in_array($segment, ['admin', 'vendor', 'seller'], true)) {
            return $name;
        }

        return $this->relationLoaded('translations') ? ($this->translations[0]->value ?? $name) : $name;
    }

    public function getDefaultNameAttribute(): ?string
    {
        if (! $this->relationLoaded('translations')) {
            return $this->name;
        }

        return $this->translations->firstWhere('locale', getDefaultLanguage())?->value ?? $this->name;
    }

    public function storage(): MorphMany
    {
        return $this->morphMany(Storage::class, 'data');
    }

    public function getImageFullUrlAttribute(): array
    {
        $value = $this->image;

        return $this->storageLink('brand', $value, $this->image_storage_type ?? 'public');
    }

    protected $appends = ['image_full_url'];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'brands');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'brands');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query
                        ->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }]);
        });
    }
}
