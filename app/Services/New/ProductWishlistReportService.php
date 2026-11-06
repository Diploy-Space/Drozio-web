<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductWishlistReportService extends BaseService
{
    public const SELLER_ALL = 'all';

    public const SELLER_IN_HOUSE = 'in_house';

    public const SORT_WISHLIST_HIGH_TO_LOW = 'DESC';

    public const SORT_WISHLIST_LOW_TO_HIGH = 'ASC';

    public const SORTS = [self::SORT_WISHLIST_HIGH_TO_LOW, self::SORT_WISHLIST_LOW_TO_HIGH];

    private const ADDED_BY_ADMIN = 'admin';

    private const ADDED_BY_SELLER = 'seller';

    private const REQUEST_STATUS_APPROVED = 1;

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

    public function getProductWishlistList(
        int|string|null $sellerId = null,
        ?string $searchValue = null,
        string $sortBy = self::SORT_WISHLIST_HIGH_TO_LOW,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->where('request_status', self::REQUEST_STATUS_APPROVED)
            ->when($sellerId === self::SELLER_IN_HOUSE, fn (Builder $builder) => $builder->where('added_by', self::ADDED_BY_ADMIN))
            ->when(is_int($sellerId), fn (Builder $builder) => $builder->where(['added_by' => self::ADDED_BY_SELLER, 'user_id' => $sellerId]))
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->withCount('wishList')
            ->orderBy('wish_list_count', $sortBy === self::SORT_WISHLIST_LOW_TO_HIGH ? 'asc' : 'desc')
            ->orderBy('id');

        $toRow = fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'thumbnail_full_url' => $product->thumbnail_full_url,
            'added_by' => $product->added_by,
            'created_at' => $product->created_at,
            'wishlist_count' => (int) $product->wish_list_count,
        ];

        return $dataLimit === 'all'
            ? $query->get()->toBase()->map($toRow)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->through($toRow);
    }
}
