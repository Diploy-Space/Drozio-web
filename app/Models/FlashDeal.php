<?php

namespace App\Models;

use App\Enums\DealType;
use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $title
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $status
 * @property bool $featured
 * @property string $background_color
 * @property string $text_color
 * @property string $banner
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $product_id
 * @property string $deal_type
 * @property-read string|null $start_at
 * @property-read string|null $end_at
 * @property-read float $elapsed_percentage
 */
class FlashDeal extends Model
{
    use CacheManagerTrait, StorageTrait;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'status',
        'featured',
        'background_color',
        'text_color',
        'banner',
        'slug',
        'product_id',
        'deal_type',
    ];

    protected $casts = [
        'status' => 'boolean',
        'featured' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeDealType($query, string $dealType = DealType::FLASH)
    {
        return $query->where('deal_type', $dealType);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FlashDealProduct::class, 'flash_deal_id');
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->status) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $start = date('Y-m-d H:i:s', strtotime($this->start_date));
        $end = date('Y-m-d H:i:s', strtotime($this->end_date));

        return $now >= $start && $now <= $end;
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne('App\Models\SeoMetaInfo', 'seoable');
    }

    public function getTitleAttribute($title): ?string
    {
        if (in_array(request()->segment(1), ['admin', 'vendor', 'seller'], true)) {
            return $title;
        }

        return $this->relationLoaded('translations') ? ($this->translations[0]->value ?? $title) : $title;
    }

    public function getStartAtAttribute(): ?string
    {
        return $this->startsAt()?->toIso8601String();
    }

    public function getEndAtAttribute(): ?string
    {
        return $this->endsAt()?->toIso8601String();
    }

    public function getElapsedPercentageAttribute(): float
    {
        $startsAt = $this->startsAt();
        $endsAt = $this->endsAt();

        if ($startsAt === null || $endsAt === null || now()->lt($startsAt)) {
            return 0;
        }

        if (now()->gte($endsAt)) {
            return 100;
        }

        return round($startsAt->diffInSeconds(now()) / $startsAt->diffInSeconds($endsAt) * 100, 2);
    }

    private function startsAt(): ?Carbon
    {
        return $this->start_date?->copy()->startOfDay();
    }

    private function endsAt(): ?Carbon
    {
        return $this->end_date?->copy()->endOfDay();
    }

    public function getBannerFullUrlAttribute(): string|null|array
    {
        $value = $this->banner;
        if (count($this->storage) > 0 && $this->storageConnectionCheck() == 's3') {
            $storage = $this->storage->where('key', 'banner')->first();
        }

        return $this->storageLink('deal', $value, $storage['value'] ?? 'public');
    }

    protected $appends = ['banner_full_url'];

    protected $with = ['storage'];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'flash_deals');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'flash_deals');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }]);
        });

        static::saved(function ($model) {
            if ($model->isDirty('banner')) {
                $storage = config('filesystems.disks.default') ?? 'public';
                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'banner',
                ], [
                    'value' => $storage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
