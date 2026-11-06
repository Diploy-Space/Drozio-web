<?php

namespace App\Services\New\SupportTicket;

use App\Models\SupportTicketConv;
use App\Services\BaseService;
use App\Traits\AttachmentStorageTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SupportTicketConvService extends BaseService
{
    use AttachmentStorageTrait;

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(SupportTicketConv $supportTicketConv)
    {
        parent::__construct($supportTicketConv);
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
            ->withCount($withCount);

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            default => $query->oldest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function create(array $data, array $attachments = []): ?Model
    {
        return $this->query()->create(array_filter([
            'support_ticket_id' => $data['support_ticket_id'],
            'customer_message' => $data['customer_message'] ?? null,
            'admin_message' => $data['admin_message'] ?? null,
            'admin_id' => $data['admin_id'] ?? null,
            'attachment' => $this->storeAttachments(
                files: $attachments,
                directory: SupportTicketService::ATTACHMENT_DIRECTORY,
            ),
        ], static fn ($value) => $value !== null));
    }

    public function deleteBy(array $params): int
    {
        $query = $this->query();

        $this->applyFilters($query, $params);

        $conversations = $query->get(['id', 'attachment']);

        foreach ($conversations as $conversation) {
            foreach (SupportTicketService::getAttachmentFileNames($conversation->attachment) as $fileName) {
                $this->deleteFile(filePath: SupportTicketService::ATTACHMENT_DIRECTORY.$fileName);
            }
        }

        return $conversations->isEmpty()
            ? 0
            : $this->query()->whereIn('id', $conversations->modelKeys())->delete();
    }
}
