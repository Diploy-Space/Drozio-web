<?php

namespace App\Services\New;

use App\Enums\GlobalConstant;
use App\Models\DeliveryCountryCode;
use App\Services\BaseService;
use App\Services\Cache\AppCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DeliveryCountryCodeService extends BaseService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const COUNTRY_CODES_CACHE_KEY = 'shipping:zones:country_codes';

    public function __construct(DeliveryCountryCode $deliveryCountryCode)
    {
        parent::__construct($deliveryCountryCode);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('country_code', 'like', "%{$searchValue}%"));

        match ($sortBy) {
            self::SORT_OLDEST => $query->oldest('id'),
            default => $query->latest('id'),
        };

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    /** @return array<int|string, array{code: string, name: string}> */
    public function getDeliveryCountryArray(?object $deliveryCountryCodes): array
    {
        $countries = [];

        foreach ($deliveryCountryCodes ?? [] as $deliveryCountryCode) {
            foreach (GlobalConstant::COUNTRIES as $key => $country) {
                if ($country['code'] === $deliveryCountryCode['country_code']) {
                    $countries[$key] = ['code' => $country['code'], 'name' => $country['name']];
                }
            }
        }

        return $countries;
    }

    public function hasCountryName(?string $countryName): bool
    {
        $countryCodes = AppCache::rememberInGroup(
            self::COUNTRY_CODES_CACHE_KEY,
            AppCache::GROUP_SHIPPING,
            fn () => $this->query()->pluck('country_code')->toArray(),
        );

        $countryNames = [];

        foreach ($countryCodes as $countryCode) {
            foreach (COUNTRIES as $country) {
                if ($country['code'] == $countryCode) {
                    $countryNames[] = $country['name'];
                }
            }
        }

        return in_array($countryName, $countryNames);
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
        return $this->query()->create(['country_code' => $data['country_code']]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update(array_filter([
            'country_code' => $data['country_code'] ?? null,
        ], static fn ($value) => $value !== null));

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
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
        $record = $this->query()->find($id);

        return $record === null ? false : (bool) $record->delete();
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
