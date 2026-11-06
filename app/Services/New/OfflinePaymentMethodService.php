<?php

namespace App\Services\New;

use App\Models\OfflinePaymentMethod;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OfflinePaymentMethodService extends BaseService
{
    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NEWEST_ID = 'newest-id';

    public function __construct(OfflinePaymentMethod $offlinePaymentMethod)
    {
        parent::__construct($offlinePaymentMethod);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('method_name', 'like', "%{$searchValue}%"))
            ->when($sortBy === self::SORT_LATEST, fn (Builder $builder) => $builder->latest())
            ->when($sortBy === self::SORT_NAME_ASC, fn (Builder $builder) => $builder->orderBy('method_name'))
            ->when($sortBy === self::SORT_NEWEST_ID, fn (Builder $builder) => $builder->orderByDesc('id'));

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->appends(['searchValue' => $searchValue]);
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
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerInputValues(Model $method, array $input): array
    {
        $values = [];

        foreach (array_column($method->method_informations ?? [], 'customer_input') as $field) {
            if (array_key_exists($field, $input)) {
                $values[$field] = $input[$field];
            }
        }

        return $values;
    }

    public function hasActiveMethod(): bool
    {
        return $this->query()->where('status', self::STATUS_ACTIVE)->exists();
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where('method_name', 'like', "%{$searchValue}%"));

        $this->applyFilters($query, $filters);

        $counts = $query->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts[self::STATUS_ACTIVE] ?? 0),
            'inactive' => (int) ($counts[self::STATUS_INACTIVE] ?? 0),
        ];
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
}
