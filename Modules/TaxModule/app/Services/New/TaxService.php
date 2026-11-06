<?php

namespace Modules\TaxModule\app\Services\New;

use App\Enums\BusinessModule;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\TaxModule\app\Models\Tax;

class TaxService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    private const COUNTRY_TYPE_SINGLE = 'single';

    public function __construct(Tax $tax)
    {
        parent::__construct($tax);
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
                foreach (explode(' ', $searchValue) as $keyword) {
                    $inner->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('id', 'like', "%{$keyword}%")
                        ->orWhere('tax_rate', 'like', "%{$keyword}%");
                }
            }));

        match ($sortBy) {
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
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

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($this->columns($data) + [
            'business_module' => $data['business_module'] ?? BusinessModule::RETAIL,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($this->columns($data));

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->update(['is_active' => $status]);
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

    private function columns(array $data): array
    {
        $columns = [
            'name' => $data['name'],
            'tax_rate' => $data['tax_rate'],
            'is_default' => true,
            'is_active' => $data['status'] ?? 0,
        ];

        if (! $this->isSingleCountry()) {
            $columns['country_code'] = $data['country_code'] ?? null;
            $columns['is_default'] = false;
        }

        return $columns;
    }

    private function isSingleCountry(): bool
    {
        return config('taxmodule.country_type') === self::COUNTRY_TYPE_SINGLE;
    }
}
