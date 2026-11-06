<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Notification;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class NotificationService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_SELLER = 'seller';

    public const SENT_BY_ADMIN = 'admin';

    public const DEFAULT_IMAGE = 'def.png';

    public const FILTER_SINCE = 'since';

    public const FILTER_UNSEEN = 'unseen';

    private const IMAGE_DIRECTORY = 'notification/';

    public function __construct(Notification $notification)
    {
        parent::__construct($notification);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
    }

    public function pushTopic(): string
    {
        return (string) config('business_modules.modules.'.$this->businessModule().'.customer_push_topic');
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
                $inner->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%");
            }))
            ->latest('id');

        $filters = $this->applySeenFilters($query, $filters);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applySeenFilters(Builder $query, array $filters): array
    {
        if (! empty($filters[self::FILTER_SINCE])) {
            $query->where('created_at', '>=', $filters[self::FILTER_SINCE]);
        }

        if (! empty($filters[self::FILTER_UNSEEN])) {
            $query->whereDoesntHave('notificationSeenBy');
        }

        unset($filters[self::FILTER_SINCE], $filters[self::FILTER_UNSEEN]);

        return $filters;
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

    public function create(array $data, ?UploadedFile $image = null): ?Model
    {
        return DB::transaction(fn (): Model => $this->query()->create([
            ...array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ], static fn ($value) => $value !== null),
            'sent_by' => self::SENT_BY_ADMIN,
            'sent_to' => $data['sent_to'] ?? self::AUDIENCE_CUSTOMER,
            'business_module' => $this->businessModule(),
            'image' => $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image),
            'notification_count' => 1,
            'status' => 1,
        ]));
    }

    public function update(mixed $id, array $data, ?UploadedFile $image = null): ?Model
    {
        $notification = $this->query()->find($id);

        if ($notification === null) {
            return null;
        }

        return DB::transaction(function () use ($notification, $data, $image): Model {
            $columns = array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'sent_to' => $data['sent_to'] ?? null,
            ], static fn ($value) => $value !== null);

            if ($image !== null) {
                $columns['image'] = $this->updateFile(self::IMAGE_DIRECTORY, $notification->image, 'webp', $image);
            }

            $notification->update($columns);

            return $notification->refresh();
        });
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => (int) $status]);
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
        $notification = $this->query()->find($id);

        if ($notification === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($notification): bool {
            if ($notification->image) {
                $this->deleteFile(filePath: self::IMAGE_DIRECTORY.$notification->image);
            }

            return (bool) $notification->delete();
        });
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $filters = $this->applySeenFilters($query, $filters);

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function resend(mixed $id): ?Model
    {
        $notification = $this->query()->find($id);

        if ($notification === null) {
            return null;
        }

        $notification->increment('notification_count');

        return $notification->refresh();
    }

    public function getBroadcastList(
        string $audience,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        ?int $userId = null,
        ?int $sellerId = null,
        ?Carbon $joinedAt = null,
    ): LengthAwarePaginator {
        return $this->broadcastQuery($audience, $joinedAt)
            ->with(['notificationSeenBy' => fn ($query) => $this->scopeSeenToViewer($query, $userId, $sellerId)])
            ->latest('id')
            ->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function getUnseenCount(
        string $audience,
        ?int $userId = null,
        ?int $sellerId = null,
        ?Carbon $joinedAt = null,
    ): int {
        if ($userId === null && $sellerId === null) {
            return $this->broadcastQuery($audience, $joinedAt)->count();
        }

        return $this->broadcastQuery($audience, $joinedAt)
            ->whereDoesntHave('notificationSeenBy', fn ($query) => $this->scopeSeenToViewer($query, $userId, $sellerId))
            ->count();
    }

    private function broadcastQuery(string $audience, ?Carbon $joinedAt = null): Builder
    {
        return $this->query()
            ->active()
            ->where('sent_to', $audience)
            ->when($joinedAt !== null, fn (Builder $query) => $query->where('created_at', '>=', $joinedAt));
    }

    private function scopeSeenToViewer(Builder|Relation $query, ?int $userId, ?int $sellerId): Builder|Relation
    {
        return $query
            ->when($userId !== null, fn ($inner) => $inner->where('user_id', $userId))
            ->when($sellerId !== null, fn ($inner) => $inner->where('seller_id', $sellerId));
    }
}
