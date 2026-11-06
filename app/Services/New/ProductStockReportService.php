<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductStockReportService extends BaseService
{
    public const SELLER_ALL = 'all';

    public const SELLER_IN_HOUSE = 'in_house';

    public const SORT_STOCK_LOW_TO_HIGH = 'ASC';

    public const SORT_STOCK_HIGH_TO_LOW = 'DESC';

    public const SORTS = [self::SORT_STOCK_LOW_TO_HIGH, self::SORT_STOCK_HIGH_TO_LOW];

    public const STOCK_STATUS_IN_STOCK = 'in_stock';

    public const STOCK_STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const STOCK_STATUS_SOON_STOCK_OUT = 'soon_stock_out';

    private const ADDED_BY_ADMIN = 'admin';

    private const ADDED_BY_SELLER = 'seller';

    private const PRODUCT_TYPE_PHYSICAL = 'physical';

    public function __construct(Product $product)
    {
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

    public function getProductStockList(
        int $stockLimit,
        int|string|null $sellerId = null,
        ?int $categoryId = null,
        ?string $searchValue = null,
        string $sortBy = self::SORT_STOCK_LOW_TO_HIGH,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->where('product_type', self::PRODUCT_TYPE_PHYSICAL)
            ->when($sellerId === null, fn (Builder $builder) => $builder->whereIn('added_by', [self::ADDED_BY_ADMIN, self::ADDED_BY_SELLER]))
            ->when($sellerId === self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where('added_by', self::ADDED_BY_ADMIN))
            ->when(is_int($sellerId), fn (Builder $builder) => $builder->where(['added_by' => self::ADDED_BY_SELLER, 'user_id' => $sellerId]))
            ->when($categoryId !== null, fn (Builder $builder) => $builder->whereJsonContains('category_ids', ['id' => (string) $categoryId]))
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->orderBy('current_stock', $sortBy === self::SORT_STOCK_HIGH_TO_LOW ? 'desc' : 'asc')
            ->orderBy('id');

        $toRow = fn (Product $product): array => $this->buildProductStockRow(product: $product, stockLimit: $stockLimit);

        return $dataLimit === 'all'
            ? $query->get()->toBase()->map($toRow)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($toRow);
    }

    /** @return array{id: int, name: string, thumbnail_full_url: mixed, added_by: string, updated_at: mixed, current_stock: int, stock_status: string} */
    private function buildProductStockRow(Product $product, int $stockLimit): array
    {
        $currentStock = (int) $product->current_stock;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'thumbnail_full_url' => $product->thumbnail_full_url,
            'added_by' => $product->added_by,
            'updated_at' => $product->updated_at,
            'current_stock' => $currentStock,
            'stock_status' => match (true) {
                $currentStock > $stockLimit => self::STOCK_STATUS_IN_STOCK,
                $currentStock <= 0 => self::STOCK_STATUS_OUT_OF_STOCK,
                default => self::STOCK_STATUS_SOON_STOCK_OUT,
            },
        ];
    }
}
