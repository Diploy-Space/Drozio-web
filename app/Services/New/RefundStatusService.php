<?php

namespace App\Services\New;

use App\Models\RefundStatus;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RefundStatusService extends BaseService
{
    public function __construct(RefundStatus $refundStatus)
    {
        parent::__construct($refundStatus);
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

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function createForStatusChange(int|string $refundRequestId, string $changedBy, int|string $changedById, string $status, ?string $message): Model
    {
        return $this->query()->create([
            'refund_request_id' => $refundRequestId,
            'change_by' => $changedBy,
            'change_by_id' => $changedById,
            'status' => $status,
            'message' => $message,
        ]);
    }

    public function createBySeller(int|string $refundRequestId, int $sellerId, string $status, ?string $note): Model
    {
        $isNotedStatus = in_array($status, [RefundRequestService::STATUS_APPROVED, RefundRequestService::STATUS_REJECTED], true);

        return $this->query()->create([
            'refund_request_id' => $refundRequestId,
            'change_by' => RefundRequestService::CHANGED_BY_SELLER,
            'change_by_id' => $sellerId,
            'status' => $status,
            'message' => $isNotedStatus ? $note : null,
        ]);
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
