<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InhouseSaleReportService extends BaseService
{
    public const FILTER_CATEGORY_ID = 'category_id';

    private const ADDED_BY_ADMIN = 'admin';

    private const DELIVERY_STATUS_DELIVERED = 'delivered';

    public function __construct(
        Product $product,
        private readonly OrderDetail $orderDetail,
    ) {
        parent::__construct($product);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()
            ->businessModule($this->businessModule())
            ->where('added_by', self::ADDED_BY_ADMIN);
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
            ->withSum(['orderDetails as total_sold_quantity' => fn (Builder $detail) => $detail->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)], 'qty')
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->when(isset($filters[self::FILTER_CATEGORY_ID]), fn (Builder $builder) => $builder->where('category_id', $filters[self::FILTER_CATEGORY_ID]))
            ->orderByDesc('id');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    /** @return array{total_sale_amount: float, total_order_count: int, average_order_value: float, total_customer_count: int} */
    public function getSaleSummary(array $filters = []): array
    {
        $summary = $this->orderDetail->newQuery()
            ->toBase()
            ->join('products', 'products.id', '=', 'order_details.product_id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('order_details.delivery_status', self::DELIVERY_STATUS_DELIVERED)
            ->where('products.business_module', $this->businessModule())
            ->where('products.added_by', self::ADDED_BY_ADMIN)
            ->when(isset($filters[self::FILTER_CATEGORY_ID]), fn ($builder) => $builder->where('products.category_id', $filters[self::FILTER_CATEGORY_ID]))
            ->select([
                DB::raw('COALESCE(SUM(order_details.price * order_details.qty - order_details.discount), 0) AS total_sale_amount'),
                DB::raw('COUNT(DISTINCT order_details.order_id) AS total_order_count'),
                DB::raw('COUNT(DISTINCT orders.is_guest, orders.customer_id) AS total_customer_count'),
            ])
            ->first();

        $totalSaleAmount = (float) $summary->total_sale_amount;
        $totalOrderCount = (int) $summary->total_order_count;

        return [
            'total_sale_amount' => $totalSaleAmount,
            'total_order_count' => $totalOrderCount,
            'average_order_value' => $totalOrderCount > 0 ? $totalSaleAmount / $totalOrderCount : 0.0,
            'total_customer_count' => (int) $summary->total_customer_count,
        ];
    }
}
