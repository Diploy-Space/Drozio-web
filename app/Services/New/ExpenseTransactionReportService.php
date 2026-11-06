<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ExpenseTransactionReportService extends BaseService
{
    use ReportPeriodTrait;

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const COUPON_BEARER_IN_HOUSE = 'inhouse';

    private const COUPON_BEARER_SELLER = 'seller';

    private const FREE_DELIVERY_BEARER_ADMIN = 'admin';

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    private const FREE_DELIVERY_BEARER_SELLER = 'seller';

    private const TRANSACTION_STATUS_DISBURSE = 'disburse';

    private const EMPTY_COUPON_CODES = ['0', 'NULL'];

    private const EXTRA_DISCOUNT_FREE_SHIPPING_OVER_AMOUNT = 'free_shipping_over_order_amount';

    private const COUPON_TYPE_FREE_DELIVERY = 'free_delivery';

    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
    }

    public function getExpenseTransactionList(
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->expenseOrdersQuery(period: $period, searchValue: $searchValue)
            ->with(['orderTransaction', 'coupon'])
            ->latest('created_at');

        $withExpense = fn (Order $order): Order => $this->attachExpenseDetails(order: $order);

        return $dataLimit === 'all'
            ? $query->get()->each($withExpense)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($withExpense);
    }

    /** @return array{total_expense: float, total_free_delivery: float, total_free_delivery_over_amount: float, total_coupon_discount: float, total_referral_discount: float} */
    public function getExpenseSummary(string $dateType, ?string $from = null, ?string $to = null, ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $summary = $this->expenseOrdersQuery(period: $period, searchValue: $searchValue)
            ->selectRaw('COALESCE(SUM(refer_and_earn_discount), 0) AS referral_discount')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN coupon_discount_bearer = ? AND '.$this->freeDeliveryCouponExistsExpression().' THEN discount_amount ELSE 0 END), 0) AS free_delivery',
                [self::COUPON_BEARER_IN_HOUSE, self::COUPON_TYPE_FREE_DELIVERY]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN coupon_discount_bearer = ? OR (coupon_discount_bearer = ? AND seller_is = ?) THEN discount_amount ELSE 0 END), 0) AS coupon_discount',
                [self::COUPON_BEARER_IN_HOUSE, self::COUPON_BEARER_SELLER, self::SELLER_IS_ADMIN]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN COALESCE(is_shipping_free, 0) <> 0 AND free_delivery_bearer = ? THEN extra_discount ELSE 0 END), 0) AS free_delivery_over_amount',
                [self::FREE_DELIVERY_BEARER_ADMIN]
            )
            ->toBase()
            ->first();

        $freeDelivery = (float) $summary->free_delivery;
        $freeDeliveryOverAmount = (float) $summary->free_delivery_over_amount;
        $couponDiscount = (float) $summary->coupon_discount;
        $referralDiscount = (float) $summary->referral_discount;

        return [
            'total_expense' => $freeDelivery + $couponDiscount + $referralDiscount + $freeDeliveryOverAmount,
            'total_free_delivery' => $freeDelivery,
            'total_free_delivery_over_amount' => $freeDeliveryOverAmount,
            'total_coupon_discount' => $couponDiscount,
            'total_referral_discount' => $referralDiscount,
        ];
    }

    /** @return array<int, array{duration: int|string, expense_amount: float}> */
    public function getExpenseChart(string $dateType, ?string $from = null, ?string $to = null, ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);
        $isMonthly = $period['granularity'] === self::GRANULARITY_MONTH;

        $amounts = $this->expenseOrdersQuery(period: $period, searchValue: $searchValue)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                'COALESCE(SUM(
                    CASE WHEN COALESCE(is_shipping_free, 0) <> 0 AND free_delivery_bearer = ? THEN COALESCE(extra_discount, 0) ELSE 0 END
                    + CASE WHEN coupon_discount_bearer = ? OR (? = 1 AND seller_is = ?) THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + COALESCE(refer_and_earn_discount, 0)
                ), 0) AS expense_amount',
                [self::FREE_DELIVERY_BEARER_ADMIN, self::COUPON_BEARER_IN_HOUSE, (int) $isMonthly, self::SELLER_IS_ADMIN]
            )
            ->groupBy('bucket')
            ->toBase()
            ->pluck('expense_amount', 'bucket');

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $chart[] = [
                'duration' => $isMonthly ? Carbon::create(month: $bucket)->format('F') : $duration,
                'expense_amount' => (float) $amounts->get($bucket, 0),
            ];
        }

        return $chart;
    }

    public function findExpenseTransaction(int $orderId): ?Model
    {
        return $this->query()->with(['orderTransaction', 'coupon'])->find($orderId);
    }

    public function getVendorExpenseTransactionList(
        int $sellerId,
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->vendorExpenseOrdersQuery(sellerId: $sellerId, period: $period, searchValue: $searchValue, includeSellerBorneCoupons: true)
            ->with(['orderTransaction', 'coupon'])
            ->latest('created_at');

        $withExpense = fn (Order $order): Order => $this->attachVendorExpenseDetails(order: $order);

        return $dataLimit === 'all'
            ? $query->get()->each($withExpense)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($withExpense);
    }

    public function getVendorExpenseExportList(int $sellerId, string $dateType, ?string $from = null, ?string $to = null, ?string $searchValue = null): Collection
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        return $this->vendorExpenseOrdersQuery(sellerId: $sellerId, period: $period, searchValue: $searchValue, includeSellerBorneCoupons: false)
            ->with(['orderTransaction', 'coupon'])
            ->latest('created_at')
            ->get()
            ->each(fn (Order $order): Order => $this->attachVendorExpenseDetails(order: $order));
    }

    /** @return array{total_expense: float, total_free_delivery: float, total_coupon_discount: float} */
    public function getVendorExpenseSummary(int $sellerId, string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $summary = $this->vendorExpenseOrdersQuery(sellerId: $sellerId, period: $period, searchValue: null, includeSellerBorneCoupons: true)
            ->selectRaw($this->vendorExpenseAmountExpression().' AS total_expense', $this->vendorExpenseAmountBindings())
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN coupon_discount_bearer = ? AND '.$this->freeDeliveryCouponExistsExpression().' THEN discount_amount ELSE 0 END), 0) AS free_delivery_coupon',
                [self::COUPON_BEARER_SELLER, self::COUPON_TYPE_FREE_DELIVERY]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN COALESCE(is_shipping_free, 0) <> 0 AND free_delivery_bearer = ? THEN extra_discount ELSE 0 END), 0) AS free_shipping',
                [self::FREE_DELIVERY_BEARER_SELLER]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN coupon_discount_bearer = ? AND NOT '.$this->freeDeliveryCouponExistsExpression().' THEN discount_amount ELSE 0 END), 0) AS coupon_discount',
                [self::COUPON_BEARER_SELLER, self::COUPON_TYPE_FREE_DELIVERY]
            )
            ->toBase()
            ->first();

        return [
            'total_expense' => (float) $summary->total_expense,
            'total_free_delivery' => (float) $summary->free_delivery_coupon + (float) $summary->free_shipping,
            'total_coupon_discount' => (float) $summary->coupon_discount,
        ];
    }

    /** @return array{total_expense: float, total_free_delivery: float, total_coupon_discount: float, total_free_delivery_over_amount: float} */
    public function getVendorExpenseSummaryPdfTotals(int $sellerId, string $dateType, ?string $from = null, ?string $to = null, ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $totals = $this->vendorExpenseOrdersQuery(sellerId: $sellerId, period: $period, searchValue: $searchValue, includeSellerBorneCoupons: false)
            ->selectRaw($this->vendorExpenseAmountExpression().' AS total_expense', $this->vendorExpenseAmountBindings())
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN '.$this->freeDeliveryCouponExistsExpression().' THEN discount_amount ELSE 0 END), 0) AS free_delivery',
                [self::COUPON_TYPE_FREE_DELIVERY]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN coupon_discount_bearer = ? AND NOT '.$this->freeDeliveryCouponExistsExpression().' THEN discount_amount ELSE 0 END), 0) AS coupon_discount',
                [self::COUPON_BEARER_SELLER, self::COUPON_TYPE_FREE_DELIVERY]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN free_delivery_bearer = ? THEN extra_discount ELSE 0 END), 0) AS free_delivery_over_amount',
                [self::FREE_DELIVERY_BEARER_SELLER]
            )
            ->toBase()
            ->first();

        return [
            'total_expense' => (float) $totals->total_expense,
            'total_free_delivery' => (float) $totals->free_delivery,
            'total_coupon_discount' => (float) $totals->coupon_discount,
            'total_free_delivery_over_amount' => (float) $totals->free_delivery_over_amount,
        ];
    }

    /** @return array<int, array{duration: int|string, expense_amount: float}> */
    public function getVendorExpenseChart(int $sellerId, string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $amounts = $this->vendorExpenseOrdersQuery(sellerId: $sellerId, period: $period, searchValue: null, includeSellerBorneCoupons: false)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw($this->vendorExpenseAmountExpression().' AS expense_amount', $this->vendorExpenseAmountBindings())
            ->groupBy('bucket')
            ->toBase()
            ->pluck('expense_amount', 'bucket');

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $chart[] = [
                'duration' => $duration,
                'expense_amount' => (float) $amounts->get($bucket, 0),
            ];
        }

        return $chart;
    }

    public function findVendorExpenseTransaction(int $orderId, int $sellerId): ?Order
    {
        $order = $this->query()
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId])
            ->with(['orderTransaction', 'coupon'])
            ->find($orderId);

        return $order === null ? null : $this->attachVendorExpenseDetails(order: $order);
    }

    private function expenseOrdersQuery(array $period, ?string $searchValue): Builder
    {
        return $this->query()
            ->where(['order_type' => self::ORDER_TYPE_DEFAULT, 'order_status' => self::ORDER_STATUS_DELIVERED])
            ->where(fn (Builder $bearer) => $bearer
                ->where('coupon_discount_bearer', self::COUPON_BEARER_IN_HOUSE)
                ->orWhere('free_delivery_bearer', self::FREE_DELIVERY_BEARER_ADMIN))
            ->where(fn (Builder $discount) => $discount
                ->whereNotIn('coupon_code', self::EMPTY_COUPON_CODES)
                ->orWhere(fn (Builder $freeShipping) => $freeShipping->where([
                    'extra_discount_type' => self::EXTRA_DISCOUNT_FREE_SHIPPING_OVER_AMOUNT,
                    'free_delivery_bearer' => self::FREE_DELIVERY_BEARER_ADMIN,
                ]))
                ->orWhere('refer_and_earn_discount', '>', 0))
            ->when($searchValue, fn (Builder $builder) => $builder->where(fn (Builder $search) => $search
                ->where('id', 'like', "%{$searchValue}%")
                ->orWhereHas('orderTransaction', fn (Builder $transaction) => $transaction->where('transaction_id', 'like', "%{$searchValue}%"))))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function vendorExpenseOrdersQuery(int $sellerId, array $period, ?string $searchValue, bool $includeSellerBorneCoupons): Builder
    {
        return $this->query()
            ->where([
                'order_type' => self::ORDER_TYPE_DEFAULT,
                'order_status' => self::ORDER_STATUS_DELIVERED,
                'seller_is' => self::SELLER_IS_SELLER,
                'seller_id' => $sellerId,
            ])
            ->where(fn (Builder $bearer) => $bearer
                ->whereNotIn('free_delivery_bearer', [self::FREE_DELIVERY_BEARER_ADMIN])
                ->orWhereNot('coupon_discount_bearer', self::COUPON_BEARER_IN_HOUSE))
            ->where(fn (Builder $discount) => $discount
                ->whereNotIn('coupon_code', self::EMPTY_COUPON_CODES)
                ->orWhere(fn (Builder $freeShipping) => $freeShipping->where([
                    'extra_discount_type' => self::EXTRA_DISCOUNT_FREE_SHIPPING_OVER_AMOUNT,
                    'free_delivery_bearer' => self::FREE_DELIVERY_BEARER_SELLER,
                ]))
                ->when($includeSellerBorneCoupons, fn (Builder $sellerCoupon) => $sellerCoupon->orWhere('coupon_discount_bearer', self::COUPON_BEARER_SELLER)))
            ->whereHas('orderTransaction', fn (Builder $transaction) => $transaction
                ->where('status', self::TRANSACTION_STATUS_DISBURSE)
                ->when($searchValue, fn (Builder $builder) => $builder->where(fn (Builder $search) => $search
                    ->where('order_id', 'like', "%{$searchValue}%")
                    ->orWhere('transaction_id', 'like', "%{$searchValue}%"))))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function vendorExpenseAmountExpression(): string
    {
        return 'COALESCE(SUM(
            CASE WHEN coupon_discount_bearer = ? THEN discount_amount ELSE 0 END
            + CASE WHEN free_delivery_bearer = ? THEN extra_discount ELSE 0 END
        ), 0)';
    }

    private function vendorExpenseAmountBindings(): array
    {
        return [self::COUPON_BEARER_SELLER, self::FREE_DELIVERY_BEARER_SELLER];
    }

    private function freeDeliveryCouponExistsExpression(): string
    {
        return 'EXISTS (SELECT 1 FROM coupons WHERE coupons.code = orders.coupon_code AND coupons.coupon_type = ?)';
    }

    private function attachExpenseDetails(Order $order): Order
    {
        $isAdminBorneCoupon = $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE
            || ($order->coupon_discount_bearer == self::COUPON_BEARER_SELLER && $order->seller_is == self::SELLER_IS_ADMIN);

        $order->setAttribute('expense_amount', (float) ($order->refer_and_earn_discount ?? 0)
            + ($order->seller_is == self::SELLER_IS_ADMIN || $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE ? (float) $order->discount_amount : 0.0)
            + ($order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN ? (float) $order->extra_discount : 0.0));

        $order->setAttribute('expense_types', [
            'coupon_type' => $isAdminBorneCoupon ? $order->coupon?->coupon_type : null,
            'has_untyped_coupon' => $isAdminBorneCoupon && ! isset($order->coupon->coupon_type) && ! is_null($order->coupon_code) && $order->coupon_code != 0,
            'free_delivery_type' => $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN ? (string) $order->extra_discount_type : null,
            'has_referral_discount' => $order->refer_and_earn_discount > 0,
        ]);

        return $order;
    }

    private function attachVendorExpenseDetails(Order $order): Order
    {
        $isSellerBorneCoupon = $order->coupon_discount_bearer == self::COUPON_BEARER_SELLER;
        $isSellerBorneFreeDelivery = $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_SELLER;

        $order->setAttribute('expense_amount', ($isSellerBorneCoupon ? (float) $order->discount_amount : 0.0)
            + ($isSellerBorneFreeDelivery ? (float) $order->extra_discount : 0.0));

        $order->setAttribute('expense_types', [
            'coupon_type' => $isSellerBorneCoupon ? $order->coupon?->coupon_type : null,
            'free_delivery_type' => $isSellerBorneFreeDelivery ? (string) $order->extra_discount_type : null,
        ]);

        return $order;
    }
}
