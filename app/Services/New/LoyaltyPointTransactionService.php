<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\LoyaltyPointTransaction;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoyaltyPointTransactionService extends BaseService
{
    public const FILTER_CREATED_BETWEEN = 'created_between';

    public const TRANSACTION_TYPE_ORDER_PLACE = 'order_place';

    public const TRANSACTION_TYPE_POINT_TO_WALLET = 'point_to_wallet';

    public const TRANSACTION_TYPE_REFUND_ORDER = 'refund_order';

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
        $query = $this->listQuery($searchValue, $filters)
            ->with($relations)
            ->withCount($withCount)
            ->latest();

        unset($filters[self::FILTER_CREATED_BETWEEN]);

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
        return $this->query()->create($data);
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
        return $this->listQuery(null, $filters)->count();
    }

    public function getTransactionTypes(): array
    {
        return [
            self::TRANSACTION_TYPE_ORDER_PLACE => translate('Order place'),
            self::TRANSACTION_TYPE_POINT_TO_WALLET => translate('Point to wallet'),
            self::TRANSACTION_TYPE_REFUND_ORDER => translate('Refund order'),
        ];
    }

    public function getOrderReferenceTypes(): array
    {
        return [
            self::TRANSACTION_TYPE_ORDER_PLACE,
            self::TRANSACTION_TYPE_REFUND_ORDER,
        ];
    }

    public function createPointEntry(
        int|string $customerId,
        float $currentPoint,
        float $amount,
        string $transactionType,
        ?string $reference = null,
        string $businessModule = BusinessModule::RETAIL,
    ): ?Model {
        $credit = 0;
        $debit = 0;

        if ($transactionType === self::TRANSACTION_TYPE_ORDER_PLACE) {
            $credit = (int) $amount;
        } elseif (in_array($transactionType, [self::TRANSACTION_TYPE_POINT_TO_WALLET, self::TRANSACTION_TYPE_REFUND_ORDER], true)) {
            $debit = $amount;
        }

        if ($credit == 0 && $debit == 0) {
            return null;
        }

        return $this->create([
            'user_id' => $customerId,
            'business_module' => $businessModule,
            'transaction_id' => Str::uuid(),
            'transaction_type' => $transactionType,
            'reference' => $reference,
            'credit' => $credit,
            'debit' => $debit,
            'balance' => $currentPoint + $credit - $debit,
        ]);
    }

    public function getTransactionSummary(array $filters = []): array
    {
        $totals = $this->listQuery(null, $filters)
            ->selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->first();

        return [
            'credit' => (float) ($totals?->total_credit ?? 0),
            'debit' => (float) ($totals?->total_debit ?? 0),
        ];
    }

    private function listQuery(?string $searchValue, array $filters): Builder
    {
        $createdBetween = $filters[self::FILTER_CREATED_BETWEEN] ?? [];
        unset($filters[self::FILTER_CREATED_BETWEEN]);

        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('transaction_id', 'like', "%{$searchValue}%")
                    ->orWhere('reference', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function (Builder $user) use ($searchValue): void {
                        $user->where('f_name', 'like', "%{$searchValue}%")
                            ->orWhere('l_name', 'like', "%{$searchValue}%")
                            ->orWhereRaw("CONCAT(f_name, ' ', l_name) like ?", ["%{$searchValue}%"])
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            }))
            ->when($createdBetween[0] ?? null, fn (Builder $builder) => $builder->where('created_at', '>=', $createdBetween[0]))
            ->when($createdBetween[1] ?? null, fn (Builder $builder) => $builder->where('created_at', '<=', $createdBetween[1]));

        return $this->applyFilters($query, $filters);
    }
}
