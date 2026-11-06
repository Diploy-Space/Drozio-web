<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderReportService extends BaseService
{
    use ReportPeriodTrait;

    public const SELLER_ALL = 'all';

    public const SELLER_IN_HOUSE = 'inhouse';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    private const ONGOING_STATUSES = [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_PROCESSING, self::STATUS_CONFIRMED, self::STATUS_PENDING];

    private const CANCELED_STATUSES = [self::STATUS_CANCELED, self::STATUS_FAILED, self::STATUS_RETURNED];

    private const SETTLED_OR_CLOSED_STATUSES = [self::STATUS_DELIVERED, self::STATUS_CANCELED, self::STATUS_RETURNED, self::STATUS_FAILED];

    private const IN_HOUSE_SELLER_ID = 1;

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    private const CASH_PAYMENT_METHODS = ['cash', 'cash_on_delivery'];

    private const WALLET_PAYMENT_METHOD = 'pay_by_wallet';

    private const OFFLINE_PAYMENT_METHOD = 'offline_payment';

    private const EDIT_DUE_PAID = 'paid';

    private const EDIT_DUE_CASH_METHOD = 'cash_on_delivery';

    private const EDIT_DUE_WALLET_METHOD = 'wallet';

    private const EDIT_DUE_OFFLINE_METHOD = 'offline_payment';

    private const EDIT_RETURN_STATUS_RETURNED = 'returned';

    private const EXTRA_DISCOUNT_FREE_SHIPPING = 'free_shipping_over_order_amount';

    private const DELIVERY_TYPE_SELF = 'self_delivery';

    public function __construct(
        Order $order,
        private readonly OrderDetail $orderDetail,
        private readonly OrderEditHistory $orderEditHistory,
    ) {
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

    public function getOrderList(
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        int|string|null $sellerId = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->searchedOrdersQuery(period: $period, sellerId: $sellerId, searchValue: $searchValue)
            ->withSum('details', 'tax')
            ->withSum('details', 'discount')
            ->latest('created_at')
            ->orderByDesc('id');

        return $dataLimit === 'all' ? $query->get() : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @return array{ongoing_order: int, canceled_order: int, delivered_order: int, total_order: int} */
    public function getOrderStatusCounts(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $counts = $this->ordersQuery(period: $period, sellerId: $sellerId)
            ->selectRaw(
                'COUNT(CASE WHEN order_status IN (?, ?, ?, ?) THEN 1 END) AS ongoing_order,
                COUNT(CASE WHEN order_status IN (?, ?, ?) THEN 1 END) AS canceled_order,
                COUNT(CASE WHEN order_status = ? THEN 1 END) AS delivered_order',
                [...self::ONGOING_STATUSES, ...self::CANCELED_STATUSES, self::STATUS_DELIVERED]
            )
            ->toBase()
            ->first();

        $ongoingOrder = (int) $counts->ongoing_order;
        $canceledOrder = (int) $counts->canceled_order;
        $deliveredOrder = (int) $counts->delivered_order;

        return [
            'ongoing_order' => $ongoingOrder,
            'canceled_order' => $canceledOrder,
            'delivered_order' => $deliveredOrder,
            'total_order' => $ongoingOrder + $canceledOrder + $deliveredOrder,
        ];
    }

    /** @return array{total_order_amount: float, due_amount: float, settled_amount: float, referral_discount: float} */
    public function getOrderAmountSummary(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $amounts = $this->ordersQuery(period: $period, sellerId: $sellerId)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_status NOT IN (?, ?, ?, ?) THEN order_amount ELSE 0 END), 0) AS due_amount,
                COALESCE(SUM(CASE WHEN order_status = ? THEN order_amount ELSE 0 END), 0) AS settled_amount,
                COALESCE(SUM(CASE WHEN order_status = ? THEN refer_and_earn_discount ELSE 0 END), 0) AS referral_discount',
                [...self::SETTLED_OR_CLOSED_STATUSES, self::STATUS_DELIVERED, self::STATUS_DELIVERED]
            )
            ->toBase()
            ->first();

        $dueAmount = (float) $amounts->due_amount;
        $settledAmount = (float) $amounts->settled_amount;
        $referralDiscount = (float) $amounts->referral_discount;

        return [
            'total_order_amount' => $dueAmount + $settledAmount - $referralDiscount,
            'due_amount' => $dueAmount,
            'settled_amount' => $settledAmount,
            'referral_discount' => $referralDiscount,
        ];
    }

    /** @return array{cash_payment: float, wallet_payment: float, offline_payment: float, digital_payment: float, total_payment: float, return_amount: float} */
    public function getPaymentStatistics(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);
        $deliveredOrdersQuery = $this->ordersQuery(period: $period, sellerId: $sellerId)->where('order_status', self::STATUS_DELIVERED);

        $orderPayments = (clone $deliveredOrdersQuery)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_method IN (?, ?) THEN init_order_amount ELSE 0 END), 0) AS cash_payment,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN init_order_amount ELSE 0 END), 0) AS wallet_payment,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN init_order_amount ELSE 0 END), 0) AS offline_payment,
                COALESCE(SUM(CASE WHEN payment_method NOT IN (?, ?, ?, ?) THEN init_order_amount ELSE 0 END), 0) AS digital_payment',
                [
                    ...self::CASH_PAYMENT_METHODS,
                    self::WALLET_PAYMENT_METHOD,
                    self::OFFLINE_PAYMENT_METHOD,
                    ...self::CASH_PAYMENT_METHODS, self::WALLET_PAYMENT_METHOD, self::OFFLINE_PAYMENT_METHOD,
                ]
            )
            ->toBase()
            ->first();

        $editPayments = $this->getOrderEditPayments(orderIds: $deliveredOrdersQuery->select('id'));

        $cashPayment = (float) $orderPayments->cash_payment + (float) $editPayments->cash_payment;
        $walletPayment = (float) $orderPayments->wallet_payment + (float) $editPayments->wallet_payment;
        $offlinePayment = (float) $orderPayments->offline_payment + (float) $editPayments->offline_payment;
        $digitalPayment = (float) $orderPayments->digital_payment + (float) $editPayments->digital_payment;
        $returnAmount = (float) $editPayments->return_amount;

        return [
            'cash_payment' => $cashPayment,
            'wallet_payment' => $walletPayment,
            'offline_payment' => $offlinePayment,
            'digital_payment' => $digitalPayment,
            'total_payment' => $cashPayment + $walletPayment + $digitalPayment + $offlinePayment - $returnAmount,
            'return_amount' => $returnAmount,
        ];
    }

    /** @return array<int, array{duration: int|string, order_amount: float}> */
    public function getOrderAmountChart(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $amounts = $this->ordersQuery(period: $period, sellerId: $sellerId)
            ->where('order_status', self::STATUS_DELIVERED)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket, COALESCE(SUM(order_amount), 0) AS order_amount')
            ->groupBy('bucket')
            ->toBase()
            ->pluck('order_amount', 'bucket');

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $chart[] = ['duration' => $duration, 'order_amount' => (float) $amounts->get($bucket, 0)];
        }

        return $chart;
    }

    /** @return array{total_orders: int, total_order_amount: float, total_product_discount: float, total_coupon_discount: float, total_referral_discount: float, total_order_due_amount: float, total_order_return_amount: float, total_tax: float, total_product_tax: float, total_order_commission: float, total_delivery_charge: float, total_deliveryman_incentive: float} */
    public function getSummaryPdfTotals(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null, ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);
        $ordersQuery = $this->searchedOrdersQuery(period: $period, sellerId: $sellerId, searchValue: $searchValue);

        $totals = (clone $ordersQuery)
            ->selectRaw(
                'COUNT(*) AS total_orders,
                COALESCE(SUM(order_amount), 0) AS total_order_amount,
                COALESCE(SUM(discount_amount), 0) AS total_coupon_discount,
                COALESCE(SUM(refer_and_earn_discount), 0) AS total_referral_discount,
                COALESCE(SUM(edit_due_amount), 0) AS total_order_due_amount,
                COALESCE(SUM(edit_return_amount), 0) AS total_order_return_amount,
                COALESCE(SUM(total_tax_amount), 0) AS total_tax,
                COALESCE(SUM(admin_commission), 0) AS total_order_commission,
                COALESCE(SUM(shipping_cost - CASE WHEN extra_discount_type = ? THEN extra_discount ELSE 0 END), 0) AS total_delivery_charge,
                COALESCE(SUM(CASE WHEN delivery_type = ? AND delivery_man_id IS NOT NULL AND delivery_man_id <> 0 THEN deliveryman_charge ELSE 0 END), 0) AS total_deliveryman_incentive',
                [self::EXTRA_DISCOUNT_FREE_SHIPPING, self::DELIVERY_TYPE_SELF]
            )
            ->toBase()
            ->first();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_order_amount' => (float) $totals->total_order_amount,
            'total_product_discount' => (float) $this->orderDetail->newQuery()->whereIn('order_id', (clone $ordersQuery)->select('id'))->sum('discount'),
            'total_product_tax' => (float) $this->orderDetail->newQuery()->whereIn('order_id', (clone $ordersQuery)->select('id'))->sum('tax'),
            'total_coupon_discount' => (float) $totals->total_coupon_discount,
            'total_referral_discount' => (float) $totals->total_referral_discount,
            'total_order_due_amount' => (float) $totals->total_order_due_amount,
            'total_order_return_amount' => (float) $totals->total_order_return_amount,
            'total_tax' => (float) $totals->total_tax,
            'total_order_commission' => (float) $totals->total_order_commission,
            'total_delivery_charge' => (float) $totals->total_delivery_charge,
            'total_deliveryman_incentive' => (float) $totals->total_deliveryman_incentive,
        ];
    }

    private function ordersQuery(array $period, int|string|null $sellerId): Builder
    {
        return $this->query()
            ->when($sellerId === self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where(['seller_id' => self::IN_HOUSE_SELLER_ID, 'seller_is' => self::SELLER_IS_ADMIN]))
            ->when(is_int($sellerId), fn (Builder $builder) => $builder->where(['seller_id' => $sellerId, 'seller_is' => self::SELLER_IS_SELLER]))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function searchedOrdersQuery(array $period, int|string|null $sellerId, ?string $searchValue): Builder
    {
        return $this->ordersQuery(period: $period, sellerId: $sellerId)
            ->when($searchValue, fn (Builder $builder) => $builder->where('id', 'like', "%{$searchValue}%"));
    }

    private function getOrderEditPayments(Builder $orderIds): object
    {
        return $this->orderEditHistory->newQuery()
            ->whereIn('order_id', $orderIds)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_due_payment_status = ? AND order_due_payment_method = ? THEN order_due_amount ELSE 0 END), 0) AS cash_payment,
                COALESCE(SUM(CASE WHEN order_due_payment_status = ? AND order_due_payment_method = ? THEN order_due_amount ELSE 0 END), 0) AS wallet_payment,
                COALESCE(SUM(CASE WHEN order_due_payment_status = ? AND order_due_payment_method = ? THEN order_due_amount ELSE 0 END), 0) AS offline_payment,
                COALESCE(SUM(CASE WHEN order_due_payment_status = ? AND order_due_payment_method <> '' AND order_due_payment_method NOT IN (?, ?, ?) THEN order_due_amount ELSE 0 END), 0) AS digital_payment,
                COALESCE(SUM(CASE WHEN order_return_payment_status = ? THEN order_return_amount ELSE 0 END), 0) AS return_amount",
                [
                    self::EDIT_DUE_PAID, self::EDIT_DUE_CASH_METHOD,
                    self::EDIT_DUE_PAID, self::EDIT_DUE_WALLET_METHOD,
                    self::EDIT_DUE_PAID, self::EDIT_DUE_OFFLINE_METHOD,
                    self::EDIT_DUE_PAID, self::EDIT_DUE_CASH_METHOD, self::EDIT_DUE_WALLET_METHOD, self::EDIT_DUE_OFFLINE_METHOD,
                    self::EDIT_RETURN_STATUS_RETURNED,
                ]
            )
            ->toBase()
            ->first();
    }
}
