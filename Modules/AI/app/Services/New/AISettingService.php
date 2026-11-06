<?php

namespace Modules\AI\app\Services\New;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Models\AISetting;

class AISettingService extends BaseService
{
    public const ACTIVE_PROVIDER_CACHE_KEY = 'active_ai_provider';

    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    /** @var array<string, mixed> */
    private const DEFAULT_CONFIGURATION = [
        'ai_name' => 'OpenAI',
        'api_key' => '',
        'organization_id' => '',
        'image_upload_limit' => 0,
        'generate_limit' => 0,
        'customer_generate_limit' => 0,
        'customer_image_upload_limit' => 0,
        'status' => 0,
    ];

    public function __construct(AISetting $aiSetting)
    {
        parent::__construct($aiSetting);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('ai_name', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_LATEST => $query->latest(),
            self::SORT_NAME_ASC => $query->orderBy('ai_name'),
            default => $query->latest(),
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
        return $this->query()->with($relations)->where($params)->first();
    }

    public function hasActiveProvider(): bool
    {
        return $this->query()
            ->where('status', 1)
            ->whereNotNull('api_key')
            ->where('api_key', '!=', '')
            ->exists();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        return $this->query()->create($data + self::DEFAULT_CONFIGURATION);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
    }

    public function updateWhere(array $params, array $data): int
    {
        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        Cache::forget(self::ACTIVE_PROVIDER_CACHE_KEY);

        return (bool) $record->delete();
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
