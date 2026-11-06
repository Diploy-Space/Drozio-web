<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Chatting;
use App\Services\BaseService;
use App\Traits\AttachmentStorageTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ChattingService extends BaseService
{
    use AttachmentStorageTrait;

    public const ATTACHMENT_DIRECTORY = 'chatting/';

    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_SELLER = 'seller';

    public const TYPE_DELIVERY_MAN = 'delivery-man';

    public const TYPE_ADMIN = 'admin';

    public const ADMIN_THREAD_ID = 0;

    public const FILTER_FROM_CUSTOMER = 'from_customer';

    public const FILTER_FROM_DELIVERY_MAN = 'from_delivery_man';

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_NEWEST_ID = 'newest_id';

    public const SORT_OLDEST_ID = 'oldest_id';

    public const FILTER_HAS_SHOP = 'has_shop';

    private const PARTY_COLUMNS = [
        self::TYPE_CUSTOMER => 'user_id',
        self::TYPE_SELLER => 'seller_id',
        self::TYPE_DELIVERY_MAN => 'delivery_man_id',
        self::TYPE_ADMIN => 'admin_id',
    ];

    private const SEEN_COLUMNS = [
        self::TYPE_CUSTOMER => 'seen_by_customer',
        self::TYPE_SELLER => 'seen_by_seller',
        self::TYPE_DELIVERY_MAN => 'seen_by_delivery_man',
        self::TYPE_ADMIN => 'seen_by_admin',
    ];

    private const SENT_BY_COLUMNS = [
        self::TYPE_CUSTOMER => 'sent_by_customer',
        self::TYPE_SELLER => 'sent_by_seller',
        self::TYPE_DELIVERY_MAN => 'sent_by_delivery_man',
        self::TYPE_ADMIN => 'sent_by_admin',
    ];

    private const NOTIFICATION_RECEIVERS = [
        self::TYPE_CUSTOMER => 'customer',
        self::TYPE_SELLER => 'seller',
        self::TYPE_DELIVERY_MAN => 'deliveryman',
        self::TYPE_ADMIN => 'admin',
    ];

    public function __construct(Chatting $chatting)
    {
        parent::__construct($chatting);
    }

    /**
     * @return array<int, string>
     */
    public static function counterpartyTypes(): array
    {
        return [self::TYPE_SELLER, self::TYPE_DELIVERY_MAN, self::TYPE_ADMIN];
    }

    /**
     * @return array<int, string>
     */
    public static function counterpartyTypesFor(string $viewerType): array
    {
        return array_values(array_diff(
            [self::TYPE_CUSTOMER, self::TYPE_SELLER, self::TYPE_DELIVERY_MAN, self::TYPE_ADMIN],
            [$viewerType],
        ));
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('message', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest(),
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            self::SORT_OLDEST_ID => $query->orderBy('id'),
            default => $query->latest(),
        };

        if (! empty($filters[self::FILTER_HAS_SHOP])) {
            $query->whereNotNull('shop_id');
        }

        unset($filters[self::FILTER_HAS_SHOP]);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        if (! empty($filters[self::FILTER_FROM_CUSTOMER])) {
            $query->whereNotNull('user_id');
        }

        if (! empty($filters[self::FILTER_FROM_DELIVERY_MAN])) {
            $query->whereNotNull('delivery_man_id');
        }

        unset($filters[self::FILTER_FROM_CUSTOMER], $filters[self::FILTER_FROM_DELIVERY_MAN]);

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function getThreadList(
        string $viewerType,
        int $viewerId,
        string|array $counterpartyType,
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        ?string $businessModule = null,
    ): Collection|LengthAwarePaginator {
        $counterpartyTypes = $this->searchableCounterpartyTypes(
            counterpartyTypes: (array) $counterpartyType,
            searchValue: $searchValue,
        );

        $query = $this->query()
            ->with($relations)
            ->when($businessModule, fn (Builder $query) => $query->businessModule($businessModule))
            ->where(function (Builder $builder) use ($viewerType, $viewerId, $counterpartyTypes, $searchValue, $businessModule): void {
                foreach ($counterpartyTypes as $type) {
                    $builder->orWhere(fn (Builder $group) => $group
                        ->whereIn('id', $this->latestMessageIdsPerThread(
                            viewerType: $viewerType,
                            viewerId: $viewerId,
                            column: $this->partyColumn($type),
                            businessModule: $businessModule,
                        ))
                        ->when($searchValue, fn (Builder $inner) => $this->applyCounterpartySearch($inner, $type, $searchValue)));
                }
            })
            ->latest();

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function getMessageList(
        string $viewerType,
        int $viewerId,
        string $counterpartyType,
        ?int $counterpartyId = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        ?string $businessModule = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->threadQuery(viewerType: $viewerType, viewerId: $viewerId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId, businessModule: $businessModule)
            ->with($relations)
            ->latest();

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function sendMessage(
        string $viewerType,
        int $viewerId,
        string $counterpartyType,
        ?int $counterpartyId = null,
        ?string $message = null,
        array $attachments = [],
        ?int $shopId = null,
        string $businessModule = BusinessModule::RETAIL,
    ): ?Model {
        return $this->query()->create([
            'business_module' => $businessModule,
            $this->partyColumn($viewerType) => $viewerId,
            $this->partyColumn($counterpartyType) => $this->threadId($counterpartyType, $counterpartyId),
            'shop_id' => $shopId,
            'message' => $message,
            'attachment' => json_encode($this->storeAttachments(files: $attachments, directory: self::ATTACHMENT_DIRECTORY)),
            self::SENT_BY_COLUMNS[$viewerType] => 1,
            self::SEEN_COLUMNS[$viewerType] => 1,
            self::SEEN_COLUMNS[$counterpartyType] => 0,
            'notification_receiver' => self::NOTIFICATION_RECEIVERS[$counterpartyType],
        ]);
    }

    public function markSeen(string $viewerType, int $viewerId, string $counterpartyType, ?int $counterpartyId = null): bool
    {
        $seenColumn = self::SEEN_COLUMNS[$viewerType];

        $this->threadQuery(viewerType: $viewerType, viewerId: $viewerId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId)
            ->where(fn (Builder $builder) => $this->whereUnseen($builder, $seenColumn))
            ->update([$seenColumn => 1]);

        return true;
    }

    public function getUnseenCount(string $viewerType, int $viewerId, string $counterpartyType, ?int $counterpartyId = null): int
    {
        return $this->threadQuery(viewerType: $viewerType, viewerId: $viewerId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId)
            ->where(fn (Builder $builder) => $this->whereUnseen($builder, self::SEEN_COLUMNS[$viewerType]))
            ->where(fn (Builder $builder) => $this->whereNotSentBy($builder, $viewerType))
            ->count();
    }

    /**
     * @return array<int|string, int>
     */
    public function getUnseenCountsByCounterparty(string $viewerType, int $viewerId, string $counterpartyType): array
    {
        $column = $this->partyColumn($counterpartyType);

        return $this->query()
            ->where($this->partyColumn($viewerType), $viewerId)
            ->whereNotNull($column)
            ->where(fn (Builder $builder) => $this->whereUnseen($builder, self::SEEN_COLUMNS[$viewerType]))
            ->where(fn (Builder $builder) => $this->whereNotSentBy($builder, $viewerType))
            ->select($column)
            ->selectRaw('count(*) as total')
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(static fn ($total) => (int) $total)
            ->all();
    }

    public function hasThread(string $viewerType, int $viewerId, string $counterpartyType, ?int $counterpartyId = null): bool
    {
        return $this->threadQuery(viewerType: $viewerType, viewerId: $viewerId, counterpartyType: $counterpartyType, counterpartyId: $counterpartyId)
            ->exists();
    }

    private function threadQuery(string $viewerType, int $viewerId, string $counterpartyType, ?int $counterpartyId = null, ?string $businessModule = null): Builder
    {
        return $this->query()
            ->when($businessModule, fn (Builder $query) => $query->businessModule($businessModule))
            ->where($this->partyColumn($viewerType), $viewerId)
            ->where($this->partyColumn($counterpartyType), $this->threadId($counterpartyType, $counterpartyId));
    }

    private function latestMessageIdsPerThread(string $viewerType, int $viewerId, string $column, ?string $businessModule = null): Builder
    {
        return $this->query()
            ->when($businessModule, fn (Builder $query) => $query->businessModule($businessModule))
            ->where($this->partyColumn($viewerType), $viewerId)
            ->whereNotNull($column)
            ->selectRaw('max(id)')
            ->groupBy($column);
    }

    /**
     * @param  array<int, string>  $counterpartyTypes
     * @return array<int, string>
     */
    private function searchableCounterpartyTypes(array $counterpartyTypes, ?string $searchValue): array
    {
        if ($searchValue === null || count($counterpartyTypes) === 1) {
            return $counterpartyTypes;
        }

        return array_values(array_filter(
            $counterpartyTypes,
            static fn (string $type): bool => $type !== self::TYPE_ADMIN,
        ));
    }

    private function applyCounterpartySearch(Builder $query, string $counterpartyType, string $searchValue): Builder
    {
        $byName = fn (string $relation): Builder => $query->whereHas(
            $relation,
            fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('f_name', 'like', "%{$searchValue}%")
                    ->orWhere('l_name', 'like', "%{$searchValue}%");
            }),
        );

        return match ($counterpartyType) {
            self::TYPE_DELIVERY_MAN => $byName('deliveryMan'),
            self::TYPE_CUSTOMER => $byName('customer'),
            self::TYPE_ADMIN => $query,
            default => $query->whereHas('sellerInfo.shops', fn (Builder $builder) => $builder->where('name', 'like', "%{$searchValue}%")),
        };
    }

    private function threadId(string $counterpartyType, ?int $counterpartyId): int
    {
        return $counterpartyType === self::TYPE_ADMIN ? self::ADMIN_THREAD_ID : (int) $counterpartyId;
    }

    private function whereUnseen(Builder $query, string $seenColumn): Builder
    {
        return $query->where($seenColumn, 0)->orWhereNull($seenColumn);
    }

    private function whereNotSentBy(Builder $query, string $partyType): Builder
    {
        $sentByColumn = self::SENT_BY_COLUMNS[$partyType];

        return $query->where($sentByColumn, 0)->orWhereNull($sentByColumn);
    }

    private function partyColumn(string $partyType): string
    {
        return self::PARTY_COLUMNS[$partyType];
    }
}
