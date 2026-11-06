<?php

namespace App\Services\New;

use App\Models\OrderStatusHistory;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistoryService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const USER_TYPE_CUSTOMER = 'customer';

    public const USER_TYPE_DELIVERY_MAN = 'delivery_man';

    /** @var array<int, string> */
    public const STATUS_SEQUENCE = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'returned', 'failed', 'canceled'];

    public const TRACKING_ORDER_PLACED = 'order_placed';

    public const TRACKING_ORDER_DELIVERED = 'order_delivered';

    /** @var array<string, string> */
    public const PHYSICAL_TRACKING_STATUSES = [
        'order_placed' => 'pending',
        'order_confirmed' => 'confirmed',
        'preparing_for_shipment' => 'processing',
        'order_is_on_the_way' => 'out_for_delivery',
        'order_delivered' => 'delivered',
        'order_returned' => 'returned',
        'order_failed' => 'failed',
        'order_canceled' => 'canceled',
    ];

    /** @var array<string, string> */
    public const DIGITAL_TRACKING_STATUSES = [
        'order_placed' => 'pending',
        'order_confirmed' => 'confirmed',
        'order_delivered' => 'delivered',
    ];

    /** @var array<int, string> */
    public const TERMINAL_TRACKING_STEPS = ['order_canceled', 'order_returned', 'order_failed'];

    public function __construct(OrderStatusHistory $orderStatusHistory)
    {
        parent::__construct($orderStatusHistory);
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

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest('id'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'] ?? 0,
            'user_type' => $data['user_type'],
            'status' => $data['status'],
            'cause' => $data['cause'] ?? null,
        ]);
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

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function deleteOutdatedHistory(int|string $orderId, string $orderStatus): void
    {
        $currentIndex = array_search($orderStatus, self::STATUS_SEQUENCE, true);

        if ($currentIndex === false) {
            return;
        }

        if ($currentIndex < count(self::STATUS_SEQUENCE) - 1) {
            $this->query()
                ->where('order_id', $orderId)
                ->whereIn('status', array_slice(self::STATUS_SEQUENCE, $currentIndex + 1))
                ->delete();
        }

        $historiesByStatus = $this->query()
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('status');

        foreach ($historiesByStatus as $histories) {
            foreach ($histories->slice(1) as $duplicate) {
                $duplicate->delete();
            }
        }
    }

    /** @return array<string, mixed> */
    public function getLatestStatusDates(iterable $histories): array
    {
        return collect($histories)
            ->sortByDesc('created_at')
            ->unique('status')
            ->mapWithKeys(static fn ($history) => [$history->status => $history->created_at])
            ->all();
    }

    /** @return array{history: array<string, array{key: string, status: bool, date_time: mixed}>, is_digital_order: bool} */
    public function getTrackingTimeline(int|string $orderId, mixed $orderPlacedAt, bool $isOrderOnlyDigital): array
    {
        $historiesByStatus = $this->query()
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->unique('status')
            ->keyBy('status');

        $statusMapping = $isOrderOnlyDigital ? self::DIGITAL_TRACKING_STATUSES : self::PHYSICAL_TRACKING_STATUSES;

        $tracking = [];
        foreach ($statusMapping as $step => $status) {
            $isPlacedStep = $step === self::TRACKING_ORDER_PLACED;
            $tracking[$step] = [
                'key' => $step,
                'status' => $isPlacedStep,
                'date_time' => $isPlacedStep ? $orderPlacedAt : null,
            ];

            if (isset($historiesByStatus[$status])) {
                $tracking[$step]['status'] = true;
                $tracking[$step]['date_time'] = $historiesByStatus[$status]->created_at;
            }
        }

        $steps = array_keys($tracking);
        $activeTerminalStep = null;
        $terminalIndex = -1;

        foreach (self::TERMINAL_TRACKING_STEPS as $terminalStep) {
            if (isset($tracking[$terminalStep]) && $tracking[$terminalStep]['status'] === true) {
                $activeTerminalStep = $terminalStep;
                $terminalIndex = array_search($terminalStep, $steps);
                break;
            }
        }

        $tracking = $activeTerminalStep !== null
            ? $this->alignTrackingToTerminalStep(tracking: $tracking, steps: $steps, activeTerminalStep: $activeTerminalStep, terminalIndex: $terminalIndex)
            : $this->backfillTrackingToLastCompletedStep(tracking: $tracking, steps: $steps);

        $withDate = [];
        $withoutDate = [];
        foreach ($steps as $step) {
            if ($tracking[$step]['date_time'] !== null) {
                $withDate[$step] = $tracking[$step];
            } else {
                $withoutDate[$step] = $tracking[$step];
            }
        }

        return [
            'history' => $withDate + $withoutDate,
            'is_digital_order' => $isOrderOnlyDigital,
        ];
    }

    /**
     * @param  array<string, array{key: string, status: bool, date_time: mixed}>  $tracking
     * @param  array<int, string>  $steps
     * @return array<string, array{key: string, status: bool, date_time: mixed}>
     */
    private function alignTrackingToTerminalStep(array $tracking, array $steps, string $activeTerminalStep, int $terminalIndex): array
    {
        if (isset($tracking[self::TRACKING_ORDER_DELIVERED])) {
            $tracking[self::TRACKING_ORDER_DELIVERED]['status'] = false;
            $tracking[self::TRACKING_ORDER_DELIVERED]['date_time'] = null;
        }

        $lastCompletedIndex = -1;
        for ($index = 0; $index < $terminalIndex; $index++) {
            if ($this->isProgressStep(step: $steps[$index])
                && $tracking[$steps[$index]]['status'] === true
                && $tracking[$steps[$index]]['date_time'] !== null) {
                $lastCompletedIndex = $index;
            }
        }

        if ($lastCompletedIndex >= 0) {
            $backfillDateTime = $tracking[$steps[$lastCompletedIndex]]['date_time'];

            for ($index = 0; $index < $lastCompletedIndex; $index++) {
                if ($tracking[$steps[$index]]['status'] === false && $this->isProgressStep(step: $steps[$index])) {
                    $tracking[$steps[$index]]['status'] = true;
                    $tracking[$steps[$index]]['date_time'] = $backfillDateTime;
                }
            }

            for ($index = $lastCompletedIndex + 1; $index < $terminalIndex; $index++) {
                if ($this->isProgressStep(step: $steps[$index])) {
                    $tracking[$steps[$index]]['status'] = false;
                    $tracking[$steps[$index]]['date_time'] = null;
                }
            }
        }

        foreach (self::TERMINAL_TRACKING_STEPS as $terminalStep) {
            if ($terminalStep !== $activeTerminalStep && isset($tracking[$terminalStep])) {
                $tracking[$terminalStep]['status'] = false;
                $tracking[$terminalStep]['date_time'] = null;
            }
        }

        return $tracking;
    }

    /**
     * @param  array<string, array{key: string, status: bool, date_time: mixed}>  $tracking
     * @param  array<int, string>  $steps
     * @return array<string, array{key: string, status: bool, date_time: mixed}>
     */
    private function backfillTrackingToLastCompletedStep(array $tracking, array $steps): array
    {
        $lastCompletedIndex = null;
        for ($index = count($steps) - 1; $index >= 0; $index--) {
            if ($tracking[$steps[$index]]['status'] === true) {
                $lastCompletedIndex = $index;
                break;
            }
        }

        if ($lastCompletedIndex !== null) {
            for ($index = 0; $index < $lastCompletedIndex; $index++) {
                if ($tracking[$steps[$index]]['status'] === false) {
                    $tracking[$steps[$index]]['status'] = true;
                    $tracking[$steps[$index]]['date_time'] = $tracking[$steps[$lastCompletedIndex]]['date_time'];
                }
            }
        }

        return $tracking;
    }

    private function isProgressStep(string $step): bool
    {
        return $step !== self::TRACKING_ORDER_DELIVERED && ! in_array($step, self::TERMINAL_TRACKING_STEPS);
    }
}
