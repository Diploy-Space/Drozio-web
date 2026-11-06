<?php

namespace App\Services\New;

use App\Models\OrderEditHistory;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

class OrderEditHistoryService extends BaseService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const DUE_PAYMENT_STATUS_PAID = 'paid';

    public const DUE_PAYMENT_STATUS_UNPAID = 'unpaid';

    public const DUE_PAYMENT_METHOD_WALLET = 'wallet';

    public const DUE_PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    public const DUE_PAYMENT_METHOD_OFFLINE = 'offline_payment';

    public const RETURN_PAYMENT_STATUS_RETURNED = 'returned';

    public const RETURN_PAYMENT_METHOD_WALLET = 'wallet';

    public const RETURN_PAYMENT_METHOD_MANUALLY = 'manually';

    public const DUE_PAYMENT_NOTE_PAID_ON_DELIVERY = 'Marked as paid by Delivery Man';

    public const DUE_PAYMENT_NOTE_PAID_BY_DELIVERY_MAN = 'Marked as paid by deliveryman';

    public function __construct(OrderEditHistory $orderEditHistory)
    {
        parent::__construct($orderEditHistory);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('order_id', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_NEWEST => $query->latest('id'),
            self::SORT_OLDEST => $query->oldest('id'),
            default => $query->oldest('id'),
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

    public function findLatestByOrder(int|string $orderId): ?Model
    {
        return $this->query()->where('order_id', $orderId)->latest('created_at')->first();
    }

    public function findLatestForOrder(int|string $orderId): ?Model
    {
        return $this->query()->where('order_id', $orderId)->latest('id')->first();
    }

    /** @param array{edit_by: string, edited_user_id: int|string|null, edited_user_name: ?string} $editor */
    public function markReturnPaymentReturned(mixed $id, float|int|string $returnAmount, string $paymentMethod, ?string $paymentNote, array $editor): int
    {
        return $this->query()->where('id', $id)->update([
            ...$editor,
            'order_return_amount' => $returnAmount,
            'order_return_payment_status' => self::RETURN_PAYMENT_STATUS_RETURNED,
            'order_return_payment_method' => $paymentMethod,
            'order_return_transaction_ref' => null,
            'order_return_payment_note' => $paymentNote,
        ]);
    }

    /** @param array{edit_by: string, edited_user_id: int|string|null, edited_user_name: ?string} $editor */
    public function markDuePaidByEditor(mixed $id, ?string $paymentMethod, string $paymentNote, array $editor): int
    {
        return $this->query()->where('id', $id)->update([
            ...$editor,
            'order_due_payment_status' => self::DUE_PAYMENT_STATUS_PAID,
            'order_due_payment_method' => $paymentMethod ?: self::DUE_PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_due_transaction_ref' => '',
            'order_due_payment_note' => $paymentNote,
        ]);
    }

    public function markDuePaymentPaid(mixed $id, string $paymentMethod): int
    {
        return $this->query()->where('id', $id)->update([
            'order_due_payment_status' => self::DUE_PAYMENT_STATUS_PAID,
            'order_due_payment_method' => $paymentMethod,
        ]);
    }

    public function markDuePaidOnDelivery(mixed $id): int
    {
        return $this->query()->where('id', $id)->update([
            'order_due_payment_status' => self::DUE_PAYMENT_STATUS_PAID,
            'order_due_payment_note' => self::DUE_PAYMENT_NOTE_PAID_ON_DELIVERY,
        ]);
    }

    public function markDuePaidByDeliveryman(mixed $id, ?string $paymentMethod): int
    {
        return $this->query()->where('id', $id)->update([
            'order_due_payment_status' => self::DUE_PAYMENT_STATUS_PAID,
            'order_due_payment_method' => $paymentMethod ?? self::DUE_PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_due_transaction_ref' => '',
            'order_due_payment_note' => self::DUE_PAYMENT_NOTE_PAID_BY_DELIVERY_MAN,
        ]);
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
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

    public function getPaymentHistory(int|string $orderId): SupportCollection
    {
        $histories = $this->query()->where('order_id', $orderId)->orderBy('id', 'asc')->get();

        $latestHistory = $histories->sortByDesc('updated_at')->first();
        $unpaidDueHistories = [];
        if ($latestHistory && $latestHistory->order_due_payment_status !== self::DUE_PAYMENT_STATUS_PAID && $latestHistory->order_due_amount > 0) {
            $unpaidDueHistories[] = $latestHistory;
        }

        return collect()->merge($this->filterSettledHistories(histories: $histories))->merge($unpaidDueHistories)->values();
    }

    /** @return array{settled_histories: SupportCollection, has_unpaid_due: bool, edit_due_amount: mixed, edit_return_amount: mixed, paid_before_due: mixed, paid_before_return: mixed} */
    public function getSettlementSummary(Model $order, iterable $histories): array
    {
        $latestHistory = $order->latestEditHistory;

        return [
            'settled_histories' => $this->filterSettledHistories(histories: $histories),
            'has_unpaid_due' => $order['edit_due_amount'] > 0 && $latestHistory && $latestHistory->order_due_payment_status === self::DUE_PAYMENT_STATUS_UNPAID,
            'edit_due_amount' => $order['edit_due_amount'] ?? 0,
            'edit_return_amount' => $order['edit_return_amount'] ?? 0,
            'paid_before_due' => $latestHistory ? $latestHistory->order_amount - $latestHistory->order_due_amount : 0,
            'paid_before_return' => $latestHistory ? $latestHistory->order_amount + $latestHistory->order_return_amount : 0,
        ];
    }

    private function filterSettledHistories(iterable $histories): SupportCollection
    {
        return collect($histories)->filter(
            static fn ($history) => $history->order_due_payment_status === self::DUE_PAYMENT_STATUS_PAID
                || $history->order_return_payment_status === self::RETURN_PAYMENT_STATUS_RETURNED,
        );
    }

    /** @param array{edit_by?: string, edited_user_id?: int|string|null, edited_user_name?: ?string} $editor */
    public function updateDuePaymentMethod(mixed $id, string $paymentMethod, ?array $paymentInfo = null, ?string $paymentNote = null, array $editor = []): bool
    {
        $record = $id === null ? null : $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        $data = [...$editor, 'order_due_payment_method' => $paymentMethod];

        if ($paymentInfo !== null) {
            $data['order_due_payment_info'] = $paymentInfo;
        }

        if ($paymentNote !== null) {
            $data['order_due_payment_note'] = $paymentNote;
        }

        return $record->update($data);
    }
}
