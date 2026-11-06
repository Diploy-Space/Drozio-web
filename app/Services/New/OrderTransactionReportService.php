<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\Shop;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderTransactionReportService extends BaseService
{
    use ReportPeriodTrait;

    public const FILTER_STATUS = 'status';

    public const FILTER_CUSTOMER_ID = 'customer_id';

    public const FILTER_SELLER_ID = 'seller_id';

    public const SELLER_IN_HOUSE = 'inhouse';

    public const STATUS_DISBURSE = 'disburse';

    public const STATUS_HOLD = 'hold';

    public const STATUSES = [self::STATUS_DISBURSE, self::STATUS_HOLD];

    private const IN_HOUSE_SELLER_ID = 1;

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    private const PAYMENT_STATUS_PAID = 'paid';

    private const CASH_PAYMENT_METHODS = ['cash', 'cash_on_delivery'];

    private const WALLET_PAYMENT_METHOD = 'pay_by_wallet';

    private const OFFLINE_PAYMENT_METHOD = 'offline_payment';

    private const EDIT_DUE_PAID = 'paid';

    private const EDIT_DUE_CASH_METHOD = 'cash_on_delivery';

    private const EDIT_DUE_WALLET_METHOD = 'wallet';

    private const EDIT_DUE_OFFLINE_METHOD = 'offline_payment';

    private const EDIT_RETURN_STATUS_RETURNED = 'returned';

    private const DELIVERY_TYPE_SELF = 'self_delivery';

    private const SHIPPING_RESPONSIBILITY_IN_HOUSE = 'inhouse_shipping';

    private const SHIPPING_RESPONSIBILITY_SELLER_WISE = 'sellerwise_shipping';

    private const COUPON_BEARER_IN_HOUSE = 'inhouse';

    private const COUPON_BEARER_SELLER = 'seller';

    private const FREE_DELIVERY_BEARER_ADMIN = 'admin';

    private const FREE_DELIVERY_BEARER_SELLER = 'seller';

    private const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    private const COUPON_TYPE_FREE_DELIVERY = 'free_delivery';

    private const ORDER_STATUSES_ONGOING = ['out_for_delivery', 'processing', 'confirmed', 'pending'];

    private const ORDER_STATUSES_CANCELED = ['canceled', 'failed', 'returned'];

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const ROW_RELATIONS = ['seller.shop', 'customer', 'order.deliveryMan', 'order.coupon'];

    private const PDF_CHUNK_SIZE = 200;

    public function __construct(
        OrderTransaction $orderTransaction,
        private readonly Order $order,
        private readonly Product $product,
        private readonly Shop $shop,
        private readonly OrderEditHistory $orderEditHistory,
    ) {
        parent::__construct($orderTransaction);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule()));
    }

    public function getTransactionList(
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        array $filters = [],
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->withOrderDetailTotals(query: $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue))
            ->with(self::ROW_RELATIONS)
            ->latest('created_at');

        $toRow = fn (OrderTransaction $transaction): array => $this->buildTransactionRow(transaction: $transaction);

        return $dataLimit === 'all'
            ? $query->get()->map($toRow)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($toRow);
    }

    /** @return array{total_orders: int, in_house_orders: int, seller_orders: int, total_in_house_products: int, total_seller_products: int, total_stores: int} */
    public function getOrderSummary(string $dateType, ?string $from = null, ?string $to = null, array $filters = [], ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $totalStores = $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue)
            ->distinct()
            ->count(DB::raw("CONCAT(COALESCE(seller_id, ''), '_', COALESCE(seller_is, ''))"));

        return [
            ...$this->getOrderCounts(period: $period, filters: $filters, searchValue: $searchValue),
            ...$this->getProductCounts(period: $period, sellerId: $filters[self::FILTER_SELLER_ID] ?? null),
            'total_stores' => $totalStores,
        ];
    }

    /** @return array<string, float> */
    public function getPaymentStatistics(string $dateType, ?string $from = null, ?string $to = null, array $filters = []): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);
        $paidOrdersQuery = $this->paidOrdersQuery(period: $period, filters: $filters)
            ->whereHas('orderTransaction', fn (Builder $transaction) => $this->applyTransactionStatus(query: $transaction, filters: $filters));

        $orderPayments = (clone $paidOrdersQuery)
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

        $editPayments = $this->getOrderEditPayments(orderIds: $paidOrdersQuery->select('id'));

        $cashPayment = (float) $orderPayments->cash_payment + (float) $editPayments->cash_payment;
        $walletPayment = (float) $orderPayments->wallet_payment + (float) $editPayments->wallet_payment;
        $offlinePayment = (float) $orderPayments->offline_payment + (float) $editPayments->offline_payment;
        $digitalPayment = (float) $orderPayments->digital_payment + (float) $editPayments->digital_payment;
        $returnAmount = (float) $editPayments->return_amount;

        return [
            'digital_payment' => $digitalPayment,
            'cash_payment' => $cashPayment,
            'wallet_payment' => $walletPayment,
            'offline_payment' => $offlinePayment,
            'total_payment' => $cashPayment + $walletPayment + $digitalPayment + $offlinePayment - $returnAmount,
            'return_amount' => $returnAmount,
        ];
    }

    /** @return array<int, array{duration: int|string, order_amount: float}> */
    public function getOrderAmountChart(string $dateType, ?string $from = null, ?string $to = null, array $filters = []): array
    {
        return $this->orderAmountChart(
            period: $this->resolvePeriod(dateType: $dateType, from: $from, to: $to),
            filters: $filters,
            deliverymanChargeCondition: 'delivery_type = ? AND shipping_responsibility = ?',
            conditionBindings: [self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_IN_HOUSE],
        );
    }

    /** @return array<int, array{duration: int|string, order_amount: float}> */
    public function getVendorOrderAmountChart(string $dateType, ?string $from = null, ?string $to = null, array $filters = []): array
    {
        return $this->orderAmountChart(
            period: $this->resolvePeriod(dateType: $dateType, from: $from, to: $to),
            filters: $filters,
            deliverymanChargeCondition: 'delivery_type = ? AND shipping_responsibility = ? AND seller_is = ?',
            conditionBindings: [self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_SELLER_WISE, self::SELLER_IS_SELLER],
        );
    }

    /** @return array{total_products: int, active_products: int, inactive_products: int, pending_products: int} */
    public function getVendorProductCounts(int $sellerId): array
    {
        $counts = $this->product->newQuery()
            ->businessModule($this->businessModule())
            ->where(['user_id' => $sellerId, 'added_by' => self::SELLER_IS_SELLER])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = 1 AND request_status = 1 THEN 1 ELSE 0 END), 0) AS active_products,
                COALESCE(SUM(CASE WHEN status = 0 AND request_status = 1 THEN 1 ELSE 0 END), 0) AS inactive_products,
                COALESCE(SUM(CASE WHEN status = 0 AND request_status = 0 THEN 1 ELSE 0 END), 0) AS pending_products'
            )
            ->toBase()
            ->first();

        return [
            'total_products' => (int) $counts->active_products + (int) $counts->inactive_products + (int) $counts->pending_products,
            'active_products' => (int) $counts->active_products,
            'inactive_products' => (int) $counts->inactive_products,
            'pending_products' => (int) $counts->pending_products,
        ];
    }

    /** @return array<string, int|float> */
    public function getSummaryPdfTotals(string $dateType, ?string $from = null, ?string $to = null, array $filters = [], ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);
        $sellerId = $filters[self::FILTER_SELLER_ID] ?? null;

        $totals = array_fill_keys([
            'total_ordered_product_price', 'total_product_discount', 'total_coupon_discount', 'total_referral_discount',
            'total_discounted_amount', 'total_tax', 'total_delivery_charge', 'total_order_amount', 'total_admin_discount',
            'total_seller_discount', 'total_admin_commission', 'total_admin_net_income', 'total_seller_net_income',
            'total_admin_shipping_discount', 'total_seller_shipping_discount', 'total_deliveryman_incentive',
        ], 0);

        $transactions = $this->withOrderDetailTotals(query: $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue))
            ->with(['order.deliveryMan', 'order.coupon'])
            ->lazyById(self::PDF_CHUNK_SIZE);

        foreach ($transactions as $transaction) {
            $this->addToSummaryPdfTotals(totals: $totals, transaction: $transaction);
        }

        return [
            ...$totals,
            ...$this->getOrderCounts(period: $period, filters: $filters, searchValue: $searchValue),
            ...$this->getProductCounts(period: $period, sellerId: $sellerId),
            'total_stores' => $this->shop->newQuery()
                ->when($sellerId !== null && $sellerId !== self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where('seller_id', $sellerId))
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count(),
        ];
    }

    /** @return array<string, int|float> */
    public function getVendorSummaryPdfTotals(string $dateType, ?string $from = null, ?string $to = null, array $filters = [], ?string $searchValue = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $totals = array_fill_keys([
            'total_ordered_product_price', 'total_product_discount', 'total_coupon_discount', 'total_referral_discount',
            'total_discounted_amount', 'total_tax', 'total_delivery_charge', 'total_order_amount', 'total_admin_discount',
            'total_vendor_discount', 'total_admin_commission', 'total_vendor_net_income', 'total_deliveryman_incentive',
        ], 0);

        $transactions = $this->withOrderDetailTotals(query: $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue))
            ->with(['order.deliveryMan', 'order.coupon'])
            ->lazyById(self::PDF_CHUNK_SIZE);

        foreach ($transactions as $transaction) {
            $this->addToVendorSummaryPdfTotals(totals: $totals, transaction: $transaction);
        }

        return [
            ...$totals,
            ...$this->getOrderCounts(period: $period, filters: $filters, searchValue: $searchValue),
            ...$this->getVendorOrderStatusCounts(period: $period, sellerId: (int) ($filters[self::FILTER_SELLER_ID] ?? 0)),
        ];
    }

    public function getVendorTransactionExportList(string $dateType, ?string $from = null, ?string $to = null, array $filters = [], ?string $searchValue = null): Collection
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        return $this->withOrderDetailTotals(query: $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue))
            ->with(self::ROW_RELATIONS)
            ->latest('created_at')
            ->get()
            ->map(fn (OrderTransaction $transaction): array => [
                ...$this->buildTransactionRow(transaction: $transaction),
                'order_amount' => (float) $transaction['order_amount'],
            ]);
    }

    public function findOrderTransaction(int $orderId, ?int $sellerId = null): ?Model
    {
        return $this->query()
            ->with(['seller.shop', 'customer', 'order', 'orderDetails'])
            ->withSum('orderDetails', 'price')
            ->withSum('orderDetails', 'discount')
            ->withSum('orderDetails', 'qty')
            ->where('order_id', $orderId)
            ->when($sellerId !== null, fn (Builder $builder) => $builder->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId]))
            ->first();
    }

    private function filteredTransactionsQuery(array $period, array $filters, ?string $searchValue): Builder
    {
        return $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(fn (Builder $inner) => $inner
                ->where('order_id', 'like', "%{$searchValue}%")
                ->orWhere('transaction_id', 'like', "%{$searchValue}%")))
            ->tap(fn (Builder $builder) => $this->applyTransactionStatus(query: $builder, filters: $filters))
            ->tap(fn (Builder $builder) => $this->applyCustomerAndSeller(query: $builder, filters: $filters))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function withOrderDetailTotals(Builder $query): Builder
    {
        return $query->withCount([
            'orderDetails as order_details_sum_price' => fn (Builder $detail) => $detail->select(DB::raw('SUM(qty * price)')),
            'orderDetails as order_details_sum_discount' => fn (Builder $detail) => $detail->select(DB::raw('SUM(discount)')),
        ]);
    }

    private function ordersQuery(array $period, array $filters): Builder
    {
        return $this->order->newQuery()
            ->businessModule($this->businessModule())
            ->tap(fn (Builder $builder) => $this->applyCustomerAndSeller(query: $builder, filters: $filters))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function paidOrdersQuery(array $period, array $filters): Builder
    {
        return $this->ordersQuery(period: $period, filters: $filters)->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    /** @return array{total_orders: int, in_house_orders: int, seller_orders: int} */
    private function getOrderCounts(array $period, array $filters, ?string $searchValue): array
    {
        $counts = $this->filteredTransactionsQuery(period: $period, filters: $filters, searchValue: $searchValue)
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN seller_is = ? THEN order_id END) AS in_house_orders, COUNT(DISTINCT CASE WHEN seller_is = ? THEN order_id END) AS seller_orders',
                [self::SELLER_IS_ADMIN, self::SELLER_IS_SELLER]
            )
            ->toBase()
            ->first();

        return [
            'total_orders' => (int) $counts->in_house_orders + (int) $counts->seller_orders,
            'in_house_orders' => (int) $counts->in_house_orders,
            'seller_orders' => (int) $counts->seller_orders,
        ];
    }

    /** @return array<int, array{duration: int|string, order_amount: float}> */
    private function orderAmountChart(array $period, array $filters, string $deliverymanChargeCondition, array $conditionBindings): array
    {
        $amounts = $this->paidOrdersQuery(period: $period, filters: $filters)
            ->when(isset($filters[self::FILTER_STATUS]), fn (Builder $builder) => $builder->whereHas(
                'orderTransaction',
                fn (Builder $transaction) => $this->applyTransactionStatus(query: $transaction, filters: $filters),
            ))
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$deliverymanChargeCondition} THEN (order_amount - deliveryman_charge) ELSE order_amount END), 0) AS order_amount",
                $conditionBindings
            )
            ->groupBy('bucket')
            ->toBase()
            ->pluck('order_amount', 'bucket');

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $chart[] = ['duration' => $duration, 'order_amount' => (float) $amounts->get($bucket, 0)];
        }

        return $chart;
    }

    /** @return array{ongoing_order: int, canceled_order: int, completed_order: int} */
    private function getVendorOrderStatusCounts(array $period, int $sellerId): array
    {
        $ongoingPlaceholders = implode(', ', array_fill(0, count(self::ORDER_STATUSES_ONGOING), '?'));
        $canceledPlaceholders = implode(', ', array_fill(0, count(self::ORDER_STATUSES_CANCELED), '?'));

        $counts = $this->ordersQuery(period: $period, filters: [self::FILTER_SELLER_ID => $sellerId])
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_status IN ({$ongoingPlaceholders}) THEN 1 ELSE 0 END), 0) AS ongoing_order,
                COALESCE(SUM(CASE WHEN order_status IN ({$canceledPlaceholders}) THEN 1 ELSE 0 END), 0) AS canceled_order,
                COALESCE(SUM(CASE WHEN order_status = ? THEN 1 ELSE 0 END), 0) AS completed_order",
                [...self::ORDER_STATUSES_ONGOING, ...self::ORDER_STATUSES_CANCELED, self::ORDER_STATUS_DELIVERED]
            )
            ->toBase()
            ->first();

        return [
            'ongoing_order' => (int) $counts->ongoing_order,
            'canceled_order' => (int) $counts->canceled_order,
            'completed_order' => (int) $counts->completed_order,
        ];
    }

    /** @return array{total_in_house_products: int, total_seller_products: int} */
    private function getProductCounts(array $period, int|string|null $sellerId): array
    {
        $isInHouse = $sellerId === self::SELLER_IN_HOUSE;
        $isVendor = $sellerId !== null && ! $isInHouse;

        $counts = $this->product->newQuery()
            ->businessModule($this->businessModule())
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN added_by = ? AND (? = 0 OR user_id = ?) THEN 1 ELSE 0 END), 0) AS in_house_products,
                COALESCE(SUM(CASE WHEN added_by = ? AND (? = 0 OR user_id = ?) THEN 1 ELSE 0 END), 0) AS seller_products',
                [self::SELLER_IS_ADMIN, (int) $isInHouse, self::IN_HOUSE_SELLER_ID, self::SELLER_IS_SELLER, (int) $isVendor, $isVendor ? $sellerId : 0]
            )
            ->toBase()
            ->first();

        return [
            'total_in_house_products' => (int) $counts->in_house_products,
            'total_seller_products' => (int) $counts->seller_products,
        ];
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

    private function applyTransactionStatus(Builder $query, array $filters): void
    {
        $query->when(isset($filters[self::FILTER_STATUS]), fn (Builder $builder) => $builder->where('status', $filters[self::FILTER_STATUS]));
    }

    private function applyCustomerAndSeller(Builder $query, array $filters): void
    {
        $sellerId = $filters[self::FILTER_SELLER_ID] ?? null;

        $query->when(isset($filters[self::FILTER_CUSTOMER_ID]), fn (Builder $builder) => $builder->where('customer_id', $filters[self::FILTER_CUSTOMER_ID]))
            ->when($sellerId === self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where(['seller_id' => self::IN_HOUSE_SELLER_ID, 'seller_is' => self::SELLER_IS_ADMIN]))
            ->when($sellerId !== null && $sellerId !== self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where(['seller_id' => $sellerId, 'seller_is' => self::SELLER_IS_SELLER]));
    }

    /** @return array<string, mixed> */
    private function buildTransactionRow(OrderTransaction $transaction): array
    {
        $order = $transaction->order;
        $isGuest = $order?->is_guest ?? 0;
        $customer = ! $isGuest ? $transaction->customer : null;
        $orderDetailsSumPrice = (float) ($transaction->order_details_sum_price ?? 0);
        $orderDetailsSumDiscount = (float) ($transaction->order_details_sum_discount ?? 0);
        $referAndEarnDiscount = (float) ($order?->refer_and_earn_discount ?? 0);
        $adminCouponDiscount = $this->adminCouponDiscount(transaction: $transaction, includeInHouseSales: true);
        $adminShippingDiscount = $this->adminShippingDiscount(order: $order);
        $sellerCouponDiscount = $this->sellerCouponDiscount(transaction: $transaction, requireVendorSale: true);

        return [
            'order_id' => $transaction['order_id'],
            'shop_name' => $transaction['seller_is'] == self::SELLER_IS_ADMIN ? getInHouseShopConfig(key: 'name') : $transaction->seller?->shop?->name,
            'is_in_house' => $transaction['seller_is'] == self::SELLER_IS_ADMIN,
            'is_verified' => $transaction['seller_is'] == self::SELLER_IS_ADMIN ? isInHouseShopVerified() : (bool) $transaction->seller?->hasVerifiedBadge(),
            'is_guest' => $isGuest,
            'customer_id' => $customer?->id,
            'customer_name' => $customer ? $customer->f_name.' '.$customer->l_name : null,
            'total_product_amount' => $orderDetailsSumPrice,
            'product_discount' => $orderDetailsSumDiscount,
            'coupon_discount' => (float) ($order?->discount_amount ?? 0),
            'referral_discount' => $referAndEarnDiscount,
            'discounted_amount' => $orderDetailsSumPrice - $orderDetailsSumDiscount - $this->nonFreeDeliveryCouponDiscount(order: $order) - $referAndEarnDiscount,
            'tax' => (float) ($transaction->tax ?? 0),
            'shipping_charge' => (float) ($order?->shipping_cost ?? 0),
            'order_amount' => (float) ($order?->order_amount ?? 0),
            'edit_due_amount' => (float) ($order?->edit_due_amount ?? 0),
            'edit_return_amount' => (float) ($order?->edit_return_amount ?? 0),
            'delivered_by' => $transaction->delivered_by ?? '',
            'deliveryman_incentive' => $order->delivery_type == self::DELIVERY_TYPE_SELF && $order->delivery_man_id ? (float) $order->deliveryman_charge : 0.0,
            'admin_discount' => $adminCouponDiscount + $adminShippingDiscount + $referAndEarnDiscount,
            'vendor_discount' => $sellerCouponDiscount + $this->sellerShippingDiscount(order: $order),
            'admin_commission' => (float) ($transaction->admin_commission ?? 0),
            'admin_net_income' => $this->rowAdminNetIncome(transaction: $transaction, adminCouponDiscount: $adminCouponDiscount, adminShippingDiscount: $adminShippingDiscount, sellerCouponDiscount: $sellerCouponDiscount),
            'vendor_net_income' => $this->rowVendorNetIncome(transaction: $transaction),
            'payment_method' => $transaction->payment_method ?? '',
            'payment_status' => $transaction->status ?? '',
            'seller_is' => $transaction->seller_is ?? '',
            'shipping_responsibility' => $order?->shipping_responsibility ?? '',
            'admin_coupon_discount' => $adminCouponDiscount,
            'seller_coupon_discount' => $sellerCouponDiscount,
        ];
    }

    private function addToSummaryPdfTotals(array &$totals, OrderTransaction $transaction): void
    {
        $order = $transaction->order;

        if (! $order) {
            return;
        }

        $orderDetailsSumPrice = (float) ($transaction->order_details_sum_price ?? 0);
        $orderDetailsSumDiscount = (float) ($transaction->order_details_sum_discount ?? 0);
        $adminCouponDiscount = $this->adminCouponDiscount(transaction: $transaction, includeInHouseSales: false);
        $adminShippingDiscount = $this->adminShippingDiscount(order: $order);
        $sellerCouponDiscount = $this->sellerCouponDiscount(transaction: $transaction, requireVendorSale: false);
        $sellerShippingDiscount = $this->sellerShippingDiscount(order: $order);

        $totals['total_ordered_product_price'] += $orderDetailsSumPrice;
        $totals['total_product_discount'] += $orderDetailsSumDiscount;
        $totals['total_coupon_discount'] += $order->discount_amount;
        $totals['total_discounted_amount'] += $orderDetailsSumPrice - $orderDetailsSumDiscount - $this->nonFreeDeliveryCouponDiscount(order: $order);
        $totals['total_tax'] += $transaction->tax;
        $totals['total_delivery_charge'] += $order->shipping_cost;
        $totals['total_order_amount'] += ($order->order_amount ?? 0) + ($order->edit_due_amount ?? 0) - ($order->edit_return_amount ?? 0);
        $totals['total_admin_discount'] += $adminCouponDiscount + $adminShippingDiscount + ($order->refer_and_earn_discount ?? 0);
        $totals['total_admin_shipping_discount'] += $adminShippingDiscount;
        $totals['total_seller_discount'] += $sellerCouponDiscount + $sellerShippingDiscount;
        $totals['total_seller_shipping_discount'] += $sellerShippingDiscount;
        $totals['total_admin_commission'] += $transaction->admin_commission;
        $totals['total_deliveryman_incentive'] += $order->deliveryman_charge;
        $totals['total_admin_net_income'] += $this->summaryAdminNetIncome(transaction: $transaction, adminCouponDiscount: $adminCouponDiscount, adminShippingDiscount: $adminShippingDiscount, sellerCouponDiscount: $sellerCouponDiscount);
        $totals['total_seller_net_income'] += $this->summarySellerNetIncome(transaction: $transaction, adminCouponDiscount: $adminCouponDiscount);
    }

    private function addToVendorSummaryPdfTotals(array &$totals, OrderTransaction $transaction): void
    {
        $order = $transaction->order;

        if (! $order) {
            return;
        }

        $orderDetailsSumPrice = (float) ($transaction->order_details_sum_price ?? 0);
        $orderDetailsSumDiscount = (float) ($transaction->order_details_sum_discount ?? 0);
        $adminCouponDiscount = $this->adminCouponDiscount(transaction: $transaction, includeInHouseSales: false);
        $isSellerWiseSelfDelivery = $order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_SELLER_WISE
            && $order->delivery_type == self::DELIVERY_TYPE_SELF
            && $order->delivery_man_id
            && $order->seller_is == self::SELLER_IS_SELLER;

        $totals['total_ordered_product_price'] += $orderDetailsSumPrice;
        $totals['total_product_discount'] += $orderDetailsSumDiscount;
        $totals['total_coupon_discount'] += $order->discount_amount;
        $totals['total_referral_discount'] += $order->refer_and_earn_discount ?? 0;
        $totals['total_discounted_amount'] += $orderDetailsSumPrice - $orderDetailsSumDiscount - $this->nonFreeDeliveryCouponDiscount(order: $order);
        $totals['total_tax'] += $transaction->tax;
        $totals['total_delivery_charge'] += $order->shipping_cost;
        $totals['total_order_amount'] += $order->order_amount;
        $totals['total_admin_discount'] += $adminCouponDiscount + $this->adminShippingDiscount(order: $order);
        $totals['total_vendor_discount'] += $this->sellerCouponDiscount(transaction: $transaction, requireVendorSale: false) + $this->sellerShippingDiscount(order: $order);
        $totals['total_admin_commission'] += $transaction->admin_commission;
        $totals['total_deliveryman_incentive'] += $isSellerWiseSelfDelivery ? $order->deliveryman_charge : 0;
        $totals['total_vendor_net_income'] += $this->summarySellerNetIncome(transaction: $transaction, adminCouponDiscount: $adminCouponDiscount);
    }

    private function nonFreeDeliveryCouponDiscount(?Model $order): float
    {
        return isset($order->coupon) && $order->coupon->coupon_type != self::COUPON_TYPE_FREE_DELIVERY ? (float) $order->discount_amount : 0.0;
    }

    private function adminCouponDiscount(OrderTransaction $transaction, bool $includeInHouseSales): float
    {
        $order = $transaction->order;
        $isAdminBorne = $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE
            || ($includeInHouseSales && $transaction['seller_is'] == self::SELLER_IS_ADMIN);

        return $isAdminBorne && $order->discount_type == self::DISCOUNT_TYPE_COUPON ? (float) $order->discount_amount : 0.0;
    }

    private function sellerCouponDiscount(OrderTransaction $transaction, bool $requireVendorSale): float
    {
        $order = $transaction->order;
        $isVendorSale = ! $requireVendorSale || $transaction['seller_is'] == self::SELLER_IS_SELLER;

        return $isVendorSale && $order->coupon_discount_bearer == self::COUPON_BEARER_SELLER && $order->discount_type == self::DISCOUNT_TYPE_COUPON
            ? (float) $order->discount_amount
            : 0.0;
    }

    private function adminShippingDiscount(?Model $order): float
    {
        return $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN && $order->is_shipping_free ? (float) $order->extra_discount : 0.0;
    }

    private function sellerShippingDiscount(?Model $order): float
    {
        return $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_SELLER && $order->is_shipping_free ? (float) $order->extra_discount : 0.0;
    }

    private function adminShippingEarning(OrderTransaction $transaction): float
    {
        $order = $transaction->order;
        $isVendorSaleShippedInHouse = $transaction['seller_is'] == self::SELLER_IS_SELLER && $order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_IN_HOUSE;

        if (isset($order->deliveryMan) && $order->deliveryMan->seller_id == 0) {
            $sellerPaidFreeDelivery = $isVendorSaleShippedInHouse ? $this->sellerShippingDiscount(order: $order) : 0.0;

            return (float) $transaction['delivery_charge'] + $sellerPaidFreeDelivery;
        }

        if (! isset($order->deliveryMan) && $isVendorSaleShippedInHouse) {
            return $order->is_shipping_free && $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN ? 0.0 : (float) $order->shipping_cost;
        }

        return 0.0;
    }

    private function adminBorneDeliverymanCharge(Model $order): float
    {
        $isSelfDelivery = empty($order->delivery_type) || $order->delivery_type == self::DELIVERY_TYPE_SELF;
        $isAdminShipping = $order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_IN_HOUSE || $order->seller_is == self::SELLER_IS_ADMIN;

        return $isSelfDelivery && $isAdminShipping ? (float) $order->deliveryman_charge : 0.0;
    }

    private function vendorBorneDeliverymanCharge(Model $order): float
    {
        $isSelfDelivery = empty($order->delivery_type) || $order->delivery_type == self::DELIVERY_TYPE_SELF;

        return $isSelfDelivery && $order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_SELLER_WISE && $order->seller_is == self::SELLER_IS_SELLER
            ? (float) $order->deliveryman_charge
            : 0.0;
    }

    private function isFreeDeliveryCouponBorneBySeller(Model $order): bool
    {
        return $order->coupon_discount_bearer == self::COUPON_BEARER_SELLER && isset($order->coupon) && $order->coupon->coupon_type == self::COUPON_TYPE_FREE_DELIVERY;
    }

    private function rowAdminNetIncome(OrderTransaction $transaction, float $adminCouponDiscount, float $adminShippingDiscount, float $sellerCouponDiscount): float
    {
        $order = $transaction->order;
        $isVendorSale = $transaction['seller_is'] == self::SELLER_IS_SELLER;
        $netIncome = $transaction['seller_is'] == self::SELLER_IS_ADMIN ? (float) $transaction['order_amount'] : 0.0;

        $netIncome += $this->adminShippingEarning(transaction: $transaction);
        $netIncome += (float) $transaction['admin_commission'];
        $netIncome -= (float) ($order->refer_and_earn_discount ?? 0);
        $netIncome -= $this->adminBorneDeliverymanCharge(order: $order);

        if ($isVendorSale && $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE) {
            $netIncome -= $adminCouponDiscount;
        }

        if ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_IN_HOUSE) {
            if (! $order->is_shipping_free) {
                $netIncome += $isVendorSale ? (float) $order->shipping_cost : 0.0;
                $netIncome += $this->isFreeDeliveryCouponBorneBySeller(order: $order) ? $sellerCouponDiscount : 0.0;
            }
        } elseif ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_SELLER_WISE && $isVendorSale && $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN) {
            $netIncome -= $adminShippingDiscount;
        }

        return $netIncome;
    }

    private function rowVendorNetIncome(OrderTransaction $transaction): float
    {
        $order = $transaction->order;
        $netIncome = 0.0;

        if ($transaction['seller_is'] == self::SELLER_IS_SELLER) {
            $netIncome += (float) $transaction['order_amount'] - (float) $transaction['admin_commission'];
            $netIncome += isset($order->deliveryMan) && $order->deliveryMan->seller_id != 0 ? (float) $transaction['delivery_charge'] : 0.0;
        }

        $netIncome -= $this->vendorBorneDeliverymanCharge(order: $order);

        if ($transaction['seller_is'] == self::SELLER_IS_SELLER) {
            $netIncome += $this->adminCouponDiscount(transaction: $transaction, includeInHouseSales: false);
            $netIncome += $this->vendorShippingAdjustment(order: $order, deductSellerBorneFreeDelivery: false);
        }

        return $netIncome;
    }

    private function summaryAdminNetIncome(OrderTransaction $transaction, float $adminCouponDiscount, float $adminShippingDiscount, float $sellerCouponDiscount): float
    {
        $order = $transaction->order;
        $isVendorSale = $transaction['seller_is'] == self::SELLER_IS_SELLER;
        $netIncome = $transaction['seller_is'] == self::SELLER_IS_ADMIN ? (float) $transaction['order_amount'] : 0.0;

        $netIncome += $this->adminShippingEarning(transaction: $transaction);
        $netIncome += (float) $transaction['admin_commission'];
        $netIncome -= (float) ($order->refer_and_earn_discount ?? 0);
        $netIncome -= $this->adminBorneDeliverymanCharge(order: $order);

        if ($isVendorSale && $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE) {
            $netIncome -= $adminCouponDiscount;
        }

        if ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_IN_HOUSE) {
            if (! $order->is_shipping_free && $this->isFreeDeliveryCouponBorneBySeller(order: $order)) {
                $netIncome += $sellerCouponDiscount;
            }
        } elseif ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_SELLER_WISE && $isVendorSale && $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN) {
            $netIncome -= $adminShippingDiscount;
        }

        return $netIncome;
    }

    private function summarySellerNetIncome(OrderTransaction $transaction, float $adminCouponDiscount): float
    {
        $order = $transaction->order;
        $netIncome = 0.0;

        if ($transaction['seller_is'] == self::SELLER_IS_SELLER) {
            $netIncome += (float) $transaction['order_amount'] - (float) $transaction['admin_commission'];
            $netIncome += isset($order->deliveryMan) && $order->deliveryMan->seller_id != 0 ? (float) $transaction['delivery_charge'] : 0.0;
        }

        $netIncome -= $this->vendorBorneDeliverymanCharge(order: $order);

        if ($transaction['seller_is'] == self::SELLER_IS_SELLER) {
            $netIncome += $order->coupon_discount_bearer == self::COUPON_BEARER_IN_HOUSE ? $adminCouponDiscount : 0.0;
            $netIncome += $this->vendorShippingAdjustment(order: $order, deductSellerBorneFreeDelivery: true);
        }

        return $netIncome;
    }

    private function vendorShippingAdjustment(Model $order, bool $deductSellerBorneFreeDelivery): float
    {
        if ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_IN_HOUSE) {
            $isAdminFreeDelivery = $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN && $order->is_shipping_free;

            return $isAdminFreeDelivery ? 0.0 : -(float) $order->shipping_cost;
        }

        if ($order->shipping_responsibility == self::SHIPPING_RESPONSIBILITY_SELLER_WISE) {
            $adjustment = $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_ADMIN ? (float) $order->shipping_cost : 0.0;

            if ($deductSellerBorneFreeDelivery && $order->free_delivery_bearer == self::FREE_DELIVERY_BEARER_SELLER) {
                $adjustment -= (float) $order->shipping_cost;
            }

            return $adjustment;
        }

        return 0.0;
    }
}
