<?php

namespace App\Services\New;

use App\Models\DeliveryHistory;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DeliveryHistoryService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(DeliveryHistory $deliveryHistory)
    {
        parent::__construct($deliveryHistory);
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
            self::SORT_LATEST => $query->latest('id'),
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
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function findLatestBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->latest()->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'order_id' => $data['order_id'],
            'deliveryman_id' => $data['deliveryman_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'location' => $data['location'],
            'time' => now(),
        ]);
    }
}
