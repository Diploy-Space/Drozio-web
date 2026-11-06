<?php

namespace App\Services\New;

use App\Models\SellerWallet;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VendorWalletService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(SellerWallet $sellerWallet)
    {
        parent::__construct($sellerWallet);
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
            ->when($searchValue, fn (Builder $builder) => $builder->whereHas('seller', function (Builder $seller) use ($searchValue): void {
                $seller->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%");
            }));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
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
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($this->walletColumns($data));
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($this->walletColumns($data));

        return $record->refresh();
    }

    public function createForSeller(int $sellerId): ?Model
    {
        return $this->query()->firstOrCreate(['seller_id' => $sellerId], [
            'withdrawn' => 0,
            'commission_given' => 0,
            'total_earning' => 0,
            'pending_withdraw' => 0,
            'delivery_charge_earned' => 0,
            'collected_cash' => 0,
        ]);
    }

    public function decrementTotalEarning(int $sellerId, float|int $amount): int
    {
        return $this->query()->where('seller_id', $sellerId)->decrement('total_earning', $amount);
    }

    public function reserveWithdraw(int $sellerId, float $amount): bool
    {
        return $this->query()
            ->where('seller_id', $sellerId)
            ->where('total_earning', '>=', $amount)
            ->update([
                'total_earning' => DB::raw('total_earning - '.$amount),
                'pending_withdraw' => DB::raw('pending_withdraw + '.$amount),
            ]) > 0;
    }

    public function releaseWithdraw(int $sellerId, float $amount): bool
    {
        return $this->query()
            ->where('seller_id', $sellerId)
            ->where('pending_withdraw', '>=', $amount)
            ->update([
                'total_earning' => DB::raw('total_earning + '.$amount),
                'pending_withdraw' => DB::raw('pending_withdraw - '.$amount),
            ]) > 0;
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
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

    public function isAdminCommissionCleared(int $sellerId, float|int $adminCommissionEarned): bool
    {
        return $adminCommissionEarned >= ($this->findBy(params: ['seller_id' => $sellerId])?->admin_commission ?? 0);
    }

    private function walletColumns(array $data): array
    {
        return array_filter([
            'seller_id' => $data['seller_id'] ?? null,
            'total_earning' => $data['total_earning'] ?? null,
            'withdrawn' => $data['withdrawn'] ?? null,
            'commission_given' => $data['commission_given'] ?? null,
            'pending_withdraw' => $data['pending_withdraw'] ?? null,
            'delivery_charge_earned' => $data['delivery_charge_earned'] ?? null,
            'collected_cash' => $data['collected_cash'] ?? null,
            'total_tax_collected' => $data['total_tax_collected'] ?? null,
        ], static fn ($value) => $value !== null);
    }
}
