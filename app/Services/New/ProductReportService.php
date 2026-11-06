<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Services\BaseService;
use App\Traits\ReportPeriodTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductReportService extends BaseService
{
    use ReportPeriodTrait;

    public const SELLER_ALL = 'all';

    public const SELLER_IN_HOUSE = 'inhouse';

    private const IN_HOUSE_USER_ID = 1;

    private const ADDED_BY_ADMIN = 'admin';

    private const ADDED_BY_SELLER = 'seller';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const PRODUCT_STATUS_ACTIVE = 1;

    private const DELIVERY_STATUS_DELIVERED = 'delivered';

    private const REQUEST_STATUS_PENDING = 0;

    private const REQUEST_STATUS_ACTIVE = 1;

    private const REQUEST_STATUS_REJECTED = 2;

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
        return parent::query()->businessModule($this->businessModule());
    }

    public function getProductSaleList(
        string $dateType,
        ?string $from = null,
        ?string $to = null,
        int|string|null $sellerId = null,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $query = $this->productsQuery(period: $period, sellerId: $sellerId)
            ->withSum(['orderDetails as total_sold_amount' => fn (Builder $detail) => $detail->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)], DB::raw('qty * price'))
            ->withSum(['orderDetails as total_sold_quantity' => fn (Builder $detail) => $detail->where('delivery_status', self::DELIVERY_STATUS_DELIVERED)], 'qty')
            ->withAvg(['reviews as average_rating' => fn (Builder $review) => $review->whereNull('delivery_man_id')], 'rating')
            ->withCount('reviews')
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->latest('created_at');

        $toRow = fn (Product $product): array => $this->buildProductSaleRow(product: $product);

        return $dataLimit === 'all'
            ? $query->get()->toBase()->map($toRow)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($toRow);
    }

    /** @return array{total_product_count: int, active_product_count: int, pending_product_count: int, rejected_product_count: int} */
    public function getProductStatusCounts(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $counts = $this->productsQuery(period: $period, sellerId: $sellerId)
            ->selectRaw(
                'COUNT(CASE WHEN request_status = ? THEN 1 END) AS active_product_count,
                COUNT(CASE WHEN request_status = ? THEN 1 END) AS pending_product_count,
                COUNT(CASE WHEN request_status = ? THEN 1 END) AS rejected_product_count',
                [self::REQUEST_STATUS_ACTIVE, self::REQUEST_STATUS_PENDING, self::REQUEST_STATUS_REJECTED]
            )
            ->toBase()
            ->first();

        $activeProductCount = (int) $counts->active_product_count;
        $pendingProductCount = (int) $counts->pending_product_count;
        $rejectedProductCount = (int) $counts->rejected_product_count;

        return [
            'total_product_count' => $activeProductCount + $pendingProductCount + $rejectedProductCount,
            'active_product_count' => $activeProductCount,
            'pending_product_count' => $pendingProductCount,
            'rejected_product_count' => $rejectedProductCount,
        ];
    }

    /** @return array{total_sold_quantity: int, total_discount: float} */
    public function getProductSaleSummary(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $soldProductIds = $this->productsQuery(period: $period, sellerId: $sellerId)
            ->whereHas('orderDetails', fn (Builder $detail) => $detail->where('delivery_status', self::DELIVERY_STATUS_DELIVERED))
            ->select('id');

        $summary = $this->orderDetail->newQuery()
            ->toBase()
            ->whereIn('product_id', $soldProductIds)
            ->selectRaw('COALESCE(SUM(qty), 0) AS total_sold_quantity, COALESCE(SUM(discount), 0) AS total_discount')
            ->first();

        return [
            'total_sold_quantity' => (int) $summary->total_sold_quantity,
            'total_discount' => (float) $summary->total_discount,
        ];
    }

    /** @return array<int, array{duration: int|string, product_count: int}> */
    public function getProductCountChart(string $dateType, ?string $from = null, ?string $to = null, int|string|null $sellerId = null): array
    {
        $period = $this->resolvePeriod(dateType: $dateType, from: $from, to: $to);

        $counts = $this->productsQuery(period: $period, sellerId: $sellerId)
            ->selectRaw($this->bucketExpression(period: $period).' AS bucket, COUNT(*) AS product_count')
            ->groupBy('bucket')
            ->toBase()
            ->pluck('product_count', 'bucket');

        $chart = [];
        foreach ($period['buckets'] as $bucket => $duration) {
            $chart[] = ['duration' => $duration, 'product_count' => (int) $counts->get($bucket, 0)];
        }

        return $chart;
    }

    private function productsQuery(array $period, int|string|null $sellerId): Builder
    {
        return $this->query()
            ->when($sellerId === self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where(['user_id' => self::IN_HOUSE_USER_ID, 'added_by' => self::ADDED_BY_ADMIN]))
            ->when(is_int($sellerId), fn (Builder $builder) => $builder->where(['user_id' => $sellerId, 'added_by' => self::ADDED_BY_SELLER]))
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    /** @return array{id: int, name: string, thumbnail_full_url: mixed, added_by: string, unit_price: float, total_sold_amount: float, total_sold_quantity: int, average_sold_price: float, is_digital: bool, is_active: bool, current_stock: int, average_rating: float, review_count: int} */
    private function buildProductSaleRow(Product $product): array
    {
        $totalSoldAmount = (float) $product->total_sold_amount;
        $totalSoldQuantity = (int) $product->total_sold_quantity;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'thumbnail_full_url' => $product->thumbnail_full_url,
            'added_by' => $product->added_by,
            'unit_price' => (float) $product->unit_price,
            'total_sold_amount' => $totalSoldAmount,
            'total_sold_quantity' => $totalSoldQuantity,
            'average_sold_price' => $totalSoldQuantity > 0 ? $totalSoldAmount / $totalSoldQuantity : $totalSoldAmount,
            'is_digital' => $product->product_type === self::PRODUCT_TYPE_DIGITAL,
            'is_active' => (int) $product->status === self::PRODUCT_STATUS_ACTIVE,
            'current_stock' => (int) $product->current_stock,
            'average_rating' => (float) $product->average_rating,
            'review_count' => (int) $product->reviews_count,
        ];
    }
}
