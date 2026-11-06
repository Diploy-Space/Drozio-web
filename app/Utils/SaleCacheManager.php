<?php

namespace App\Utils;

use App\Models\FlashDeal;
use App\Models\StockClearanceSetup;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Cache;

class SaleCacheManager
{
    const TTL_FLOOR = 60;

    private const CACHE_MISS_SENTINEL = '__sale_cache_miss__';

    public static function rememberUntilBoundary(string $key, Closure $boundaryResolver, Closure $valueResolver)
    {
        $value = Cache::get($key, self::CACHE_MISS_SENTINEL);
        if ($value === self::CACHE_MISS_SENTINEL) {
            $value = $valueResolver();
            Cache::put($key, $value, self::ttl($boundaryResolver()));
        }
        return $value;
    }

    public static function ttl(?Carbon $boundary): int
    {
        if ($boundary === null) {
            return CACHE_FOR_3_HOURS;
        }
        return max(self::TTL_FLOOR, min(CACHE_FOR_3_HOURS, (int) Carbon::now()->diffInSeconds($boundary, false)));
    }

    public static function nextFlashDealBoundary(): ?Carbon
    {
        $now = Carbon::now();
        $deals = FlashDeal::where(['deal_type' => 'flash_deal', 'status' => 1])
            ->whereDate('end_date', '>=', $now->toDateString())
            ->get(['start_date', 'end_date']);

        $boundaries = [];
        foreach ($deals as $deal) {
            $start = Carbon::parse($deal->start_date)->startOfDay();
            $end = Carbon::parse($deal->end_date)->endOfDay();
            if ($start->greaterThan($now)) {
                $boundaries[] = $start;
            }
            if ($end->greaterThan($now)) {
                $boundaries[] = $end;
            }
        }

        return empty($boundaries) ? null : min($boundaries);
    }

    public static function nextClearanceBoundary(): ?Carbon
    {
        $now = Carbon::now();
        $setups = StockClearanceSetup::where('is_active', 1)
            ->whereDate('duration_end_date', '>=', $now->toDateString())
            ->get(['offer_active_time', 'offer_active_range_start', 'offer_active_range_end', 'duration_start_date', 'duration_end_date']);

        $boundaries = [];
        foreach ($setups as $setup) {
            $durationStart = Carbon::parse($setup->duration_start_date)->startOfDay();
            $durationEnd = Carbon::parse($setup->duration_end_date)->endOfDay();
            if ($durationStart->greaterThan($now)) {
                $boundaries[] = $durationStart;
            }
            if ($durationEnd->greaterThan($now)) {
                $boundaries[] = $durationEnd;
            }

            if ($setup->offer_active_time === 'specific_time' && $setup->offer_active_range_start && $setup->offer_active_range_end) {
                foreach ([$setup->offer_active_range_start, $setup->offer_active_range_end] as $time) {
                    $edge = Carbon::parse($time);
                    $edge = $now->copy()->setTime($edge->hour, $edge->minute, $edge->second);
                    if ($edge->lessThanOrEqualTo($now)) {
                        $edge->addDay();
                    }
                    $boundaries[] = $edge;
                }
            }
        }

        return empty($boundaries) ? null : min($boundaries);
    }

    // Re-evaluates the clearance active window on a cached product set at render time.
    // The lists cache the clearance row unfiltered by time (scopeActiveIgnoringTimeWindow),
    // so here we drop the relation on any product whose window is not open right now —
    // making price accessors and badges fall back to the regular price. Pure PHP, no query.
    public static function normalizeClearanceState($products)
    {
        if ($products instanceof Paginator) {
            self::normalizeClearanceState($products->getCollection());
        } elseif ($products instanceof Enumerable) {
            $products->each(fn ($product) => self::normalizeClearanceState($product));
        } elseif ($products instanceof Model && $products->relationLoaded('clearanceSale')) {
            $clearance = $products->getRelation('clearanceSale');
            if ($clearance && !$clearance->isCurrentlyActive()) {
                $products->setRelation('clearanceSale', null);
            }
        }
        return $products;
    }
}
