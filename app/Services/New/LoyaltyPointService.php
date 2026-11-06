<?php

namespace App\Services\New;

use App\Models\LoyaltyPointTransaction;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPointService extends BaseService
{
    public const FILTER_CREATED_BETWEEN = 'created_between';

    public const FILTER_ENTRY_TYPE = 'entry_type';

    public const ENTRY_CREDIT = 'credit';

    public const ENTRY_DEBIT = 'debit';

    public const REASON_BELOW_MINIMUM = 'below-minimum';

    public const REASON_INSUFFICIENT_POINT = 'insufficient-point';

    public const REASON_RESPONSE_KEYS = [
        self::REASON_BELOW_MINIMUM => 'loyalty_point_below_minimum',
        self::REASON_INSUFFICIENT_POINT => 'loyalty_point_insufficient',
    ];

    public function __construct(LoyaltyPointTransaction $loyaltyPointTransaction)
    {
        parent::__construct($loyaltyPointTransaction);
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
        $createdBetween = $filters[self::FILTER_CREATED_BETWEEN] ?? [];
        $entryType = $filters[self::FILTER_ENTRY_TYPE] ?? null;
        unset($filters[self::FILTER_CREATED_BETWEEN], $filters[self::FILTER_ENTRY_TYPE]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('transaction_id', 'like', "%{$searchValue}%"))
            ->when($entryType === self::ENTRY_DEBIT, fn (Builder $builder) => $builder->where('debit', '!=', 0))
            ->when($entryType === self::ENTRY_CREDIT, fn (Builder $builder) => $builder->where('debit', '=', 0))
            ->when($createdBetween[0] ?? null, fn (Builder $builder) => $builder->where('created_at', '>=', $createdBetween[0]))
            ->when($createdBetween[1] ?? null, fn (Builder $builder) => $builder->where('created_at', '<=', $createdBetween[1]))
            ->latest();

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function getMinimumConvertiblePoint(): int
    {
        return (int) getWebConfig(name: 'loyalty_point_minimum_point');
    }

    public function getBlockingReason(Model $customer, int $point): ?string
    {
        if ($point < $this->getMinimumConvertiblePoint()) {
            return self::REASON_BELOW_MINIMUM;
        }

        return $point > (int) $customer->loyalty_point ? self::REASON_INSUFFICIENT_POINT : null;
    }

    public function getBalanceFirstBlockingReason(Model $customer, int $point): ?string
    {
        if ($point > (int) $customer->loyalty_point) {
            return self::REASON_INSUFFICIENT_POINT;
        }

        return $point < $this->getMinimumConvertiblePoint() ? self::REASON_BELOW_MINIMUM : null;
    }
}
