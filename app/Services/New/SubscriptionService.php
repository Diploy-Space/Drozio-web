<?php

namespace App\Services\New;

use App\Models\Subscription;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionService extends BaseService
{
    public const SORT_NEWEST = 'desc';

    public const SORT_OLDEST = 'asc';

    public const FILTER_SUBSCRIBED_BETWEEN = 'subscribed_between';

    public const FILTER_LIMIT = 'limit';

    public function __construct(Subscription $subscription)
    {
        parent::__construct($subscription);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginatorContract {
        $subscribedBetween = $filters[self::FILTER_SUBSCRIBED_BETWEEN] ?? [];
        $limit = $filters[self::FILTER_LIMIT] ?? null;
        unset($filters[self::FILTER_SUBSCRIBED_BETWEEN], $filters[self::FILTER_LIMIT]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('email', 'like', "%{$searchValue}%"))
            ->when($subscribedBetween, fn (Builder $builder) => $builder->whereBetween('created_at', $subscribedBetween));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest(),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        if ($dataLimit === 'all') {
            return $limit ? $query->limit((int) $limit)->get() : $query->get();
        }

        return ($limit
            ? $this->paginateFirstRecords($query, (int) $limit, (int) $dataLimit, $page)
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
        )->appends($this->listQueryString($searchValue, $sortBy, $filters));
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
        return $this->query()->create(['email' => $data['email']]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'email' => $data['email'] ?? null,
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

    private function paginateFirstRecords(Builder $query, int $limit, int $dataLimit, ?int $page): LengthAwarePaginator
    {
        $records = $query->limit($limit)->get();
        $currentPage = $page ?? LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            items: $records->forPage($currentPage, $dataLimit)->values(),
            total: $records->count(),
            perPage: $dataLimit,
            currentPage: $currentPage,
            options: ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }
}
