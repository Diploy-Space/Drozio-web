<?php

namespace App\Console\Commands;

use App\Services\DashboardChartService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Precomputes the admin dashboard's yearly earning/order chart series (grouped
 * aggregates over the full orders / order_transactions tables) into the cache,
 * so the dashboard reads them instead of recomputing on a cold cache. Runs
 * nightly; the same keys the controller reads via Cache::remember(). Chart data
 * is intentionally not real-time — a few hours of staleness is acceptable.
 *
 *   php artisan dashboard:warm-report-cache
 */
class WarmDashboardReportCache extends Command
{
    protected $signature = 'dashboard:warm-report-cache';

    protected $description = 'Precompute admin dashboard chart aggregates into the cache (nightly warm)';

    public function handle(DashboardChartService $chartService): int
    {
        Cache::put(DashboardChartService::adminYearChartsKey(), $chartService->getAdminYearCharts(), CACHE_FOR_1_HOUR);
        $this->info('Warmed admin dashboard chart cache.');
        return self::SUCCESS;
    }
}
