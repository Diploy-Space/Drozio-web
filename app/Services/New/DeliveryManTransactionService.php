<?php

namespace App\Services\New;

use App\Models\DeliveryManTransaction;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class DeliveryManTransactionService extends BaseService
{
    public const TYPE_CASH_IN_HAND = 'cash_in_hand';

    public const FILTER_DATE_RANGE = 'date_range';

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(DeliveryManTransaction $deliveryManTransaction)
    {
        parent::__construct($deliveryManTransaction);
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
        unset($filters[self::FILTER_DATE_RANGE]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($dateRange, fn (Builder $builder) => $builder->whereBetween('created_at', $dateRange));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest(),
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
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'delivery_man_id' => $data['delivery_man_id'],
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type'],
            'credit' => $data['credit'] ?? 0,
            'debit' => $data['debit'] ?? 0,
            'transaction_id' => $data['transaction_id'] ?? Uuid::uuid4()->toString(),
            'transaction_type' => $data['transaction_type'],
        ]);
    }

    public function sumCredit(array $filters = []): float
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return (float) $query->sum('credit');
    }
}
