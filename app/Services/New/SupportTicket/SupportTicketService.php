<?php

namespace App\Services\New\SupportTicket;

use App\Enums\BusinessModule;
use App\Models\SupportTicket;
use App\Services\BaseService;
use App\Traits\AttachmentStorageTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketService extends BaseService
{
    use AttachmentStorageTrait;

    public const ATTACHMENT_DIRECTORY = 'support-ticket/';

    public const DESCRIPTION_MAX_LENGTH = 500;

    public const STATUS_PENDING = 'pending';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'close';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_RECENTLY_UPDATED = 'recently-updated';

    public function __construct(SupportTicket $supportTicket)
    {
        parent::__construct($supportTicket);
    }

    /**
     * @return array<int, string>
     */
    public static function priorities(): array
    {
        return [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
    }

    /**
     * @return array<int, string>
     */
    public static function getAttachmentFileNames(?array $attachments): array
    {
        $fileNames = [];

        foreach ($attachments ?? [] as $attachment) {
            $fileName = is_array($attachment) ? ($attachment['file_name'] ?? null) : $attachment;

            if (is_string($fileName) && $fileName !== '') {
                $fileNames[] = $fileName;
            }
        }

        return $fileNames;
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
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('subject', 'like', "%{$searchValue}%")
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereHas('customer', function (Builder $customer) use ($searchValue): void {
                        $customer->where('f_name', 'like', "%{$searchValue}%")
                            ->orWhere('l_name', 'like', "%{$searchValue}%")
                            ->orWhereRaw("concat(f_name, ' ', l_name) like ?", ["%{$searchValue}%"]);
                    });
            }));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest(),
            self::SORT_RECENTLY_UPDATED => $query->orderByDesc('updated_at'),
            default => $query->latest(),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = [], ?int $customerId = null): ?Model
    {
        return $this->query()
            ->with($relations)
            ->when($customerId !== null, fn (Builder $builder) => $builder->where('customer_id', $customerId))
            ->find($id);
    }

    public function findWithConversations(mixed $id, ?int $customerId = null, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->with(['conversations' => fn (HasMany $conversations) => $conversations->oldest('id')])
            ->when($customerId !== null, fn (Builder $builder) => $builder->where('customer_id', $customerId))
            ->find($id);
    }

    public function create(array $data, array $attachments = []): ?Model
    {
        return $this->query()->create([
            'business_module' => $data['business_module'] ?? BusinessModule::RETAIL,
            'customer_id' => $data['customer_id'],
            'subject' => $data['subject'],
            'type' => $data['type'],
            'priority' => strtolower(trim((string) ($data['priority'] ?? self::PRIORITY_LOW))),
            'description' => $data['description'],
            'attachment' => $this->storeAttachments(files: $attachments, directory: self::ATTACHMENT_DIRECTORY),
            'status' => $data['status'] ?? self::STATUS_PENDING,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $supportTicket = $this->query()->find($id);

        if ($supportTicket === null) {
            return null;
        }

        $supportTicket->update(array_filter([
            'priority' => $data['priority'] ?? null,
            'status' => $data['status'] ?? null,
        ], static fn ($value) => $value !== null));

        return $supportTicket->refresh();
    }

    public function delete(mixed $id): bool
    {
        $supportTicket = $this->query()->find($id);

        if ($supportTicket === null) {
            return false;
        }

        foreach (self::getAttachmentFileNames($supportTicket->attachment) as $fileName) {
            $this->deleteFile(filePath: self::ATTACHMENT_DIRECTORY.$fileName);
        }

        return (bool) $supportTicket->delete();
    }

    public function close(mixed $id): bool
    {
        return $this->changeStatus(id: $id, status: self::STATUS_CLOSED);
    }

    public function reopen(mixed $id): bool
    {
        return $this->changeStatus(id: $id, status: self::STATUS_OPEN);
    }

    private function changeStatus(mixed $id, string $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }
}
