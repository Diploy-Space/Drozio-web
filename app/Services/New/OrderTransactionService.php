<?php

namespace App\Services\New;

use App\Models\OrderTransaction;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderTransactionService extends BaseService
{
    public const SELLER_IS_ADMIN = 'admin';

    public const SELLER_IS_SELLER = 'seller';

    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const RECEIVED_BY_ADMIN = 'admin';

    public const STATUS_HOLD = 'hold';

    public const DELIVERED_BY_ADMIN = 'admin';

    public const STATUS_DISBURSE = 'disburse';

    public const AMOUNT_COLUMN_SELLER_AMOUNT = 'seller_amount';

    public const AMOUNT_COLUMN_ADMIN_COMMISSION = 'admin_commission';

    public const STATISTICS_YEAR = 'yearEarn';

    public const STATISTICS_MONTH = 'MonthEarn';

    public const STATISTICS_WEEK = 'WeekEarn';

    /** @var array<int, string> */
    public const STATISTICS_TYPES = [self::STATISTICS_YEAR, self::STATISTICS_MONTH, self::STATISTICS_WEEK];

    /** @var array<int, string> */
    private const AMOUNT_COLUMNS = [self::AMOUNT_COLUMN_SELLER_AMOUNT, self::AMOUNT_COLUMN_ADMIN_COMMISSION];

    /** @var array<int, string> */
    private const WEEK_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    private const PAYMENT_METHODS_RECORDED_ON_SETTLEMENT = ['cash_on_delivery', 'offline_payment'];

    public function __construct(OrderTransaction $orderTransaction)
    {
        parent::__construct($orderTransaction);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('order_id', 'like', "%{$searchValue}%")
                    ->orWhere('transaction_id', 'like', "%{$searchValue}%");
            }));

        match ($sortBy) {
            self::SORT_NEWEST => $query->latest('id'),
            self::SORT_OLDEST => $query->oldest('id'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function isSettled(int|string $orderId): bool
    {
        return $this->findBy(params: ['order_id' => $orderId])?->status === self::STATUS_DISBURSE;
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function sumAdminCommissionForSeller(int $sellerId): float
    {
        return (float) $this->query()
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->where('seller_id', $sellerId)
            ->sum('admin_commission');
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function isRecordedAtPlacement(?string $paymentMethod): bool
    {
        return ! in_array($paymentMethod, self::PAYMENT_METHODS_RECORDED_ON_SETTLEMENT);
    }

    public function createForPlacedOrder(Model $order, float|int $adminCommission, string $paymentMethod, ?Model $shop, string $transactionId): bool
    {
        $subtotal = 0;
        $totalDiscountOnProduct = 0;
        foreach ($order->details as $detail) {
            $subtotal += $detail->price * $detail->qty;
            $totalDiscountOnProduct += $detail->discount;
        }

        $totalTax = $order['total_tax_amount'];
        $orderAmount = $subtotal + $totalTax - $totalDiscountOnProduct - $order['discount_amount'];

        return $this->query()->insert([
            'transaction_id' => $transactionId,
            'customer_id' => $order['customer_id'],
            'seller_id' => $order['seller_id'],
            'shop_id' => $shop?->id,
            'seller_is' => $order['seller_is'],
            'order_id' => $order['id'],
            'order_amount' => $orderAmount,
            'seller_amount' => $orderAmount - $adminCommission,
            'admin_commission' => $adminCommission,
            'received_by' => self::RECEIVED_BY_ADMIN,
            'status' => self::STATUS_HOLD,
            'delivery_charge' => $order['shipping_cost'] - $order['extra_discount'],
            'tax' => $totalTax,
            'delivered_by' => self::DELIVERED_BY_ADMIN,
            'payment_method' => $paymentMethod,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, mixed> */
    public function getSellerDisbursedStatistics(int $sellerId, string $amountColumn, ?string $statisticsType = null): array
    {
        $period = $this->getStatisticsPeriod(statisticsType: $statisticsType);

        if ($period === null || ! in_array($amountColumn, self::AMOUNT_COLUMNS, true)) {
            return [];
        }

        $sums = $this->query()
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId, 'status' => self::STATUS_DISBURSE])
            ->selectRaw("IFNULL(sum({$amountColumn}),0) as sums")
            ->selectRaw('YEAR(created_at) year, MONTH(created_at) month,DAY(created_at) day,DAYNAME(created_at) day_of_week')
            ->whereBetween('created_at', [$period['from'], $period['to']])
            ->when($period['group_by'] === 'month', fn (Builder $builder) => $builder->groupBy('year', 'month'))
            ->when($period['group_by'] === 'day', fn (Builder $builder) => $builder->groupBy('month', 'day'))
            ->when($period['group_by'] === 'day_of_week', fn (Builder $builder) => $builder->groupBy('day_of_week'))
            ->get();

        $amounts = [];
        foreach ($period['range'] as $periodValue) {
            $amounts[$periodValue] = 0;
        }

        foreach ($period['range'] as $periodValue) {
            foreach ($sums as $sum) {
                if ($sum[$period['group_by']] == $periodValue) {
                    $amounts[$periodValue] = $sum['sums'];
                }
            }
        }

        return array_values($amounts);
    }

    /** @return array<int, mixed> */
    public function getSellerMonthlyDisbursedTotals(int $sellerId, string $amountColumn): array
    {
        if (! in_array($amountColumn, self::AMOUNT_COLUMNS, true)) {
            return [];
        }

        $sums = $this->query()
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId, 'status' => self::STATUS_DISBURSE])
            ->selectRaw("IFNULL(sum({$amountColumn}),0) as sums")
            ->selectRaw('YEAR(created_at) year, MONTH(created_at) month')
            ->whereBetween('created_at', [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')])
            ->groupBy('year', 'month')
            ->get();

        $totals = [];
        for ($month = 1; $month <= 12; $month++) {
            $total = 0;
            foreach ($sums as $sum) {
                if ($sum['month'] == $month) {
                    $total = $sum['sums'];
                }
            }
            $totals[] = $total;
        }

        return $totals;
    }

    public function getSellerTaxReportSummary(int $sellerId, string $businessModule, Carbon $from, Carbon $to): array
    {
        $summary = $this->sellerTaxReportQuery(sellerId: $sellerId, businessModule: $businessModule, from: $from, to: $to)
            ->selectRaw('COUNT(*) as total_orders, IFNULL(SUM(order_amount),0) as total_order_amount, IFNULL(SUM(tax),0) as total_tax')
            ->first();

        return [
            'total_orders' => (int) $summary->total_orders,
            'total_order_amount' => (float) $summary->total_order_amount,
            'total_tax' => (float) $summary->total_tax,
        ];
    }

    /** @return array<int, int> */
    public function getSellerTaxReportOrderIds(int $sellerId, string $businessModule, Carbon $from, Carbon $to): array
    {
        return $this->sellerTaxReportQuery(sellerId: $sellerId, businessModule: $businessModule, from: $from, to: $to)
            ->pluck('order_id')
            ->all();
    }

    public function getSellerTaxReportList(int $sellerId, string $businessModule, Carbon $from, Carbon $to, int $dataLimit, int $page): LengthAwarePaginator
    {
        return $this->sellerTaxReportQuery(sellerId: $sellerId, businessModule: $businessModule, from: $from, to: $to)
            ->with(['orderTaxes'])
            ->latest('created_at')
            ->paginate(perPage: $dataLimit, page: $page);
    }

    private function sellerTaxReportQuery(int $sellerId, string $businessModule, Carbon $from, Carbon $to): Builder
    {
        return $this->query()
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->where('seller_id', $sellerId)
            ->where('status', self::STATUS_DISBURSE)
            ->whereHas('order', fn (Builder $builder) => $builder
                ->businessModule($businessModule)
                ->where('order_status', OrderService::STATUS_DELIVERED)
                ->where('order_type', OrderService::ORDER_TYPE_DEFAULT))
            ->whereBetween('updated_at', [$from, $to]);
    }

    public function updateForEditedOrder(object|array $order, float|int $orderAmount, array $orderData): int
    {
        return $this->query()
            ->where([
                'order_id' => $order['id'],
                'seller_is' => $order['seller_is'],
                'seller_id' => $order['seller_id'],
            ])
            ->update([
                'order_amount' => $orderAmount,
                'seller_amount' => $orderAmount - $orderData['admin_commission'],
                'admin_commission' => $orderData['admin_commission'],
                'delivery_charge' => $orderData['shipping_cost'] - $orderData['extra_discount'],
                'tax' => $orderData['total_tax_amount'],
            ]);
    }

    /** @return array{from: string, to: string, range: array<int, int|string>, group_by: string}|null */
    private function getStatisticsPeriod(?string $statisticsType): ?array
    {
        return match ($statisticsType ?: self::STATISTICS_YEAR) {
            self::STATISTICS_YEAR => [
                'from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'range' => range(1, 12),
                'group_by' => 'month',
            ],
            self::STATISTICS_MONTH => [
                'from' => date('Y-m-01'),
                'to' => date('Y-m-t 23:59:59'),
                'range' => range(1, (int) date('t')),
                'group_by' => 'day',
            ],
            self::STATISTICS_WEEK => [
                'from' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'),
                'to' => Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d'),
                'range' => self::WEEK_DAYS,
                'group_by' => 'day_of_week',
            ],
            default => null,
        };
    }
}
