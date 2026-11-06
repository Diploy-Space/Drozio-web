<?php

namespace App\Services\New;

use App\Models\Color;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ColorService extends BaseService
{
    public const SORT_NAME_ASC = 'a-z';

    public const SORT_NAME_DESC = 'z-a';

    public function __construct(Color $color)
    {
        parent::__construct($color);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection {
        $query = $this->query()->with($relations)->withCount($withCount);

        match ($sortBy) {
            self::SORT_NAME_ASC => $query->orderBy('name'),
            self::SORT_NAME_DESC => $query->orderByDesc('name'),
            default => $query,
        };

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    /** @return array<string, string> */
    public function getNameMap(): array
    {
        return $this->query()->pluck('name', 'code')->all();
    }

    /** @param array<int, string> $codes @return array<string, string> */
    public function getNameMapForCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return $this->query()->whereIn('code', $codes)->pluck('name', 'code')->all();
    }

    /** @return array<string, string> */
    public function getCodeMapByName(): array
    {
        return $this->query()->orderByDesc('id')->pluck('code', 'name')->all();
    }
}
