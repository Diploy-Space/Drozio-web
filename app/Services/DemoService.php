<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * REFERENCE ONLY — the shape every service is written to. Nothing extends or resolves this class.
 * Copy the methods your entity has, keep their names and parameter lists, delete the rest.
 * Rules, and which method to include when: Modules/Wholesale/docs/SERVICE-ARCHITECTURE.md.
 */
final class DemoService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    public function __construct(Brand $demo)
    {
        parent::__construct($demo);
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
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue))
                    ->orWhereIn('id', $this->translatedIds($searchValue));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NAME_ASC => $query->orderBy('name'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = ['translations']): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = ['translations']): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return DB::transaction(function () use ($data): Model {
            // Strict null check only: a plain array_filter would drop a legitimate 0, false or ''.
            $record = $this->query()->create(array_filter([
                'name' => $this->defaultLocaleValue($data, 'name'),
                'status' => isset($data['status']) ? (int) $data['status'] : null,
            ], static fn ($value) => $value !== null));

            $this->syncTranslations($record->id, $data);

            return $record;
        });
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data): Model {
            // Strict null check only, so an edit form posting a subset never blanks stored values.
            $record->update(array_filter([
                'name' => $this->defaultLocaleValue($data, 'name'),
                'status' => isset($data['status']) ? (int) $data['status'] : null,
            ], static fn ($value) => $value !== null));

            $this->syncTranslations($record->id, $data);

            return $record->refresh();
        });
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $query = $this->query();

        $this->applyFilters($query, $params);

        $record = $query->first();

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data): Model {
            $record->update(array_filter([
                'name' => $this->defaultLocaleValue($data, 'name'),
                'status' => isset($data['status']) ? (int) $data['status'] : null,
            ], static fn ($value) => $value !== null));

            $this->syncTranslations($record->id, $data);

            return $record->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    /**
     * Update multiple records matching the given conditions.
     *
     * @return int Number of affected records.
     */
    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    /** @return array{active: int, inactive: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%"));

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

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($record): bool {
            $this->deleteTranslations([$record->id]);

            return (bool) $record->delete();
        });
    }

    public function deleteBy(array $params): int
    {
        return DB::transaction(function () use ($params): int {
            $matched = $this->query();
            $this->applyFilters($matched, $params);
            $this->deleteTranslations($matched->select('id'));

            $records = $this->query();
            $this->applyFilters($records, $params);

            return $records->delete();
        });
    }

    public function exists(array $params): bool
    {
        return $this->query()
            ->where($params)
            ->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
