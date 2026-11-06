<?php

namespace App\Services\New;

use App\Models\NotificationMessage;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotificationMessageService extends BaseService
{
    public const SORT_KEY_ASC = 'key-asc';

    public function __construct(NotificationMessage $notificationMessage)
    {
        parent::__construct($notificationMessage);
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
                $inner->where('key', 'like', "%{$searchValue}%")
                    ->orWhere('message', 'like', "%{$searchValue}%");
            }));

        match ($sortBy) {
            self::SORT_KEY_ASC => $query->orderBy('key'),
            default => $query->latest('id'),
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
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create(array_filter([
            'business_module' => $data['business_module'] ?? null,
            'user_type' => $data['user_type'] ?? null,
            'key' => $data['key'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => isset($data['status']) ? (int) $data['status'] : null,
        ], static fn ($value) => $value !== null));
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        return DB::transaction(function () use ($record, $data): Model {
            $record->update(array_filter([
                'message' => $this->defaultLocaleValue($data, 'message'),
                'status' => isset($data['status']) ? (int) $data['status'] : null,
            ], static fn ($value) => $value !== null));

            $this->syncTranslations(
                $record->id,
                ['lang' => $data['lang'] ?? [], $record->key => $data['message'] ?? []],
                [$record->key],
                skipBlankValues: true,
            );

            return $record->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => (int) $status]);
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

        return (bool) DB::transaction(function () use ($record): bool {
            $this->deleteTranslations([$record->id]);

            return (bool) $record->delete();
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
