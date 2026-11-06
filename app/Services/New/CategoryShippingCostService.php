<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\CategoryShippingCost;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CategoryShippingCostService extends BaseService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const IN_HOUSE_SELLER_ID = 0;

    public function __construct(CategoryShippingCost $categoryShippingCost)
    {
        parent::__construct($categoryShippingCost);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()
            ->where('context', $this->businessModule())
            ->whereHas('category', fn (Builder $category) => $category->businessModule($this->businessModule()));
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
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas(
                'category',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$searchValue}%"),
            ));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest('id'),
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
        return $this->query()->create([
            'seller_id' => $data['seller_id'] ?? self::IN_HOUSE_SELLER_ID,
            'category_id' => $data['category_id'],
            'cost' => $data['cost'] ?? 0,
            'context' => $this->businessModule(),
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'cost' => isset($data['cost']) ? currencyConverter($data['cost'], 'usd') : null,
            'multiply_qty' => isset($data['multiply_qty']) ? (int) $data['multiply_qty'] : null,
        ], static fn ($value) => $value !== null));

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

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record === null ? false : (bool) $record->delete();
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
}
