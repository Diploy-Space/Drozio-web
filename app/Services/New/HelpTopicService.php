<?php

namespace App\Services\New;

use App\Models\HelpTopic;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HelpTopicService extends BaseService
{
    public const TYPE_DEFAULT = 'default';

    public const TYPE_VENDOR_REGISTRATION = 'vendor_registration';

    public function __construct(HelpTopic $helpTopic)
    {
        parent::__construct($helpTopic);
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
                $inner->where('question', 'like', "%{$searchValue}%")
                    ->orWhere('answer', 'like', "%{$searchValue}%");
            }))
            ->orderByDesc('id');

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

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'type' => $data['type'] ?? self::TYPE_DEFAULT,
            'question' => $data['question'],
            'answer' => $data['answer'],
            'ranking' => $data['ranking'],
            'status' => $data['status'] ?? 0,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $helpTopic = $this->query()->find($id);

        if ($helpTopic === null) {
            return null;
        }

        $helpTopic->update([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'ranking' => $data['ranking'],
            'status' => $data['status'] ?? 0,
        ]);

        return $helpTopic->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['status' => $status ? 1 : 0]);
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
        $helpTopic = $this->query()->find($id);

        if ($helpTopic === null) {
            return false;
        }

        return (bool) $helpTopic->delete();
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
}
