<?php

namespace App\Services\New;

use App\Models\WithdrawRequest;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class WithdrawRequestService extends BaseService
{
    public const APPROVAL_PENDING = 0;

    public const APPROVAL_APPROVED = 1;

    public const APPROVAL_DENIED = 2;

    public const STATUS_ALL = 'all';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const FILTER_DATE_RANGE = 'date_range';

    public const FILTER_HAS_DELIVERY_MAN = 'has_delivery_man';

    public const SORT_LATEST = 'latest';

    public const SORT_NEWEST_ID = 'newest-id';

    public const REQUEST_METHOD_TYPE_SYSTEM = 'system';

    private const AMOUNT_MATCH_TOLERANCE = 0.01;

    public function __construct(WithdrawRequest $withdrawRequest)
    {
        parent::__construct($withdrawRequest);
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
        $dateRange = $filters[self::FILTER_DATE_RANGE] ?? [];
        $hasDeliveryMan = ! empty($filters[self::FILTER_HAS_DELIVERY_MAN]);
        unset($filters[self::FILTER_DATE_RANGE], $filters[self::FILTER_HAS_DELIVERY_MAN]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($hasDeliveryMan, fn (Builder $builder) => $builder->whereNotNull('delivery_man_id'))
            ->when($dateRange, fn (Builder $builder) => $builder->whereBetween('created_at', $dateRange))
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->whereHas('seller', function (Builder $seller) use ($searchValue): void {
                    $seller->where('f_name', 'like', "%{$searchValue}%")
                        ->orWhere('l_name', 'like', "%{$searchValue}%")
                        ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$searchValue}%"])
                        ->orWhereHas('shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"));
                })->orWhereHas('deliveryMan', function (Builder $deliveryMan) use ($searchValue): void {
                    $deliveryMan->where('f_name', 'like', "%{$searchValue}%")
                        ->orWhere('l_name', 'like', "%{$searchValue}%")
                        ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$searchValue}%"]);
                })->when(is_numeric($searchValue), fn (Builder $amount) => $amount->orWhereBetween('amount', [
                    (float) $searchValue - self::AMOUNT_MATCH_TOLERANCE,
                    (float) $searchValue + self::AMOUNT_MATCH_TOLERANCE,
                ]));
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = ['seller']): ?Model
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
        return $this->query()->create($this->withdrawRequestColumns($data));
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($this->withdrawRequestColumns($data));

        return $record->refresh();
    }

    public function updateStatus(mixed $id, int $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['approved' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    /** @return array{pending: int, approved: int, denied: int} */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas('seller', function (Builder $seller) use ($searchValue): void {
                $seller->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%");
            }));

        $this->applyFilters($query, $filters);

        $counts = $query->select('approved')
            ->selectRaw('count(*) as total')
            ->groupBy('approved')
            ->pluck('total', 'approved');

        return [
            self::STATUS_PENDING => (int) ($counts[self::APPROVAL_PENDING] ?? 0),
            self::STATUS_APPROVED => (int) ($counts[self::APPROVAL_APPROVED] ?? 0),
            self::STATUS_DENIED => (int) ($counts[self::APPROVAL_DENIED] ?? 0),
        ];
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

    /** @return array<string, mixed> */
    public function getWithdrawalMethodFields(Model $withdrawalMethod, array $methodInfo): array
    {
        $fields = ['method_name' => $withdrawalMethod->method_name];

        foreach (array_column($withdrawalMethod->method_fields ?? [], 'input_name') as $inputName) {
            if (array_key_exists($inputName, $methodInfo)) {
                $fields[$inputName] = $methodInfo[$inputName];
            }
        }

        return $fields;
    }

    private function withdrawRequestColumns(array $data): array
    {
        return array_filter([
            'seller_id' => $data['seller_id'] ?? null,
            'delivery_man_id' => $data['delivery_man_id'] ?? null,
            'admin_id' => $data['admin_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'withdrawal_method_id' => $data['withdrawal_method_id'] ?? null,
            'withdrawal_method_fields' => $data['withdrawal_method_fields'] ?? null,
            'transaction_note' => $data['transaction_note'] ?? null,
            'approved' => isset($data['approved']) ? (int) $data['approved'] : null,
            'request_method_type' => $data['request_method_type'] ?? null,
        ], static fn ($value) => $value !== null);
    }
}
