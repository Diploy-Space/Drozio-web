<?php

namespace App\Services\New;

use App\Models\PublishingHouse;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PublishingHouseService extends BaseService
{
    public const UNKNOWN_ID = 0;

    public const UNKNOWN_NAME = 'Unknown';

    private const PRODUCT_COUNT_ALIAS = 'publishing_house_products_count';

    public function __construct(PublishingHouse $publishingHouse)
    {
        parent::__construct($publishingHouse);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"));

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function getListRankedByProducts(
        int $unattachedProductCount = 0,
        array $productIds = [],
        ?int $sellerId = null,
        ?array $clearanceProductIds = null,
        bool $flashDealOnly = false,
    ): Collection {
        $owned = fn (Builder $product): Builder => $product
            ->active()
            ->where('product_type', ProductService::PRODUCT_TYPE_DIGITAL)
            ->when($productIds !== [], fn (Builder $inner) => $inner->whereIn('id', $productIds))
            ->when($sellerId, fn (Builder $inner) => $inner
                ->where('user_id', $sellerId)
                ->where('added_by', ProductService::ADDED_BY_SELLER));

        $list = $this->query()
            ->with(['publishingHouseProducts.product'])
            ->withCount(['publishingHouseProducts' => fn (Builder $builder) => $builder
                ->whereHas('product', fn (Builder $product) => $owned($product)
                    ->when($clearanceProductIds !== null, fn (Builder $inner) => $inner->whereIn('id', $clearanceProductIds))
                    ->when($flashDealOnly, fn (Builder $inner) => $inner->whereHas('flashDealProducts.flashDeal')))])
            ->when($productIds !== [], fn (Builder $builder) => $builder
                ->whereHas('publishingHouseProducts.product', fn (Builder $product) => $owned($product)))
            ->get()
            ->sortByDesc(self::PRODUCT_COUNT_ALIAS);

        if ($unattachedProductCount > 0) {
            $list->push($this->unknownPublishingHouse($unattachedProductCount));
        }

        return $list;
    }

    /** @return array<int, int> */
    public function getMatchingProductIds(string $keyword): array
    {
        return $this->query()
            ->where('name', 'like', "%{$keyword}%")
            ->with(['publishingHouseProducts'])
            ->get()
            ->flatMap(fn (Model $publishingHouse) => $publishingHouse->publishingHouseProducts->pluck('product_id'))
            ->all();
    }

    public function getAttachedProductIds(array $ownerIds = []): array
    {
        return $this->query()
            ->when($ownerIds !== [], fn (Builder $query) => $query->whereIn('id', $ownerIds))
            ->with(['publishingHouseProducts'])
            ->get()
            ->flatMap(fn (Model $house) => $house->publishingHouseProducts->pluck('product_id'))
            ->unique()
            ->values()
            ->all();
    }

    private function unknownPublishingHouse(int $productCount): Model
    {
        $unknown = new PublishingHouse([
            'name' => self::UNKNOWN_NAME,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unknown->id = self::UNKNOWN_ID;
        $unknown->{self::PRODUCT_COUNT_ALIAS} = $productCount;

        return $unknown;
    }

    public function resolveIds(array $names): array
    {
        $ids = [];

        foreach (array_filter($names) as $name) {
            $ids[] = $this->query()->updateOrCreate(['name' => $name], ['name' => $name])->id;
        }

        return $ids;
    }
}
