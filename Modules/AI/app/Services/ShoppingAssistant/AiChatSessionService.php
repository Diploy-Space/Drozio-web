<?php

namespace Modules\AI\app\Services\ShoppingAssistant;

use App\Services\BaseService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\AI\app\Models\AiChatSession;

class AiChatSessionService extends BaseService
{
    public const FILTER_BUSINESS_MODULE = 'business_module';

    public const FILTER_OWNER = 'owner';

    public const FILTER_HAS_MESSAGES = 'has_messages';

    public const OWNER_CUSTOMER_ID = 'customer_id';

    public const OWNER_GUEST_ID = 'guest_id';

    public function __construct(AiChatSession $aiChatSession)
    {
        parent::__construct($aiChatSession);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('title', 'like', "%{$searchValue}%"))
            ->orderByDesc('last_activity_at');

        $this->applySessionFilters(query: $query, filters: $filters);

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
        if (array_key_exists(self::FILTER_OWNER, $params) && ! $this->hasOwner(owner: $params[self::FILTER_OWNER])) {
            return null;
        }

        $query = $this->query()->with($relations);

        $this->applySessionFilters(query: $query, filters: $params);

        return $query->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'customer_id' => $data['customer_id'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'business_module' => $data['business_module'],
            'locale' => $data['locale'],
            'last_activity_at' => now(),
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'title' => $data['title'] ?? null,
            'last_activity_at' => $data['last_activity_at'] ?? null,
        ], static fn ($value) => $value !== null));

        return $record;
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->query()->whereKey($id)->delete();
    }

    public function deleteInactiveGuestSessions(CarbonInterface $inactiveBefore): int
    {
        return $this->query()
            ->whereNull('customer_id')
            ->where(fn (Builder $builder) => $builder
                ->where('last_activity_at', '<', $inactiveBefore)
                ->orWhereNull('last_activity_at')->where('created_at', '<', $inactiveBefore))
            ->delete();
    }

    public function deleteEmptySessions(CarbonInterface $createdBefore): int
    {
        return $this->query()
            ->doesntHave('messages')
            ->where('created_at', '<', $createdBefore)
            ->delete();
    }

    private function applySessionFilters(Builder $query, array $filters): void
    {
        if (array_key_exists(self::FILTER_OWNER, $filters)) {
            $this->applyOwner(query: $query, owner: $filters[self::FILTER_OWNER]);
        }

        if (! empty($filters[self::FILTER_HAS_MESSAGES])) {
            $query->has('messages');
        }

        unset($filters[self::FILTER_OWNER], $filters[self::FILTER_HAS_MESSAGES]);

        $this->applyFilters($query, $filters);
    }

    private function applyOwner(Builder $query, array $owner): void
    {
        if (! $this->hasOwner(owner: $owner)) {
            $query->whereKey([]);

            return;
        }

        $customerId = $owner[self::OWNER_CUSTOMER_ID] ?? null;
        $guestId = $owner[self::OWNER_GUEST_ID] ?? null;

        $query->where(fn (Builder $builder) => $builder
            ->when($customerId, fn (Builder $inner) => $inner->orWhere('customer_id', $customerId))
            ->when($guestId, fn (Builder $inner) => $inner->orWhere('guest_id', $guestId)));
    }

    private function hasOwner(array $owner): bool
    {
        return ! empty($owner[self::OWNER_CUSTOMER_ID]) || ! empty($owner[self::OWNER_GUEST_ID]);
    }
}
