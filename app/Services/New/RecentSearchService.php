<?php

namespace App\Services\New;

use App\Models\RecentSearch;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RecentSearchService extends BaseService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(RecentSearch $recentSearch)
    {
        parent::__construct($recentSearch);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('title', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest()->oldest('id'),
            default => $query->latest()->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        return $record !== null && (bool) $record->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return $this->query()->toBase()->updateOrInsert($params, $data);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupResultsByType(iterable $recentSearches): array
    {
        $results = collect();

        foreach ($recentSearches as $recentSearch) {
            $response = is_string($recentSearch->response)
                ? json_decode($recentSearch->response, true)
                : $recentSearch->response;

            if (is_array($response) && isset($response['priority'])) {
                $results->push([...$response, 'search_keyword' => (string) $recentSearch->keyword]);
            }
        }

        return $results->sortBy('priority')->values()->groupBy('type')->toArray();
    }
}
