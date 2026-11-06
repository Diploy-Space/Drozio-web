<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\OrderTransaction;
use App\Services\BaseService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class TaxReportService extends BaseService
{
    public const SOURCE_ADMIN_COMMISSION = 'admin_commission';

    public const SOURCE_DELIVERY_CHARGE = 'delivery_charge';

    public const SOURCES = [self::SOURCE_ADMIN_COMMISSION, self::SOURCE_DELIVERY_CHARGE];

    public const CALCULATE_ON_ALL_SOURCE = 'all_source';

    public const CALCULATE_ON_INDIVIDUAL_SOURCE = 'individual_source';

    public const CALCULATE_ON_OPTIONS = [self::CALCULATE_ON_ALL_SOURCE, self::CALCULATE_ON_INDIVIDUAL_SOURCE];

    public const DATE_RANGE_THIS_FISCAL_YEAR = 'this_fiscal_year';

    public const DATE_RANGE_CUSTOM = 'custom';

    public const DATE_RANGE_TYPES = [self::DATE_RANGE_THIS_FISCAL_YEAR, self::DATE_RANGE_CUSTOM];

    private const TRANSACTION_STATUS_DISBURSED = 'disburse';

    private const DELIVERED_BY_ADMIN = 'admin';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const SELLER_IS_ADMIN = 'admin';

    private const SHIPPING_RESPONSIBILITY_IN_HOUSE = 'inhouse_shipping';

    private const TRANSACTION_COLUMNS = ['id', 'order_id', 'order_amount', 'transaction_id', 'admin_commission', 'delivery_charge', 'created_at', 'updated_at'];

    private const ORDER_TAXES_TABLE = 'order_taxes';

    private const TAX_FOR_ORDERS = 'tax_for_orders';

    public function __construct(OrderTransaction $orderTransaction)
    {
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

    /** @return array<string, Collection> */
    public function groupTaxRatesBySource(Collection $taxes, string $calculateTaxOn, array $taxRateIds = [], array $orderCommissionTaxIds = [], array $deliveryChargeTaxIds = []): array
    {
        $taxesById = $taxes->keyBy('id');

        if ($calculateTaxOn === self::CALCULATE_ON_ALL_SOURCE) {
            return [
                self::SOURCE_ADMIN_COMMISSION => $taxesById->only($taxRateIds),
                self::SOURCE_DELIVERY_CHARGE => $taxesById->only($taxRateIds),
            ];
        }

        return [
            self::SOURCE_ADMIN_COMMISSION => $taxesById->only($orderCommissionTaxIds),
            self::SOURCE_DELIVERY_CHARGE => $taxesById->only($deliveryChargeTaxIds),
        ];
    }

    /** @return array<int, array{type: string, amount: float, total_tax_percentage: float|int, total_tax_amount: float|int, taxes: array<int, array{name: string, tax_rate: float|int, applicable_amount: float}>}> */
    public function getAdminTaxSummary(CarbonInterface $from, CarbonInterface $to, array $sourceTaxRates, ?string $searchValue = null): array
    {
        $amounts = $this->sourceTransactionsQuery(from: $from, to: $to, searchValue: $searchValue)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN '.$this->sourceCondition(source: self::SOURCE_ADMIN_COMMISSION).' THEN '.$this->sourceColumn(source: self::SOURCE_ADMIN_COMMISSION).' ELSE 0 END), 0) AS '.self::SOURCE_ADMIN_COMMISSION.',
                COALESCE(SUM(CASE WHEN '.$this->sourceCondition(source: self::SOURCE_DELIVERY_CHARGE).' THEN '.$this->sourceColumn(source: self::SOURCE_DELIVERY_CHARGE).' ELSE 0 END), 0) AS '.self::SOURCE_DELIVERY_CHARGE,
                [...$this->sourceBindings(source: self::SOURCE_ADMIN_COMMISSION), ...$this->sourceBindings(source: self::SOURCE_DELIVERY_CHARGE)]
            )
            ->toBase()
            ->first();

        $reportList = [];
        foreach (self::SOURCES as $source) {
            $reportList[] = $this->buildSourceTaxRow(source: $source, amount: (float) $amounts->{$source}, taxRates: $sourceTaxRates[$source] ?? collect());
        }

        return $reportList;
    }

    /** @return array{order_count: int, order_amount: float} */
    public function getAdminTaxSourceTotals(string $source, CarbonInterface $from, CarbonInterface $to, ?string $searchValue = null): array
    {
        $totals = $this->sourceTransactionsQuery(from: $from, to: $to, searchValue: $searchValue)
            ->whereRaw($this->sourceCondition(source: $source), $this->sourceBindings(source: $source))
            ->selectRaw('COUNT(*) AS order_count, COALESCE(SUM('.$this->transactionColumn(column: 'order_amount').'), 0) AS order_amount')
            ->toBase()
            ->first();

        return [
            'order_count' => (int) $totals->order_count,
            'order_amount' => (float) $totals->order_amount,
        ];
    }

    public function getAdminTaxSourceTransactions(
        string $source,
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->sourceTransactionsQuery(from: $from, to: $to, searchValue: $searchValue)
            ->whereRaw($this->sourceCondition(source: $source), $this->sourceBindings(source: $source))
            ->select(array_map(fn (string $column): string => $this->transactionColumn(column: $column), self::TRANSACTION_COLUMNS))
            ->orderByDesc($this->transactionColumn(column: 'updated_at'));

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @return array{total_orders: int, total_order_amount: float, total_tax: float} */
    public function getVendorTaxSummary(CarbonInterface $from, CarbonInterface $to, ?int $shopId = null, ?string $searchValue = null): array
    {
        return $this->summarizeVendorTaxQuery(query: $this->vendorTaxShopQuery(from: $from, to: $to, shopId: $shopId, searchValue: $searchValue));
    }

    public function getVendorTaxShopList(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $shopId = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): SupportCollection|LengthAwarePaginator {
        $query = $this->vendorTaxShopQuery(from: $from, to: $to, shopId: $shopId, searchValue: $searchValue)
            ->toBase()
            ->select($this->transactionColumn(column: 'shop_id'))
            ->selectRaw('COUNT(*) AS total_orders, COALESCE(SUM('.$this->transactionColumn(column: 'order_amount').'), 0) AS total_order_amount, COALESCE(SUM('.$this->transactionColumn(column: 'tax').'), 0) AS total_tax')
            ->groupBy($this->transactionColumn(column: 'shop_id'))
            ->orderByRaw('MAX('.$this->transactionColumn(column: 'updated_at').') DESC, MIN('.$this->transactionColumn(column: 'id').') ASC');

        $toRow = fn (object $row): array => [
            'shop_id' => $row->shop_id === null ? null : (int) $row->shop_id,
            'total_orders' => (int) $row->total_orders,
            'total_order_amount' => (float) $row->total_order_amount,
            'total_tax' => (float) $row->total_tax,
        ];

        if ($dataLimit === 'all') {
            return $query->get()->map($toRow);
        }

        $total = DB::query()->fromSub($query, 'vendor_tax_shops')->count();
        $page = max(1, (int) ($page ?? 1));

        return new LengthAwarePaginator(
            items: $query->forPage($page, (int) $dataLimit)->get()->map($toRow),
            total: $total,
            perPage: (int) $dataLimit,
            currentPage: $page,
        );
    }

    /** @return array<int|string, array<string, array<string, float>>> */
    public function getVendorTaxBreakdownByShop(CarbonInterface $from, CarbonInterface $to, array $shopIds, ?string $searchValue = null): array
    {
        if ($shopIds === []) {
            return [];
        }

        $taxes = self::ORDER_TAXES_TABLE;

        return $this->vendorTaxShopQuery(from: $from, to: $to, shopId: null, searchValue: $searchValue)
            ->whereIn($this->transactionColumn(column: 'shop_id'), $shopIds)
            ->join($taxes, $taxes.'.order_id', '=', $this->transactionColumn(column: 'order_id'))
            ->where($taxes.'.tax_for', self::TAX_FOR_ORDERS)
            ->toBase()
            ->groupBy($this->transactionColumn(column: 'shop_id'), $taxes.'.tax_on', $taxes.'.tax_name')
            ->orderByRaw('MAX('.$this->transactionColumn(column: 'updated_at').') DESC, MIN('.$taxes.'.id) ASC')
            ->get([$this->transactionColumn(column: 'shop_id'), $taxes.'.tax_on', $taxes.'.tax_name', DB::raw('SUM('.$taxes.'.tax_amount) AS total_amount')])
            ->groupBy('shop_id')
            ->map(fn (SupportCollection $shopTaxes): array => $shopTaxes->groupBy('tax_on')
                ->sortKeys()
                ->map(fn (SupportCollection $taxOnGroup): array => $taxOnGroup->mapWithKeys(fn (object $tax): array => [$tax->tax_name => (float) $tax->total_amount])->all())
                ->all())
            ->all();
    }

    public function getVendorTaxShopTransactions(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $shopId = null,
        ?string $searchValue = null,
        array $relations = [],
    ): Collection {
        return $this->vendorTaxShopQuery(from: $from, to: $to, shopId: $shopId, searchValue: $searchValue)
            ->select($this->model->getTable().'.*')
            ->with($relations)
            ->orderByDesc($this->transactionColumn(column: 'updated_at'))
            ->get();
    }

    /** @return array{total_orders: int, total_order_amount: float, total_tax: float} */
    public function getVendorTaxOrderSummary(int $shopId, CarbonInterface $from, CarbonInterface $to, ?string $searchValue = null): array
    {
        return $this->summarizeVendorTaxQuery(query: $this->vendorTaxOrderQuery(shopId: $shopId, from: $from, to: $to, searchValue: $searchValue));
    }

    public function getVendorTaxOrderList(
        int $shopId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $searchValue = null,
        array $relations = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->vendorTaxOrderQuery(shopId: $shopId, from: $from, to: $to, searchValue: $searchValue)
            ->select($this->model->getTable().'.*')
            ->with($relations)
            ->orderByDesc($this->transactionColumn(column: 'updated_at'));

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    private function vendorTaxShopQuery(CarbonInterface $from, CarbonInterface $to, ?int $shopId, ?string $searchValue): Builder
    {
        return $this->vendorTaxBaseQuery(from: $from, to: $to, shopId: $shopId)
            ->when(! empty($searchValue), fn (Builder $query) => $query->where(function (Builder $search) use ($searchValue): void {
                $search->whereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%")->orWhere('contact', 'like', "%{$searchValue}%"))
                    ->orWhereHas('orderTaxes', fn (Builder $orderTax) => $orderTax->where('tax_name', 'like', "%{$searchValue}%"));
            }));
    }

    private function vendorTaxOrderQuery(int $shopId, CarbonInterface $from, CarbonInterface $to, ?string $searchValue): Builder
    {
        return $this->vendorTaxBaseQuery(from: $from, to: $to, shopId: $shopId)
            ->whereHas('order', fn (Builder $order) => $order->where('order_type', self::ORDER_TYPE_DEFAULT))
            ->when(! empty($searchValue), fn (Builder $query) => $query->where(function (Builder $search) use ($searchValue): void {
                $search->where($this->transactionColumn(column: 'order_id'), 'like', "%{$searchValue}%")
                    ->orWhereHas('orderTaxes', fn (Builder $orderTax) => $orderTax->where('tax_name', 'like', "%{$searchValue}%"));
            }));
    }

    private function vendorTaxBaseQuery(CarbonInterface $from, CarbonInterface $to, ?int $shopId): Builder
    {
        return $this->query()
            ->where($this->transactionColumn(column: 'status'), self::TRANSACTION_STATUS_DISBURSED)
            ->whereHas('order', fn (Builder $order) => $order->where('order_status', self::ORDER_STATUS_DELIVERED))
            ->when($shopId !== null, fn (Builder $query) => $query->where($this->transactionColumn(column: 'shop_id'), $shopId))
            ->whereBetween($this->transactionColumn(column: 'updated_at'), [$from, $to]);
    }

    /** @return array{total_orders: int, total_order_amount: float, total_tax: float} */
    private function summarizeVendorTaxQuery(Builder $query): array
    {
        $totals = $query->toBase()
            ->selectRaw('COUNT(*) AS total_orders, COALESCE(SUM('.$this->transactionColumn(column: 'order_amount').'), 0) AS total_order_amount, COALESCE(SUM('.$this->transactionColumn(column: 'tax').'), 0) AS total_tax')
            ->first();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_order_amount' => (float) $totals->total_order_amount,
            'total_tax' => (float) $totals->total_tax,
        ];
    }

    private function sourceTransactionsQuery(CarbonInterface $from, CarbonInterface $to, ?string $searchValue): Builder
    {
        return $this->query()
            ->join('orders', 'orders.id', '=', $this->transactionColumn(column: 'order_id'))
            ->where($this->transactionColumn(column: 'status'), self::TRANSACTION_STATUS_DISBURSED)
            ->where($this->transactionColumn(column: 'delivered_by'), self::DELIVERED_BY_ADMIN)
            ->where('orders.order_status', self::ORDER_STATUS_DELIVERED)
            ->where('orders.order_type', self::ORDER_TYPE_DEFAULT)
            ->when(! empty($searchValue), fn (Builder $query) => $query->where(function (Builder $search) use ($searchValue): void {
                $search->where($this->transactionColumn(column: 'transaction_id'), 'like', "%{$searchValue}%")
                    ->orWhere('orders.id', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderTaxes', fn (Builder $orderTax) => $orderTax->where('tax_name', 'like', "%{$searchValue}%"));
            }))
            ->whereBetween($this->transactionColumn(column: 'updated_at'), [$from, $to]);
    }

    private function sourceCondition(string $source): string
    {
        return match ($source) {
            self::SOURCE_ADMIN_COMMISSION => $this->sourceColumn(source: $source).' > 0',
            self::SOURCE_DELIVERY_CHARGE => '('.$this->transactionColumn(column: 'seller_is').' = ? OR orders.shipping_responsibility = ?) AND '.$this->sourceColumn(source: $source).' > 0',
        };
    }

    private function sourceBindings(string $source): array
    {
        return match ($source) {
            self::SOURCE_ADMIN_COMMISSION => [],
            self::SOURCE_DELIVERY_CHARGE => [self::SELLER_IS_ADMIN, self::SHIPPING_RESPONSIBILITY_IN_HOUSE],
        };
    }

    private function sourceColumn(string $source): string
    {
        return $this->transactionColumn(column: $source);
    }

    private function transactionColumn(string $column): string
    {
        return $this->model->getTable().'.'.$column;
    }

    private function buildSourceTaxRow(string $source, float $amount, SupportCollection $taxRates): array
    {
        $taxes = $taxRates->map(fn ($tax): array => [
            'name' => $tax['name'],
            'tax_rate' => $tax['tax_rate'],
            'applicable_amount' => ($tax['tax_rate'] * $amount) / 100,
        ])->values()->all();

        return [
            'type' => $source,
            'amount' => $amount,
            'total_tax_percentage' => $taxRates->sum('tax_rate'),
            'total_tax_amount' => collect($taxes)->sum('applicable_amount'),
            'taxes' => $taxes,
        ];
    }
}
