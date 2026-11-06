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

class VatReportService extends BaseService
{
    public const FILTER_SHOP_ID = 'shop_id';

    private const STATUS_DISBURSE = 'disburse';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const ORDER_TYPE_DEFAULT = 'default_type';

    private const TAX_FOR_ORDERS = 'tax_for_orders';

    private const ORDER_TAXES_TABLE = 'order_taxes';

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

    public function getTransactionList(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        array $filters = [],
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->filteredQuery(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $searchValue)
            ->with($relations)
            ->orderByDesc($this->model->qualifyColumn('created_at'));

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @return array{total_orders: int, total_order_amount: float, total_tax: float} */
    public function getSummary(CarbonInterface $startDate, CarbonInterface $endDate, array $filters = [], ?string $searchValue = null): array
    {
        $totals = $this->filteredQuery(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $searchValue)
            ->toBase()
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(order_amount), 0) as total_order_amount, COALESCE(SUM(tax), 0) as total_tax')
            ->first();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_order_amount' => (float) $totals->total_order_amount,
            'total_tax' => (float) $totals->total_tax,
        ];
    }

    /** @return array<int, array{tax_on: string, rows: array<int, array{name: string, tax_rate: float, total_amount: float}>}> */
    public function getTaxBreakdown(CarbonInterface $startDate, CarbonInterface $endDate, array $filters = [], ?string $searchValue = null): array
    {
        $taxes = self::ORDER_TAXES_TABLE;

        return $this->filteredQuery(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $searchValue)
            ->join($taxes, $taxes.'.order_id', '=', $this->model->qualifyColumn('order_id'))
            ->where($taxes.'.tax_for', self::TAX_FOR_ORDERS)
            ->groupBy($taxes.'.tax_on', $taxes.'.tax_name', $taxes.'.tax_rate')
            ->orderByRaw('MAX('.$this->model->qualifyColumn('created_at').') DESC, MIN('.$taxes.'.id) ASC')
            ->toBase()
            ->get([$taxes.'.tax_on', $taxes.'.tax_name', $taxes.'.tax_rate', DB::raw('SUM('.$taxes.'.tax_amount) as total_amount')])
            ->groupBy('tax_on')
            ->sortKeys()
            ->map(fn (SupportCollection $taxOnGroup, string $taxOn): array => [
                'tax_on' => $taxOn,
                'rows' => $taxOnGroup->groupBy('tax_name')
                    ->map(fn (SupportCollection $rates, string $taxName): array => [
                        'name' => $taxName,
                        'tax_rate' => (float) $rates->first()->tax_rate,
                        'total_amount' => (float) $rates->sum('total_amount'),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function filteredQuery(CarbonInterface $startDate, CarbonInterface $endDate, array $filters, ?string $searchValue): Builder
    {
        $query = $this->query()
            ->where($this->model->qualifyColumn('status'), self::STATUS_DISBURSE)
            ->whereHas('order', fn (Builder $order) => $order->where(['order_status' => self::ORDER_STATUS_DELIVERED, 'order_type' => self::ORDER_TYPE_DEFAULT]))
            ->whereBetween($this->model->qualifyColumn('updated_at'), [$startDate, $endDate])
            ->when($searchValue, fn (Builder $query) => $query->where(fn (Builder $search) => $search
                ->where($this->model->qualifyColumn('order_id'), 'like', "%{$searchValue}%")
                ->orWhereHas('orderTaxes', fn (Builder $orderTax) => $orderTax->where('tax_name', 'like', "%{$searchValue}%"))));

        $this->applyFilters($query, collect($filters)->mapWithKeys(fn (mixed $value, string $column): array => [$this->model->qualifyColumn($column) => $value])->all());

        return $query;
    }
}
