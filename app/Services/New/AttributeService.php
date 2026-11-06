<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Attribute;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AttributeService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NAME_DESC = 'z-a';

    public const SORT_NEWEST_ID = 'newest-id';

    public function __construct(Attribute $attribute)
    {
        parent::__construct($attribute);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()
            ->withoutGlobalScopes()
            ->businessModule($this->businessModule());
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
            self::SORT_LATEST => $query->latest()->orderByDesc('id'),
            self::SORT_OLDEST => $query->oldest()->orderBy('id'),
            self::SORT_NAME_ASC => $query->orderBy('name')->orderBy('id'),
            self::SORT_NAME_DESC => $query->orderByDesc('name')->orderByDesc('id'),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            default => $query->orderBy('id'),
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

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return DB::transaction(function () use ($data): Model {
            $columns = ['business_module' => $this->businessModule()];

            if ($name = $this->defaultLocaleValue($data, 'name')) {
                $columns['name'] = $name;
            }

            $attribute = $this->query()->create($columns);

            $this->syncTranslations($attribute->id, $data);

            return $attribute;
        });
    }

    public function update(mixed $id, array $data): ?Model
    {
        $attribute = $this->find(id: $id);

        if ($attribute === null) {
            return null;
        }

        return DB::transaction(function () use ($attribute, $data): Model {
            if ($name = $this->defaultLocaleValue($data, 'name')) {
                $attribute->update(['name' => $name]);
            }

            $this->syncTranslations($attribute->id, $data);

            return $attribute->refresh();
        });
    }

    public function getIds(array $filters = []): array
    {
        return $this->applyFilters($this->query(), $filters)->pluck('id')->toArray();
    }

    public function getNameMap(): array
    {
        return $this->query()->pluck('name', 'id')->all();
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
        $attribute = $this->find(id: $id);

        if ($attribute === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($attribute): bool {
            $this->deleteTranslations([$attribute->id]);

            return (bool) $attribute->delete();
        });
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
