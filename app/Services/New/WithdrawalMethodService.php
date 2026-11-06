<?php

namespace App\Services\New;

use App\Models\WithdrawalMethod;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WithdrawalMethodService extends BaseService
{
    public const STATUS_ACTIVE = 1;

    public const STATUS_INACTIVE = 0;

    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    public const USER_TYPE_VENDOR = 'vendor';

    public const USER_TYPE_CUSTOMER = 'customer';

    public const FILTER_USER_TYPE = 'user_type';

    public function __construct(WithdrawalMethod $withdrawalMethod)
    {
        parent::__construct($withdrawalMethod);
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
        $userType = $filters[self::FILTER_USER_TYPE] ?? null;
        unset($filters[self::FILTER_USER_TYPE]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($userType, fn (Builder $builder) => $builder->where($this->statusColumn($userType), self::STATUS_ACTIVE))
            ->when($searchValue, fn (Builder $builder) => $builder->where('method_name', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NAME_ASC => $query->orderBy('method_name'),
            default => $query,
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

    public function findPayableBy(mixed $id, string $userType): ?Model
    {
        return $this->query()
            ->where($this->statusColumn($userType), self::STATUS_ACTIVE)
            ->where('is_active', self::STATUS_ACTIVE)
            ->find($id);
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data, string $userType): ?Model
    {
        return DB::transaction(function () use ($data, $userType): Model {
            $isDefault = ! empty($data['is_default']) || ! $this->hasAnyMethod($userType);

            $method = $this->query()->create([
                'method_name' => $data['method_name'],
                'method_fields' => $this->buildMethodFields($data),
                'is_default' => $isDefault,
                'is_active' => self::STATUS_ACTIVE,
                'vendor_status' => $userType === self::USER_TYPE_VENDOR ? 1 : 0,
                'customer_status' => $userType === self::USER_TYPE_CUSTOMER ? 1 : 0,
            ]);

            if ($isDefault) {
                $this->clearDefaultOfOthers(id: $method->id, userType: $userType);
            }

            return $method;
        });
    }

    public function update(mixed $id, array $data, string $userType): ?Model
    {
        $method = $this->query()->where($this->statusColumn($userType), self::STATUS_ACTIVE)->find($id);

        if ($method === null) {
            return null;
        }

        return DB::transaction(function () use ($method, $data, $userType): Model {
            $isDefault = $method->is_default || ! empty($data['is_default']);

            $method->update([
                'method_name' => $data['method_name'],
                'method_fields' => $this->buildMethodFields($data),
                'is_default' => $isDefault,
                'is_active' => $isDefault ? self::STATUS_ACTIVE : $method->is_active,
            ]);

            if ($isDefault) {
                $this->clearDefaultOfOthers(id: $method->id, userType: $userType);
            }

            return $method->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $method = $this->query()->find($id);

        if ($method === null || $method->is_default) {
            return false;
        }

        return (bool) $method->update(['is_active' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function updateDefaultStatus(mixed $id, string $userType): bool
    {
        $method = $this->query()->where($this->statusColumn($userType), self::STATUS_ACTIVE)->find($id);

        if ($method === null || $method->is_default || ! $method->is_active) {
            return false;
        }

        return (bool) DB::transaction(function () use ($method, $userType): bool {
            $this->clearDefaultOfOthers(id: $method->id, userType: $userType);

            return (bool) $method->update(['is_default' => 1]);
        });
    }

    public function delete(mixed $id): bool
    {
        $method = $this->query()->find($id);

        if ($method === null || $method->is_default) {
            return false;
        }

        return (bool) $method->delete();
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

    private function statusColumn(string $userType): string
    {
        return match ($userType) {
            self::USER_TYPE_CUSTOMER => 'customer_status',
            default => 'vendor_status',
        };
    }

    private function hasAnyMethod(string $userType): bool
    {
        return $this->query()->where($this->statusColumn($userType), self::STATUS_ACTIVE)->exists();
    }

    private function clearDefaultOfOthers(mixed $id, string $userType): void
    {
        $this->query()
            ->where($this->statusColumn($userType), self::STATUS_ACTIVE)
            ->whereKeyNot($id)
            ->update(['is_default' => 0]);
    }

    private function buildMethodFields(array $data): array
    {
        $methodFields = [];

        foreach ($data['field_name'] ?? [] as $key => $fieldName) {
            $methodFields[] = [
                'input_type' => $data['field_type'][$key],
                'input_name' => strtolower(str_replace(' ', '_', $fieldName)),
                'placeholder' => $data['placeholder_text'][$key],
                'is_required' => isset($data['is_required'][$key]) ? 1 : 0,
            ];
        }

        return $methodFields;
    }
}
