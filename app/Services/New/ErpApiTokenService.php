<?php

namespace App\Services\New;

use App\Models\ErpApiToken;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ErpApiTokenService extends BaseService
{
    public const SORT_LATEST = 'latest';

    public const SORT_OLDEST = 'oldest';

    public function __construct(ErpApiToken $erpApiToken)
    {
        parent::__construct($erpApiToken);
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
                $inner->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('api_key', $searchValue);
            }));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest(),
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

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'name' => $data['name'] ?? null,
            'api_key' => $data['api_key'],
            'api_secret' => Crypt::encryptString($data['api_secret']),
            'webhook_url' => $data['webhook_url'] ?? null,
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $token = $this->query()->find($id);

        if ($token === null) {
            return null;
        }

        $token->update(array_filter([
            'name' => $data['name'] ?? null,
            'webhook_url' => $data['webhook_url'] ?? null,
            'last_used_at' => $data['last_used_at'] ?? null,
            'webhook_last_dispatched_at' => $data['webhook_last_dispatched_at'] ?? null,
        ], static fn ($value) => $value !== null));

        return $token->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_active' => $status]);
    }

    public function delete(mixed $id): bool
    {
        $token = $this->query()->find($id);

        return $token !== null && (bool) $token->delete();
    }
}
