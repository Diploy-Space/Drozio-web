<?php

namespace App\Services\New;

use App\Models\VendorRegistrationReason;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VendorRegistrationReasonService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_PRIORITY = 'priority';

    public const SORT_PRIORITY_DESC = 'priority-desc';

    public function __construct(VendorRegistrationReason $vendorRegistrationReason)
    {
        parent::__construct($vendorRegistrationReason);
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
                $inner->where('title', 'like', "%{$searchValue}%")
                    ->when(is_numeric($searchValue), fn (Builder $query) => $query->orWhere('id', $searchValue));
            }));

        match ($sortBy) {
            self::SORT_PRIORITY => $query->orderBy('priority'),
            self::SORT_PRIORITY_DESC => $query->orderByDesc('priority'),
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

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }
}
