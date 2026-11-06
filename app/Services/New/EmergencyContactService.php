<?php

namespace App\Services\New;

use App\Models\EmergencyContact;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EmergencyContactService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const ADMIN_USER_ID = 0;

    public function __construct(EmergencyContact $emergencyContact)
    {
        parent::__construct($emergencyContact);
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
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            }));

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
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'country_code' => $data['country_code'] ?? null,
            'phone' => $data['phone'],
            'status' => isset($data['status']) ? (int) $data['status'] : 1,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $contact = $this->query()->find($id);

        if ($contact === null) {
            return null;
        }

        $contact->update(array_filter([
            'name' => $data['name'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], static fn ($value) => $value !== null));

        return $contact->refresh();
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
        $contact = $this->query()->find($id);

        if ($contact === null) {
            return false;
        }

        return (bool) $contact->delete();
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

    public function phoneExistsForUser(?string $countryCode, ?string $phone, mixed $userId, mixed $excludeId = null): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('country_code', $countryCode)
            ->where('phone', $phone)
            ->when($excludeId, fn (Builder $builder) => $builder->whereKeyNot($excludeId))
            ->exists();
    }
}
