<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardChartService
{
    private const PERIOD_COLUMNS = 'YEAR(created_at) year, MONTH(created_at) month,DAY(created_at) day,DAYNAME(created_at) day_of_week';

    public function __construct(
        private readonly DashboardService $dashboardService,
    )
    {
    }

    public static function adminYearChartsKey(): string
    {
        return 'dashboard_admin_charts_' . Carbon::now()->startOfYear()->format('Y-m-d') . '_' . Carbon::now()->endOfYear()->format('Y-m-d');
    }

    public static function vendorYearChartsKey(int $vendorId): string
    {
        return "dashboard_vendor_charts_{$vendorId}_" . Carbon::now()->startOfYear()->format('Y-m-d') . '_' . Carbon::now()->endOfYear()->format('Y-m-d');
    }

    public function getOrderStatisticsData(string|Carbon $from, string|Carbon $to, array $range, string $type, string $sellerIs, ?int $sellerId = null): array
    {
        $filters = ['seller_is' => $sellerIs, 'payment_status' => 'paid'];
        if ($sellerId !== null) {
            $filters['seller_id'] = $sellerId;
        }
        $orderEarnings = Order::where($filters)
            ->select(
                DB::raw('IFNULL(sum(order_amount),0) as sums'),
                DB::raw(self::PERIOD_COLUMNS),
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('year', 'month', 'day_of_week')
            ->get();
        $orderEarningArray = [];
        foreach ($range as $value) {
            $matchingEarnings = $orderEarnings->where($type, $value);
            $orderEarningArray[$value] = $matchingEarnings->count() > 0
                ? usdToDefaultCurrency($matchingEarnings->sum('sums'))
                : 0;
        }
        return $orderEarningArray;
    }

    public function getEarning(string|Carbon $from, string|Carbon $to, array $range, string $type, string $sellerIs, ?int $sellerId = null): array
    {
        $filters = ['seller_is' => $sellerIs, 'status' => 'disburse'];
        if ($sellerId !== null) {
            $filters['seller_id'] = $sellerId;
        }
        $earning = $this->sumTransactionsByPeriod(filters: $filters, amountColumn: 'seller_amount', from: $from, to: $to, groupBy: $type);
        return $this->dashboardService->getDateWiseAmount(range: $range, type: $type, amountArray: $earning);
    }

    public function getAdminCommission(string|Carbon $from, string|Carbon $to, array $range, string $type, ?int $sellerId = null): array
    {
        $filters = ['seller_is' => 'seller', 'status' => 'disburse'];
        if ($sellerId !== null) {
            $filters['seller_id'] = $sellerId;
        }
        $commissionGiven = $this->sumTransactionsByPeriod(filters: $filters, amountColumn: 'admin_commission', from: $from, to: $to, groupBy: $type);
        return $this->dashboardService->getDateWiseAmount(range: $range, type: $type, amountArray: $commissionGiven);
    }

    private function sumTransactionsByPeriod(array $filters, string $amountColumn, string|Carbon $from, string|Carbon $to, ?string $groupBy): Collection
    {
        return OrderTransaction::where($filters)
            ->select(
                DB::raw("IFNULL(sum({$amountColumn}),0) as sums"),
                DB::raw(self::PERIOD_COLUMNS),
            )
            ->whereBetween('created_at', [$from, $to])
            ->when($groupBy === 'month', fn ($query) => $query->groupBy('year', 'month'))
            ->when($groupBy === 'day', fn ($query) => $query->groupBy('month', 'day'))
            ->when($groupBy === 'day_of_week', fn ($query) => $query->groupBy('day_of_week'))
            ->get();
    }

    public function getAdminYearCharts(): array
    {
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');
        $range = range(1, 12);
        return [
            'inHouseOrderEarningArray' => $this->getOrderStatisticsData(from: $from, to: $to, range: $range, type: 'month', sellerIs: 'admin'),
            'vendorOrderEarningArray' => $this->getOrderStatisticsData(from: $from, to: $to, range: $range, type: 'month', sellerIs: 'seller'),
            'inHouseEarning' => $this->getEarning(from: $from, to: $to, range: $range, type: 'month', sellerIs: 'admin'),
            'vendorEarning' => $this->getEarning(from: $from, to: $to, range: $range, type: 'month', sellerIs: 'seller'),
            'commissionEarn' => $this->getAdminCommission(from: $from, to: $to, range: $range, type: 'month'),
        ];
    }

    public function getVendorYearCharts(int $vendorId): array
    {
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');
        $range = range(1, 12);
        return [
            'vendorEarning' => $this->getEarning(from: $from, to: $to, range: $range, type: 'month', sellerIs: 'seller', sellerId: $vendorId),
            'commissionEarn' => $this->getAdminCommission(from: $from, to: $to, range: $range, type: 'month', sellerId: $vendorId),
        ];
    }
}
