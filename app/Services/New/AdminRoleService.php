<?php

namespace App\Services\New;

use App\Models\AdminRole;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdminRoleService extends BaseService
{
    public const SUPER_ADMIN_ROLE_ID = 1;

    public const FILTER_EXCLUDE_SUPER_ADMIN = 'exclude_super_admin';

    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_NAME_ASC = 'a-z';

    public function __construct(AdminRole $adminRole)
    {
        parent::__construct($adminRole);
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
        $excludesSuperAdmin = ! empty($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);
        unset($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->when($excludesSuperAdmin, fn (Builder $builder) => $builder->whereKeyNot(self::SUPER_ADMIN_ROLE_ID));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            default => $query->latest(),
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

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'name' => $data['name'],
            'module_access' => $data['module_access'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : 1,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'name' => $data['name'] ?? null,
            'module_access' => $data['module_access'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }

    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $excludesSuperAdmin = ! empty($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);
        unset($filters[self::FILTER_EXCLUDE_SUPER_ADMIN]);

        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"))
            ->when($excludesSuperAdmin, fn (Builder $builder) => $builder->whereKeyNot(self::SUPER_ADMIN_ROLE_ID));

        $this->applyFilters($query, $filters);

        $counts = $query->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'active' => (int) ($counts[1] ?? 0),
            'inactive' => (int) ($counts[0] ?? 0),
        ];
    }

    public function countEmployees(mixed $id): int
    {
        return (int) $this->query()->where('id', $id)->withCount('employees')->value('employees_count');
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record === null ? false : (bool) $record->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
