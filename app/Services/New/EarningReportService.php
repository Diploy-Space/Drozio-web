<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Seller;
use App\Models\SellerWallet;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EarningReportService extends BaseService
{
    use ReportPeriodTrait;

    private const SELLER_IS_ADMIN = 'admin';

    private const SELLER_IS_SELLER = 'seller';

    private const IN_HOUSE_DELIVERY_MAN_SELLER_ID = '0';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const COUPON_BEARER_IN_HOUSE = 'inhouse';

    private const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    private const FREE_DELIVERY_BEARER_ADMIN = 'admin';

    private const SHIPPING_RESPONSIBILITY_IN_HOUSE = 'inhouse_shipping';

    private const SHIPPING_RESPONSIBILITY_SELLER_WISE = 'sellerwise_shipping';

    private const COUPON_BEARER_SELLER = 'seller';

    private const FREE_DELIVERY_BEARER_SELLER = 'seller';

    private const DELIVERY_TYPE_SELF = 'self_delivery';

    private const TRANSACTION_STATUS_DISBURSED = 'disburse';

    private const REFUND_PAYMENT_STATUS_PAID = 'paid';

    private const CASH_PAYMENT_METHODS = ['cash', 'cash_on_delivery'];

    private const WALLET_PAYMENT_METHOD = 'pay_by_wallet';

    private const OFFLINE_PAYMENT_METHOD = 'offline_payment';

    private const EDIT_DUE_PAID = 'paid';

    private const EDIT_DUE_CASH_METHOD = 'cash_on_delivery';

    private const EDIT_DUE_WALLET_METHOD = 'wallet';

    private const EDIT_DUE_OFFLINE_METHOD = 'offline_payment';

    private const EDIT_RETURN_STATUS_RETURNED = 'returned';

    public function __construct(
        Order $order,
        private readonly OrderTransaction $orderTransaction,
        private readonly RefundTransaction $refundTransaction,
        private readonly OrderEditHistory $orderEditHistory,
        private readonly Product $product,
        private readonly Seller $seller,
        private readonly SellerWallet $sellerWallet,
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

    /** @return array{rows: array<int, array<string, int|float|string>>, summary: array<string, float>} */
    public function getAdminEarningReport(string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $orderAmounts = $this->getAdminOrderAmountsByBucket(period: $period);
        $deliverymanIncentives = $this->getAdminDeliverymanIncentivesByBucket(period: $period);
        $taxes = $this->getTaxesByBucket(period: $period, sellerIs: self::SELLER_IS_ADMIN);
        $refunds = $this->getRefundsByBucket(period: $period, paidBy: self::SELLER_IS_ADMIN);

        $rows = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $amounts = $orderAmounts->get($bucket);

            $rows[] = $this->buildAdminEarningRow(
                duration: $duration,
                inHouseEarning: (float) ($amounts->in_house_earning ?? 0),
                commissionEarning: (float) ($amounts->commission_earning ?? 0),
                shippingEarning: (float) ($amounts->shipping_earning ?? 0),
                deliverymanIncentive: (float) $deliverymanIncentives->get($bucket, 0),
                discountGiven: (float) ($amounts->discount_given ?? 0),
                vatTax: (float) $taxes->get($bucket, 0),
                refundGiven: (float) $refunds->get($bucket, 0),
            );
        }

        return [
            'rows' => $rows,
            'summary' => $this->summarizeAdminEarningRows(rows: $rows),
        ];
    }

    /** @return array<string, float> */
    public function getAdminPaymentStatistics(string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $orderPayments = $this->deliveredOrdersQuery(period: $period)
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

        $editPayments = $this->getOrderEditPayments(period: $period);

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
            'total_payment' => $cashPayment + $walletPayment + $offlinePayment + $digitalPayment - $returnAmount,
            'return_amount' => $returnAmount,
        ];
    }

    public function getAverageOrderValue(string $dateType, ?string $from = null, ?string $to = null): float
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        return (float) $this->deliveredOrdersQuery(period: $period)
            ->where('seller_is', self::SELLER_IS_ADMIN)
            ->avg('order_amount');
    }

    public function countInHouseProducts(string $dateType, ?string $from = null, ?string $to = null): int
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        return $this->product->newQuery()
            ->businessModule($this->businessModule())
            ->where('added_by', self::SELLER_IS_ADMIN)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();
    }

    /** @return array<int, array{duration: int|string, total_earning: float}> */
    public function getVendorEarningChart(string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $orderAmounts = $this->getVendorOrderAmountsByBucket(period: $period);
        $deliverymanIncentives = $this->getVendorDeliverymanIncentivesByBucket(period: $period);
        $taxes = $this->getTaxesByBucket(period: $period, sellerIs: self::SELLER_IS_SELLER);
        $refunds = $this->getRefundsByBucket(period: $period, paidBy: self::SELLER_IS_SELLER);

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $amounts = $orderAmounts->get($bucket);

            $chart[] = [
                'duration' => $duration,
                'total_earning' => (float) ($amounts->vendor_earning ?? 0)
                    + (float) ($amounts->shipping_earning ?? 0)
                    + (float) $taxes->get($bucket, 0)
                    - (float) ($amounts->discount_given ?? 0)
                    - (float) ($amounts->commission_given ?? 0)
                    - (float) $refunds->get($bucket, 0)
                    - (float) $deliverymanIncentives->get($bucket, 0),
            ];
        }

        return $chart;
    }

    /** @return array{rows: array<int, array<string, int|float|string>>, summary: array<string, int|float>} */
    public function getVendorEarningList(string $dateType, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $orderAmounts = $this->getVendorOrderAmountsBySeller(period: $period);
        $taxes = $this->getVendorTaxesBySeller(period: $period);
        $refunds = $this->getVendorRefundsBySeller(period: $period);
        $refundOnlySellerIds = $refunds->keys()->diff($orderAmounts->keys());
        $vendorNames = $this->getVendorNames(sellerIds: $orderAmounts->keys()->merge($refundOnlySellerIds)->all());

        $rows = [];
        foreach ($orderAmounts as $sellerId => $amounts) {
            $rows[] = $this->buildVendorEarningRow(
                sellerId: $sellerId,
                vendorName: $vendorNames->get($sellerId, ''),
                earnFromOrder: (float) $amounts->vendor_earning,
                earnFromShipping: (float) $amounts->shipping_earning,
                deliverymanIncentive: (float) $amounts->deliveryman_incentive,
                commissionGiven: (float) $amounts->commission_given,
                discountGiven: $vendorNames->has($sellerId) ? (float) $amounts->discount_given : 0.0,
                taxCollected: (float) $taxes->get($sellerId, 0),
                refundGiven: (float) $refunds->get($sellerId, 0),
            );
        }

        foreach ($refundOnlySellerIds as $sellerId) {
            $rows[] = $this->buildVendorEarningRow(
                sellerId: $sellerId,
                vendorName: $vendorNames->get($sellerId, ''),
                earnFromOrder: 0.0,
                earnFromShipping: 0.0,
                deliverymanIncentive: 0.0,
                commissionGiven: 0.0,
                discountGiven: 0.0,
                taxCollected: (float) $taxes->get($sellerId, 0),
                refundGiven: (float) $refunds->get($sellerId, 0),
            );
        }

        return [
            'rows' => $rows,
            'summary' => $this->summarizeVendorEarningRows(rows: $rows, activeVendorCount: $orderAmounts->count()),
        ];
    }

    public function getVendorSalesTotal(string $dateType, ?string $from = null, ?string $to = null): float
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        return (float) $this->deliveredOrdersQuery(period: $period)
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->selectRaw('COALESCE(SUM('.$this->vendorOrderEarningExpression().'), 0) AS vendor_sales', $this->vendorOrderEarningBindings())
            ->toBase()
            ->value('vendor_sales');
    }

    /** @return array<string, float> */
    public function getVendorWalletSummary(): array
    {
        $wallet = $this->sellerWallet->newQuery()
            ->selectRaw('COALESCE(SUM(total_earning), 0) AS total_earning, COALESCE(SUM(pending_withdraw), 0) AS pending_withdraw, COALESCE(SUM(withdrawn), 0) AS withdrawn')
            ->toBase()
            ->first();

        return [
            'wallet_amount' => (float) $wallet->total_earning,
            'withdrawable_balance' => (float) $wallet->total_earning - (float) $wallet->pending_withdraw,
            'pending_withdraw' => (float) $wallet->pending_withdraw,
            'already_withdrawn' => (float) $wallet->withdrawn,
        ];
    }

    private function deliveredOrdersQuery(array $period): Builder
    {
        return $this->query()
            ->where('order_status', self::ORDER_STATUS_DELIVERED)
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function getAdminOrderAmountsByBucket(array $period): Collection
    {
        return $this->deliveredOrdersQuery(period: $period)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN seller_is = ? THEN
                    COALESCE(order_amount, 0) + COALESCE(refer_and_earn_discount, 0) - COALESCE(total_tax_amount, 0) - COALESCE(shipping_cost, 0)
                    + CASE WHEN discount_type = ? THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + CASE WHEN free_delivery_bearer = ? AND is_shipping_free <> 0 THEN COALESCE(extra_discount, 0) ELSE 0 END
                ELSE 0 END), 0) AS in_house_earning',
                [self::SELLER_IS_ADMIN, self::DISCOUNT_TYPE_COUPON, self::FREE_DELIVERY_BEARER_ADMIN]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN seller_is = ? THEN COALESCE(admin_commission, 0) ELSE 0 END), 0) AS commission_earning',
                [self::SELLER_IS_SELLER]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_type = ? AND (seller_is = ? OR (seller_is = ? AND shipping_responsibility = ?)) THEN COALESCE(shipping_cost, 0) ELSE 0 END), 0) AS shipping_earning',
                [self::ORDER_TYPE_DEFAULT, self::SELLER_IS_ADMIN, self::SELLER_IS_SELLER, self::SHIPPING_RESPONSIBILITY_IN_HOUSE]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_type = ? THEN
                    CASE WHEN discount_type = ? AND (coupon_discount_bearer = ? OR seller_is = ?) THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + CASE WHEN is_shipping_free = 1 AND free_delivery_bearer = ? THEN COALESCE(extra_discount, 0) ELSE 0 END
                    + COALESCE(refer_and_earn_discount, 0)
                ELSE 0 END), 0) AS discount_given',
                [self::ORDER_TYPE_DEFAULT, self::DISCOUNT_TYPE_COUPON, self::COUPON_BEARER_IN_HOUSE, self::SELLER_IS_ADMIN, self::FREE_DELIVERY_BEARER_ADMIN]
            )
            ->groupBy('bucket')
            ->toBase()
            ->get()
            ->keyBy('bucket');
    }

    private function getAdminDeliverymanIncentivesByBucket(array $period): Collection
    {
        return $this->deliveredOrdersQuery(period: $period)
            ->where('order_type', self::ORDER_TYPE_DEFAULT)
            ->whereHas('deliveryMan', fn (Builder $query) => $query->where('seller_id', self::IN_HOUSE_DELIVERY_MAN_SELLER_ID))
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN delivery_type = ? AND shipping_responsibility = ? OR seller_is = ? THEN COALESCE(deliveryman_charge, 0) ELSE 0 END), 0) AS amount',
                [self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_IN_HOUSE, self::SELLER_IS_ADMIN]
            )
            ->groupBy('bucket')
            ->toBase()
            ->pluck('amount', 'bucket');
    }

    private function getTaxesByBucket(array $period, string $sellerIs): Collection
    {
        return $this->orderTransaction->newQuery()
            ->where(['status' => self::TRANSACTION_STATUS_DISBURSED, 'seller_is' => $sellerIs])
            ->whereHas('order', fn (Builder $query) => $query->businessModule($this->businessModule()))
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw('COALESCE(SUM(tax), 0) AS amount')
            ->groupBy('bucket')
            ->toBase()
            ->pluck('amount', 'bucket');
    }

    private function getRefundsByBucket(array $period, string $paidBy): Collection
    {
        return $this->refundTransaction->newQuery()
            ->where(['payment_status' => self::REFUND_PAYMENT_STATUS_PAID, 'paid_by' => $paidBy])
            ->whereHas('order', fn (Builder $query) => $query->businessModule($this->businessModule()))
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw('COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('bucket')
            ->toBase()
            ->pluck('amount', 'bucket');
    }

    private function vendorOrderEarningExpression(): string
    {
        return 'COALESCE(order_amount, 0) + COALESCE(refer_and_earn_discount, 0) - COALESCE(total_tax_amount, 0) - COALESCE(shipping_cost, 0)
            + CASE WHEN discount_type = ? AND coupon_discount_bearer IN (?, ?) THEN COALESCE(discount_amount, 0) ELSE 0 END
            + CASE WHEN is_shipping_free <> 0 AND free_delivery_bearer IN (?, ?) THEN COALESCE(extra_discount, 0) ELSE 0 END';
    }

    /** @return array<int, string> */
    private function vendorOrderEarningBindings(): array
    {
        return [
            self::DISCOUNT_TYPE_COUPON, self::COUPON_BEARER_IN_HOUSE, self::COUPON_BEARER_SELLER,
            self::FREE_DELIVERY_BEARER_SELLER, self::FREE_DELIVERY_BEARER_ADMIN,
        ];
    }

    private function getVendorOrderAmountsByBucket(array $period): Collection
    {
        return $this->deliveredOrdersQuery(period: $period)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN seller_is = ? THEN '.$this->vendorOrderEarningExpression().' ELSE 0 END), 0) AS vendor_earning',
                [self::SELLER_IS_SELLER, ...$this->vendorOrderEarningBindings()]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN seller_is = ? THEN COALESCE(admin_commission, 0) ELSE 0 END), 0) AS commission_given',
                [self::SELLER_IS_SELLER]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_type = ? AND seller_is = ? AND COALESCE(shipping_responsibility, '') <> ? THEN COALESCE(shipping_cost, 0) ELSE 0 END), 0) AS shipping_earning",
                [self::ORDER_TYPE_DEFAULT, self::SELLER_IS_SELLER, self::SHIPPING_RESPONSIBILITY_IN_HOUSE]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN order_type = ? THEN
                    CASE WHEN discount_type = ? AND seller_is = ? AND COALESCE(coupon_discount_bearer, '') <> ? THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + CASE WHEN is_shipping_free = 1 AND free_delivery_bearer = ? THEN COALESCE(extra_discount, 0) ELSE 0 END
                ELSE 0 END), 0) AS discount_given",
                [self::ORDER_TYPE_DEFAULT, self::DISCOUNT_TYPE_COUPON, self::SELLER_IS_SELLER, self::COUPON_BEARER_IN_HOUSE, self::FREE_DELIVERY_BEARER_SELLER]
            )
            ->groupBy('bucket')
            ->toBase()
            ->get()
            ->keyBy('bucket');
    }

    private function getVendorDeliverymanIncentivesByBucket(array $period): Collection
    {
        return $this->deliveredOrdersQuery(period: $period)
            ->where('order_type', self::ORDER_TYPE_DEFAULT)
            ->whereHas('deliveryMan', fn (Builder $query) => $query->where('seller_id', '!=', self::IN_HOUSE_DELIVERY_MAN_SELLER_ID))
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN delivery_type = ? AND shipping_responsibility = ? AND seller_is = ? THEN COALESCE(deliveryman_charge, 0) ELSE 0 END), 0) AS amount',
                [self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_SELLER_WISE, self::SELLER_IS_SELLER]
            )
            ->groupBy('bucket')
            ->toBase()
            ->pluck('amount', 'bucket');
    }

    private function getVendorOrderAmountsBySeller(array $period): Collection
    {
        return $this->deliveredOrdersQuery(period: $period)
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->select('seller_id')
            ->selectRaw('COALESCE(SUM('.$this->vendorOrderEarningExpression().'), 0) AS vendor_earning', $this->vendorOrderEarningBindings())
            ->selectRaw('COALESCE(SUM(COALESCE(admin_commission, 0)), 0) AS commission_given')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_type = ? AND shipping_responsibility = ? THEN
                    CASE WHEN COALESCE(is_shipping_free, 0) = 0 THEN COALESCE(shipping_cost, 0) WHEN is_shipping_free = 1 THEN COALESCE(extra_discount, 0) ELSE 0 END
                ELSE 0 END), 0) AS shipping_earning',
                [self::ORDER_TYPE_DEFAULT, self::SHIPPING_RESPONSIBILITY_SELLER_WISE]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN order_type = ? AND delivery_type = ? AND shipping_responsibility = ? THEN COALESCE(deliveryman_charge, 0) ELSE 0 END), 0) AS deliveryman_incentive',
                [self::ORDER_TYPE_DEFAULT, self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_SELLER_WISE]
            )
            ->selectRaw(
                'COALESCE(SUM(
                    CASE WHEN discount_type = ? AND coupon_discount_bearer = ? THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + CASE WHEN is_shipping_free = 1 AND free_delivery_bearer = ? THEN COALESCE(extra_discount, 0) ELSE 0 END
                ), 0) AS discount_given',
                [self::DISCOUNT_TYPE_COUPON, self::COUPON_BEARER_SELLER, self::FREE_DELIVERY_BEARER_SELLER]
            )
            ->groupBy('seller_id')
            ->orderByRaw('MAX(created_at) DESC')
            ->toBase()
            ->get()
            ->keyBy('seller_id');
    }

    private function getVendorTaxesBySeller(array $period): Collection
    {
        return $this->orderTransaction->newQuery()
            ->where(['status' => self::TRANSACTION_STATUS_DISBURSED, 'seller_is' => self::SELLER_IS_SELLER])
            ->whereHas('order', fn (Builder $query) => $query->businessModule($this->businessModule()))
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->selectRaw('seller_id, COALESCE(SUM(tax), 0) AS amount')
            ->groupBy('seller_id')
            ->toBase()
            ->pluck('amount', 'seller_id');
    }

    private function getVendorRefundsBySeller(array $period): Collection
    {
        return $this->refundTransaction->newQuery()
            ->where(['payment_status' => self::REFUND_PAYMENT_STATUS_PAID, 'paid_by' => self::SELLER_IS_SELLER])
            ->whereHas('order', fn (Builder $query) => $query->businessModule($this->businessModule()))
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->selectRaw('payer_id, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('payer_id')
            ->orderByRaw('MAX(created_at) DESC')
            ->toBase()
            ->pluck('amount', 'payer_id');
    }

    private function getVendorNames(array $sellerIds): Collection
    {
        return $this->seller->newQuery()
            ->whereIn('id', $sellerIds)
            ->toBase()
            ->get(['id', 'f_name', 'l_name'])
            ->mapWithKeys(fn (object $seller): array => [$seller->id => $seller->f_name.' '.$seller->l_name]);
    }

    private function getOrderEditPayments(array $period): object
    {
        return $this->orderEditHistory->newQuery()
            ->whereIn('order_id', $this->deliveredOrdersQuery(period: $period)->select('id'))
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

    /** @return array<string, int|float|string> */
    private function buildAdminEarningRow(
        int|string $duration,
        float $inHouseEarning,
        float $commissionEarning,
        float $shippingEarning,
        float $deliverymanIncentive,
        float $discountGiven,
        float $vatTax,
        float $refundGiven,
    ): array {
        return [
            'duration' => $duration,
            'in_house_earning' => $inHouseEarning,
            'commission_earning' => $commissionEarning,
            'earn_from_shipping' => $shippingEarning,
            'deliveryman_incentive' => $deliverymanIncentive,
            'discount_given' => $discountGiven,
            'vat_tax' => $vatTax,
            'refund_given' => $refundGiven,
            'total_earning' => $inHouseEarning + $commissionEarning + $shippingEarning + $vatTax - $discountGiven - $refundGiven - $deliverymanIncentive,
        ];
    }

    /** @return array<string, float> */
    private function summarizeAdminEarningRows(array $rows): array
    {
        $rows = collect($rows);

        return [
            'total_earning' => (float) $rows->sum('total_earning'),
            'total_in_house_earning' => (float) $rows->sum('in_house_earning'),
            'total_commission' => (float) $rows->sum('commission_earning'),
            'total_shipping_earning' => (float) $rows->sum('earn_from_shipping'),
        ];
    }

    /** @return array<string, int|float|string> */
    private function buildVendorEarningRow(
        int|string $sellerId,
        string $vendorName,
        float $earnFromOrder,
        float $earnFromShipping,
        float $deliverymanIncentive,
        float $commissionGiven,
        float $discountGiven,
        float $taxCollected,
        float $refundGiven,
    ): array {
        return [
            'vendor_id' => $sellerId,
            'vendor_info' => $vendorName,
            'earn_from_order' => $earnFromOrder,
            'earn_from_shipping' => $earnFromShipping,
            'deliveryman_incentive' => $deliverymanIncentive,
            'commission_given' => $commissionGiven,
            'discount_given' => $discountGiven,
            'tax_collected' => $taxCollected,
            'refund_given' => $refundGiven,
            'total_earning' => $earnFromOrder + $earnFromShipping + $taxCollected - $discountGiven - $refundGiven - $commissionGiven - $deliverymanIncentive,
        ];
    }

    /** @return array<string, int|float> */
    private function summarizeVendorEarningRows(array $rows, int $activeVendorCount): array
    {
        $rows = collect($rows);
        $activeVendorEarning = (float) $rows->take($activeVendorCount)->sum('total_earning');

        return [
            'total_earning' => (float) $rows->sum('total_earning'),
            'active_vendor_count' => $activeVendorCount,
            'average_vendor_revenue' => $activeVendorCount > 0 ? $activeVendorEarning / $activeVendorCount : 0.0,
        ];
    }
}
