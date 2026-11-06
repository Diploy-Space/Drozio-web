<?php

namespace App\Services\New;

use App\Models\Contact;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ContactService extends BaseService
{
    public const REPLY_STATUS_REPLIED = 'replied';

    public const REPLY_STATUS_NOT_REPLIED = 'not_replied';

    private const REPLY_FILTER_KEY = 'reply';

    public function __construct(Contact $contact)
    {
        parent::__construct($contact);
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
        $replyStatus = $filters[self::REPLY_FILTER_KEY] ?? null;
        unset($filters[self::REPLY_FILTER_KEY]);

        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('mobile_number', 'like', "%{$searchValue}%");
            }))
            ->when($replyStatus === self::REPLY_STATUS_REPLIED, fn (Builder $builder) => $builder->whereNotNull('reply'))
            ->when($replyStatus === self::REPLY_STATUS_NOT_REPLIED, fn (Builder $builder) => $builder->whereNull('reply'))
            ->orderByDesc('id');

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters + [self::REPLY_FILTER_KEY => $replyStatus]));
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
        return $this->query()->create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'mobile_number' => $data['mobile_number'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $contact = $this->query()->find($id);

        if ($contact === null) {
            return null;
        }

        $contact->update(array_filter([
            'seen' => isset($data['seen']) ? (int) $data['seen'] : null,
            'feedback' => $data['feedback'] ?? null,
            'reply' => $data['reply'] ?? null,
        ], static fn ($value) => $value !== null));

        return $contact->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function delete(mixed $id): bool
    {
        $contact = $this->query()->find($id);

        if ($contact === null) {
            return false;
        }

        return (bool) $contact->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
