<?php

namespace App\Services\New;

use App\Models\ProductCompare;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductCompareService extends BaseService
{
    public const MAX_ITEMS = 3;

    public const FILTER_HAS_PRODUCT = 'has_product';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public function __construct(ProductCompare $productCompare)
    {
        parent::__construct($productCompare);
    }

    /**
     * @return array<int, int>
     */
    public function getProductIds(int $customerId): array
    {
        return $this->query()
            ->whereHas('product')
            ->where('user_id', $customerId)
            ->pluck('product_id')
            ->all();
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
        $query = $this->query()->with($relations)->withCount($withCount);

        if (! empty($filters[self::FILTER_HAS_PRODUCT])) {
            $query->whereHas('product');
        }

        unset($filters[self::FILTER_HAS_PRODUCT]);

        $this->applyFilters($query, $filters);

        match ($sortBy) {
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            default => null,
        };

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function addWithinLimit(?int $customerId, int $productId): Model
    {
        return DB::transaction(function () use ($customerId, $productId): Model {
            $this->makeRoomForNewItem(customerId: $customerId);

            return $this->query()->create(['user_id' => $customerId, 'product_id' => $productId]);
        });
    }

    public function toggleForCustomer(int $customerId, int|string $productId): bool
    {
        return DB::transaction(function () use ($customerId, $productId): bool {
            $existing = $this->query()->where(['user_id' => $customerId, 'product_id' => $productId])->first();

            if ($existing !== null) {
                $existing->delete();

                return false;
            }

            $this->makeRoomForNewItem(customerId: $customerId);

            $this->create(data: ['user_id' => $customerId, 'product_id' => $productId]);

            return true;
        });
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(['product_id' => $data['product_id']]);

        return $record->refresh();
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->query()->whereKey($id)->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function deleteBy(array $params): int
    {
        $query = $this->query();

        $this->applyFilters($query, $params);

        return $query->delete();
    }

    private function makeRoomForNewItem(?int $customerId): void
    {
        $query = $this->query()->whereHas('product');

        $this->applyFilters($query, ['user_id' => $customerId]);

        $evictedIds = $query->orderByDesc('id')->pluck('id')->slice(self::MAX_ITEMS - 1);

        if ($evictedIds->isNotEmpty()) {
            $this->query()->whereKey($evictedIds->all())->delete();
        }
    }
}
