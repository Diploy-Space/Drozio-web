<?php

namespace App\Services\New\Deal;

use App\Models\FlashDealProduct;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FlashDealProductService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public function __construct(FlashDealProduct $flashDealProduct)
    {
        parent::__construct($flashDealProduct);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('product_id', $searchValue)
                    ->orWhere('flash_deal_id', $searchValue);
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest('id'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function deleteBy(array $params): int
    {
        return $this->query()->where($params)->delete();
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /** @return array<int, int> */
    public function getProductIds(mixed $dealId): array
    {
        return $this->query()->where('flash_deal_id', $dealId)->pluck('product_id')->all();
    }

    public function attach(mixed $dealId, array $productIds, ?float $discount = null, ?string $discountType = null): int
    {
        $newProductIds = array_diff(array_unique($productIds), $this->getProductIds($dealId));

        if ($newProductIds === []) {
            return 0;
        }

        $rows = array_map(static fn (mixed $productId): array => [
            'flash_deal_id' => $dealId,
            'product_id' => $productId,
            'discount' => $discount ?? 0,
            'discount_type' => $discountType ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], array_values($newProductIds));

        $this->query()->insert($rows);
        cacheRemoveByType(type: 'products');

        return count($rows);
    }

    public function detach(mixed $dealId, mixed $productId): bool
    {
        $isDetached = (bool) $this->query()
            ->where('flash_deal_id', $dealId)
            ->where('product_id', $productId)
            ->delete();

        if ($isDetached) {
            cacheRemoveByType(type: 'products');
        }

        return $isDetached;
    }
}
