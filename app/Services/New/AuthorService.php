<?php

namespace App\Services\New;

use App\Models\Author;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AuthorService extends BaseService
{
    public const UNKNOWN_ID = 0;

    public const UNKNOWN_NAME = 'Unknown';

    private const PRODUCT_COUNT_ALIAS = 'digital_product_author_count';

    public function __construct(Author $author)
    {
        parent::__construct($author);
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
        bool $includeWithoutProducts = false,
    ): Collection {
        $owned = fn (Builder $product): Builder => $product
            ->active()
            ->where('product_type', ProductService::PRODUCT_TYPE_DIGITAL)
            ->when($productIds !== [], fn (Builder $inner) => $inner->whereIn('id', $productIds))
            ->when($sellerId, fn (Builder $inner) => $inner
                ->where('user_id', $sellerId)
                ->where('added_by', ProductService::ADDED_BY_SELLER));

        $list = $this->query()
            ->withCount(['digitalProductAuthor' => fn (Builder $builder) => $builder
                ->whereHas('product', fn (Builder $product) => $owned($product)
                    ->when($clearanceProductIds !== null, fn (Builder $inner) => $inner->whereIn('id', $clearanceProductIds))
                    ->when($flashDealOnly, fn (Builder $inner) => $inner->whereHas('flashDealProducts.flashDeal')))])
            ->when(! $includeWithoutProducts, fn (Builder $builder) => $builder
                ->whereHas('digitalProductAuthor.product', fn (Builder $product) => $owned($product)))
            ->orderBy('name')
            ->get()
            ->sortByDesc(self::PRODUCT_COUNT_ALIAS);

        if ($unattachedProductCount > 0) {
            $list->push($this->unknownAuthor($unattachedProductCount));
        }

        return $list;
    }

    /** @return array<int, int> */
    public function getMatchingProductIds(string $keyword): array
    {
        return $this->query()
            ->where('name', 'like', "%{$keyword}%")
            ->with(['digitalProductAuthor'])
            ->get()
            ->flatMap(fn (Model $author) => $author->digitalProductAuthor->pluck('product_id'))
            ->all();
    }

    public function getAttachedProductIds(array $ownerIds = []): array
    {
        return $this->query()
            ->when($ownerIds !== [], fn (Builder $query) => $query->whereIn('id', $ownerIds))
            ->with(['digitalProductAuthor'])
            ->get()
            ->flatMap(fn (Model $author) => $author->digitalProductAuthor->pluck('product_id'))
            ->unique()
            ->values()
            ->all();
    }

    private function unknownAuthor(int $productCount): Model
    {
        $unknown = new Author([
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
