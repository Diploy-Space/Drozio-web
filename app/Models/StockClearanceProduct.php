<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\StockClearanceProduct
 *
 * @property int $id
 * @property string $added_by
 * @property int|null $user_id
 * @property bool $is_active
 * @property string $discount_type
 * @property float $discount_amount
 * @property int|null $product_id
 * @property int|null $shop_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StockClearanceProduct extends Model
{
    use CacheManagerTrait;
    use HasFactory;

    protected $fillable = [
        'added_by',
        'user_id',
        'is_active',
        'discount_type',
        'discount_amount',
        'product_id',
        'setup_id',
        'shop_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'added_by' => 'string',
        'user_id' => 'integer',
        'is_active' => 'integer',
        'discount_type' => 'string',
        'discount_amount' => 'float',
        'product_id' => 'integer',
        'setup_id' => 'integer',
        'shop_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->activeBase()->CheckConfig();
    }

    protected function scopeActiveBase($query)
    {
        return $query
            ->where(['stock_clearance_products.is_active' => 1])
            ->whereIn('stock_clearance_products.added_by', ['admin', 'vendor']);
    }

    public function scopeCheckConfig($query): void
    {
        $currentTime = Carbon::now()->format('H:i:s');
        $query->whereHas('setup', function ($query) use ($currentTime) {
            return $query->where('is_active', 1)
                ->whereDate('duration_start_date', '<=', Carbon::now())
                ->whereDate('duration_end_date', '>=', Carbon::now())
                ->where(function ($subQuery) use ($currentTime) {
                    return $subQuery->where(function ($query) use ($currentTime) {
                        return $query->where('offer_active_time', 'specific_time')
                            ->whereTime('offer_active_range_start', '<=', $currentTime)
                            ->whereTime('offer_active_range_end', '>=', $currentTime);
                    })->orWhere(function ($query) {
                        return $query->where('offer_active_time', 'always')
                            ->whereNull('offer_active_range_start')
                            ->whereNull('offer_active_range_end');
                    });
                });
        });
    }

    // active() minus CheckConfig(): loads the clearance row + its setup WITHOUT the SQL
    // time-window gate, so the "currently active" decision is re-evaluated in PHP per
    // request on cached data (isCurrentlyActive()) instead of being frozen at query time.
    public function scopeActiveIgnoringTimeWindow($query)
    {
        return $query->activeBase()->with('setup');
    }

    public function isCurrentlyActive(): bool
    {
        if ((int) $this->is_active !== 1 || ! in_array($this->added_by, ['admin', 'vendor'], true)) {
            return false;
        }
        $setup = $this->relationLoaded('setup') ? $this->getRelation('setup') : $this->setup;
        if (! $setup || (int) $setup->is_active !== 1) {
            return false;
        }
        $now = Carbon::now();
        if ($now->lt(Carbon::parse($setup->duration_start_date)->startOfDay())
            || $now->gt(Carbon::parse($setup->duration_end_date)->endOfDay())) {
            return false;
        }
        if ($setup->offer_active_time === 'always') {
            return true;
        }
        if ($setup->offer_active_time === 'specific_time' && $setup->offer_active_range_start && $setup->offer_active_range_end) {
            $current = $now->format('H:i:s');

            return $current >= Carbon::parse($setup->offer_active_range_start)->format('H:i:s')
                && $current <= Carbon::parse($setup->offer_active_range_end)->format('H:i:s');
        }

        return false;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'user_id');
    }

    public function setup(): BelongsTo
    {
        return $this->belongsTo(StockClearanceSetup::class, 'setup_id');
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
    }
}
