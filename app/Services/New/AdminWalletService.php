<?php

namespace App\Services\New;

use App\Models\AdminWallet;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdminWalletService extends BaseService
{
    public const IN_HOUSE_ADMIN_ID = 1;

    public function __construct(AdminWallet $adminWallet)
    {
        parent::__construct($adminWallet);
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
        $query = $this->query()->with($relations)->withCount($withCount);

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

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
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

    public function createInHouseWalletIfMissing(): void
    {
        if ($this->query()->where('admin_id', self::IN_HOUSE_ADMIN_ID)->first() !== null) {
            return;
        }

        $this->query()->insert([
            'admin_id' => self::IN_HOUSE_ADMIN_ID,
            'withdrawn' => 0,
            'commission_earned' => 0,
            'inhouse_earning' => 0,
            'delivery_charge_earned' => 0,
            'pending_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function incrementInHousePendingAmount(float|int $amount): int
    {
        return $this->query()->where('admin_id', self::IN_HOUSE_ADMIN_ID)->increment('pending_amount', $amount);
    }

    public function decrementInHousePendingAmount(float|int $amount): int
    {
        return $this->query()->where('admin_id', self::IN_HOUSE_ADMIN_ID)->decrement('pending_amount', $amount);
    }

    public function decrementInHouseEarning(int $adminId, float|int $amount): int
    {
        return $this->query()->where('admin_id', $adminId)->decrement('inhouse_earning', $amount);
    }
}
