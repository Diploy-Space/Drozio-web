<?php

namespace App\Services\New;

use App\Models\VendorWithdrawMethodInfo;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VendorWithdrawMethodInfoService extends BaseService
{
    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    public function __construct(VendorWithdrawMethodInfo $vendorWithdrawMethodInfo)
    {
        parent::__construct($vendorWithdrawMethodInfo);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('method_name', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NAME_ASC => $query->orderBy('method_name'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = ['withdraw_method'], ?int $userId = null): ?Model
    {
        return $this->ownedQuery($userId)->with($relations)->find($id);
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

    public function create(array $data, array $methodFields = [], ?int $userId = null): ?Model
    {
        return DB::transaction(function () use ($data, $methodFields, $userId): Model {
            $record = $this->query()->create([
                'user_id' => $userId,
                'withdraw_method_id' => $data['withdraw_method_id'],
                'method_name' => $data['method_name'],
                'method_info' => $this->resolveMethodInfo($data['method_info'] ?? [], $methodFields),
                'is_active' => (int) ($data['status'] ?? self::STATUS_INACTIVE),
            ]);

            if ($this->ownedQuery($userId)->count() === 1) {
                $this->ownedQuery($userId)->update(['is_default' => 1, 'is_active' => self::STATUS_ACTIVE]);
            }

            return $record->refresh();
        });
    }

    public function update(mixed $id, array $data, array $methodFields = [], ?int $userId = null): ?Model
    {
        $record = $this->ownedQuery($userId)->find($id);

        if ($record === null) {
            return null;
        }

        $record->update([
            'withdraw_method_id' => $data['withdraw_method_id'],
            'method_name' => $data['method_name'],
            'method_info' => $this->resolveMethodInfo($data['method_info'] ?? [], $methodFields),
            'is_active' => array_key_exists('status', $data) ? (int) $data['status'] : (int) $record->is_active,
        ]);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status, ?int $userId = null): bool
    {
        $record = $this->ownedQuery($userId)->find($id);

        if ($record === null) {
            return false;
        }

        $record->update(['is_active' => (int) $status]);

        return true;
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function markAsDefault(mixed $id, ?int $userId = null): bool
    {
        $record = $this->ownedQuery($userId)->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($record, $userId): bool {
            $this->ownedQuery($userId)->update(['is_default' => 0]);

            $record->update(['is_default' => 1, 'is_active' => self::STATUS_ACTIVE]);

            return true;
        });
    }

    public function delete(mixed $id, ?int $userId = null): bool
    {
        $record = $this->ownedQuery($userId)->find($id);

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

    private function ownedQuery(?int $userId): Builder
    {
        return $this->query()->when($userId, fn (Builder $builder) => $builder->where('user_id', $userId));
    }

    private function resolveMethodInfo(array $methodInfo, array $methodFields): array
    {
        $resolved = [];

        foreach ($methodFields as $field) {
            $resolved[$field['input_name']] = $methodInfo[$field['input_name']] ?? '';
        }

        return $resolved;
    }
}
