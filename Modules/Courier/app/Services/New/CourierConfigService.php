<?php

namespace Modules\Courier\app\Services\New;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Courier\app\Models\CourierProviderSetting;
use Modules\Courier\app\ValueObjects\CourierOwner;

class CourierConfigService extends BaseService
{
    private const SETTING_KEYS = ['environment', 'country'];

    public function __construct(CourierProviderSetting $courierProviderSetting)
    {
        parent::__construct($courierProviderSetting);
    }

    protected function query(): Builder
    {
        return $this->queryForOwner($this->getOwner());
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('provider', 'like', "%{$searchValue}%"))
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
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
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $setting = $this->query()->find($id);

        if ($setting === null) {
            return null;
        }

        $setting->update($data);

        return $setting->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_active' => $status]);
    }

    public function delete(mixed $id): bool
    {
        $setting = $this->query()->find($id);

        if ($setting === null) {
            return false;
        }

        return (bool) $setting->delete();
    }

    public function findByProvider(string $provider, CourierOwner $owner): ?Model
    {
        return $this->queryForOwner($owner)->where('provider', $provider)->first();
    }

    public function getOwner(): CourierOwner
    {
        return $this->registry()->owner();
    }

    public function getEnabledProviderOptions(): array
    {
        return $this->registry()->enabledOptions();
    }

    /** @return array<string, string> */
    public function getBusinessModules(): array
    {
        return $this->registry()->businessModules();
    }

    public function getProviderCatalog(): array
    {
        return $this->registry()->catalog();
    }

    /** @param  array<string, mixed>  $data */
    public function save(array $data, CourierOwner $owner): Model
    {
        $setting = $this->model->newQuery()->firstOrNew([
            'owner_type' => $owner->type,
            'owner_id' => $owner->id,
            'business_module' => $owner->businessModule,
            'provider' => $data['provider'],
        ]);

        $setting->is_active = (bool) ($data['is_enabled'] ?? false);

        if (array_key_exists('credentials', $data)) {
            $setting->credentials = (array) ($data['credentials'] ?? []);
        }

        $settings = (array) $setting->settings;

        foreach (self::SETTING_KEYS as $key) {
            if (($data[$key] ?? null) !== null) {
                $settings[$key] = $data[$key];
            }
        }

        $setting->settings = $settings;
        $setting->save();

        return $setting;
    }

    protected function registry(): ProviderRegistry
    {
        return app(ProviderRegistry::class);
    }

    private function queryForOwner(CourierOwner $owner): Builder
    {
        return $this->model->newQuery()->forOwner($owner);
    }
}
