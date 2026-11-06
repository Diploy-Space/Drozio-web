<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VendorSaleReportService extends BaseService
{
    use ReportPeriodTrait;

    private const SELLER_IS_SELLER = 'seller';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    private const COUPON_BEARER_IN_HOUSE = 'inhouse';

    private const FREE_DELIVERY_BEARER_ADMIN = 'admin';

    private const SHIPPING_RESPONSIBILITY_SELLER_WISE = 'sellerwise_shipping';

    private const DELIVERY_TYPE_SELF = 'self_delivery';

    private const REFUND_PAID_BY_SELLER = 'seller';

    private const REFUND_TRANSACTION_TYPE = 'Refund';

    private const REFUND_PAYMENT_STATUS_PAID = 'paid';

    public function __construct(
        Order $order,
        private readonly RefundTransaction $refundTransaction,
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

    public function getVendorSaleList(
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        ?int $sellerId = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->deliveredVendorOrdersQuery(period: $period, sellerId: $sellerId)
            ->with(['seller.shop'])
            ->select('seller_id')
            ->selectRaw('COUNT(*) AS total_order_count, SUM(order_amount) AS total_order_amount, SUM(admin_commission) AS total_admin_commission')
            ->when($searchValue, fn (Builder $builder) => $this->applyVendorSearch(query: $builder, searchValue: $searchValue))
            ->groupBy('seller_id');

        $vendorSales = $dataLimit === 'all' ? $query->get() : $query->paginate(perPage: (int) $dataLimit, page: $page);
        $isPaginated = $vendorSales instanceof LengthAwarePaginator;
        $sellerIds = ($isPaginated ? $vendorSales->getCollection() : $vendorSales)->pluck('seller_id')->all();
        $refunds = $this->getRefundsBySeller(period: $period, sellerIds: $sellerIds);

        $toRow = fn (Order $vendorSale): array => $this->buildVendorSaleRow(vendorSale: $vendorSale, refundAmount: $refunds->get($vendorSale->seller_id));

        return $isPaginated ? $vendorSales->through($toRow) : $vendorSales->map($toRow);
    }

    /** @return array<int, array{duration: int|string, order_amount: float}> */
    public function getOrderAmountChart(string $dateType, ?string $from = null, ?string $to = null, ?int $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $amounts = $this->query()
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->when($dateType !== self::DATE_TYPE_TODAY, fn (Builder $builder) => $builder->where('order_status', self::ORDER_STATUS_DELIVERED))
            ->when($sellerId !== null, fn (Builder $builder) => $builder->where('seller_id', $sellerId))
            ->whereBetween('created_at', [$period['start'], $period['end']])
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

    /** @return array{total_store_earning: float, total_order_amount: float, delivered_order_count: int, active_vendor_count: int, average_vendor_revenue: float, average_order_value: float} */
    public function getVendorSaleSummary(string $dateType, ?string $from = null, ?string $to = null, ?int $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $orderTotals = $this->deliveredVendorOrdersQuery(period: $period, sellerId: $sellerId)
            ->selectRaw(
                'COALESCE(SUM(order_amount), 0) AS total_order_amount,
                COALESCE(SUM(COALESCE(order_amount, 0) + COALESCE(refer_and_earn_discount, 0) - COALESCE(shipping_cost, 0)
                    + CASE WHEN discount_type = ? AND coupon_discount_bearer = ? THEN COALESCE(discount_amount, 0) ELSE 0 END
                    + CASE WHEN is_shipping_free <> 0 AND free_delivery_bearer = ? THEN COALESCE(extra_discount, 0) ELSE 0 END
                    + CASE WHEN order_type = ? AND shipping_responsibility = ? THEN
                        CASE WHEN COALESCE(is_shipping_free, 0) = 0 THEN COALESCE(shipping_cost, 0) ELSE COALESCE(extra_discount, 0) END
                    ELSE 0 END
                    - CASE WHEN order_type = ? AND delivery_type = ? AND shipping_responsibility = ? THEN COALESCE(deliveryman_charge, 0) ELSE 0 END
                ), 0) AS earn_from_order,
                COALESCE(SUM(admin_commission), 0) AS admin_commission,
                COUNT(*) AS delivered_order_count,
                COUNT(DISTINCT seller_id) AS active_vendor_count',
                [
                    self::DISCOUNT_TYPE_COUPON, self::COUPON_BEARER_IN_HOUSE,
                    self::FREE_DELIVERY_BEARER_ADMIN,
                    self::ORDER_TYPE_DEFAULT, self::SHIPPING_RESPONSIBILITY_SELLER_WISE,
                    self::ORDER_TYPE_DEFAULT, self::DELIVERY_TYPE_SELF, self::SHIPPING_RESPONSIBILITY_SELLER_WISE,
                ]
            )
            ->toBase()
            ->first();

        $totalOrderAmount = (float) $orderTotals->total_order_amount;
        $deliveredOrderCount = (int) $orderTotals->delivered_order_count;
        $activeVendorCount = (int) $orderTotals->active_vendor_count;

        return [
            'total_store_earning' => (float) $orderTotals->earn_from_order
                - (float) $orderTotals->admin_commission
                - $this->getPaidRefundAmount(period: $period, sellerId: $sellerId),
            'total_order_amount' => $totalOrderAmount,
            'delivered_order_count' => $deliveredOrderCount,
            'active_vendor_count' => $activeVendorCount,
            'average_vendor_revenue' => $activeVendorCount > 0 ? $totalOrderAmount / $activeVendorCount : 0.0,
            'average_order_value' => $deliveredOrderCount > 0 ? $totalOrderAmount / $deliveredOrderCount : 0.0,
        ];
    }

    private function deliveredVendorOrdersQuery(array $period, ?int $sellerId): Builder
    {
        return $this->query()
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'order_status' => self::ORDER_STATUS_DELIVERED])
            ->when($sellerId !== null, fn (Builder $builder) => $builder->where('seller_id', $sellerId))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function applyVendorSearch(Builder $query, string $searchValue): void
    {
        $query->where(function (Builder $inner) use ($searchValue): void {
            $inner->whereHas('seller.shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"))
                ->orWhereHas('seller', fn (Builder $seller) => $seller->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("CONCAT(l_name, ' ', f_name) LIKE ?", ["%{$searchValue}%"]));
        });
    }

    private function getRefundsBySeller(array $period, array $sellerIds): Collection
    {
        if ($sellerIds === []) {
            return collect();
        }

        return $this->refundQuery(period: $period)
            ->where('transaction_type', self::REFUND_TRANSACTION_TYPE)
            ->whereIn('payer_id', $sellerIds)
            ->selectRaw('payer_id, SUM(amount) AS amount')
            ->groupBy('payer_id')
            ->toBase()
            ->pluck('amount', 'payer_id');
    }

    private function getPaidRefundAmount(array $period, ?int $sellerId): float
    {
        return (float) $this->refundQuery(period: $period)
            ->where('payment_status', self::REFUND_PAYMENT_STATUS_PAID)
            ->when($sellerId !== null, fn (Builder $builder) => $builder->where('payer_id', $sellerId))
            ->sum('amount');
    }

    private function refundQuery(array $period): Builder
    {
        return $this->refundTransaction->newQuery()
            ->where('paid_by', self::REFUND_PAID_BY_SELLER)
            ->whereHas('order', fn (Builder $order) => $order->businessModule($this->businessModule()))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    /** @return array{seller_id: int, shop_name: ?string, is_verified: bool, vendor_name: ?string, total_order_count: int, total_order_amount: float, total_admin_commission: float, refund_rate: ?float} */
    private function buildVendorSaleRow(Order $vendorSale, mixed $refundAmount): array
    {
        $totalOrderAmount = (float) $vendorSale->total_order_amount;

        return [
            'seller_id' => (int) $vendorSale->seller_id,
            'shop_name' => $vendorSale->seller?->shop?->name,
            'is_verified' => (bool) $vendorSale->seller?->hasVerifiedBadge(),
            'vendor_name' => $vendorSale->seller ? $vendorSale->seller->f_name.' '.$vendorSale->seller->l_name : null,
            'total_order_count' => (int) $vendorSale->total_order_count,
            'total_order_amount' => $totalOrderAmount,
            'total_admin_commission' => (float) $vendorSale->total_admin_commission,
            'refund_rate' => $refundAmount === null ? null : ($totalOrderAmount > 0 ? (float) $refundAmount / $totalOrderAmount * 100 : 0.0),
        ];
    }
}
