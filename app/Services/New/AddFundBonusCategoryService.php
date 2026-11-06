<?php

namespace App\Services\New;

use App\Models\AddFundBonusCategories;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AddFundBonusCategoryService extends BaseService
{
    public const SORT_NEWEST_ID = 'newest-id';

    public const SORT_OLDEST_ID = 'oldest-id';

    public const FILTER_ENDED_BEFORE = 'ended_before';

    public const FILTER_RUNNING_AT = 'running_at';

    public const BONUS_TYPE_FIXED = 'fixed';

    public const BONUS_TYPE_PERCENTAGE = 'percentage';

    public function __construct(AddFundBonusCategories $addFundBonusCategory)
    {
        parent::__construct($addFundBonusCategory);
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
        $endedBefore = $filters[self::FILTER_ENDED_BEFORE] ?? null;
        $runningAt = $filters[self::FILTER_RUNNING_AT] ?? null;
        unset($filters[self::FILTER_ENDED_BEFORE], $filters[self::FILTER_RUNNING_AT]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where('title', 'like', "%{$searchValue}%"))
            ->when($endedBefore, fn (Builder $builder) => $builder->where('end_date_time', '<', $endedBefore))
            ->when($runningAt, fn (Builder $builder) => $builder->active()
                ->whereDate('start_date_time', '<=', $runningAt)
                ->whereDate('end_date_time', '>=', $runningAt));

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

    public function create(array $data): ?Model
    {
        return $this->query()->create($this->columns($data));
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
        return (bool) $this->query()->where('id', $id)->update(['is_active' => (int) $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->applyFilters($this->query(), $params)->update($data);
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

    public function getApplicableBonusAmount(float $amount): float|int
    {
        $runningBonuses = $this->query()
            ->where('is_active', 1)
            ->whereDate('start_date_time', '<=', now())
            ->whereDate('end_date_time', '>=', now())
            ->where('min_add_money_amount', '<=', $amount)
            ->get();

        $applicableBonuses = $runningBonuses->where('min_add_money_amount', $runningBonuses->max('min_add_money_amount'));

        foreach ($applicableBonuses as $bonus) {
            $bonus->applied_bonus_amount = $bonus->bonus_type == self::BONUS_TYPE_PERCENTAGE
                ? ($amount * $bonus->bonus_amount) / 100
                : $bonus->bonus_amount;

            if ($bonus->bonus_type == self::BONUS_TYPE_PERCENTAGE && $bonus->applied_bonus_amount > $bonus->max_bonus_amount) {
                $bonus->applied_bonus_amount = $bonus->max_bonus_amount;
            }
        }

        return $applicableBonuses->max('applied_bonus_amount') ?? 0;
    }

    /** @return array<string, mixed> */
    private function columns(array $data): array
    {
        return array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'bonus_type' => $data['bonus_type'] ?? null,
            'bonus_amount' => $data['bonus_amount'] ?? null,
            'min_add_money_amount' => $data['min_add_money_amount'] ?? null,
            'max_bonus_amount' => $data['max_bonus_amount'] ?? null,
            'start_date_time' => $data['start_date_time'] ?? null,
            'end_date_time' => $data['end_date_time'] ?? null,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : null,
        ], static fn ($value) => $value !== null);
    }
}
