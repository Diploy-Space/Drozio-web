<?php

namespace App\Services\New;

use App\Enums\GlobalConstant;
use App\Models\Setting;
use App\Services\BaseService;
use App\Traits\PaymentGatewayTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SettingService extends BaseService
{
    use PaymentGatewayTrait;

    public const PAYMENT_CONFIG_TYPE = 'payment_config';

    public const SORT_LATEST = 'latest';

    public const SORT_KEY_NAME_ASC = 'a-z';

    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('key_name', 'like', "%{$searchValue}%"))
            ->when($sortBy === self::SORT_LATEST, fn (Builder $builder) => $builder->latest())
            ->when($sortBy === self::SORT_KEY_NAME_ASC, fn (Builder $builder) => $builder->orderBy('key_name'));

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)->appends(['searchValue' => $searchValue]);
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
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return (bool) $this->query()->where('id', $id)->update(['is_active' => $status]);
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

        if ($record === null) {
            return false;
        }

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

    public function findByKeyName(string $keyName, ?string $settingsType = null, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where('key_name', $keyName)
            ->when($settingsType, fn (Builder $builder) => $builder->where('settings_type', $settingsType))
            ->first();
    }

    public function hasActiveDigitalPaymentGateway(): bool
    {
        $gatewayKeys = array_column(GATEWAYS_PAYMENT_METHODS, 'key');

        return $this->getActiveGateways(
            config('get_payment_publish_status') ? $gatewayKeys : GlobalConstant::DEFAULT_PAYMENT_GATEWAYS,
        )->isNotEmpty();
    }

    public function activeGatewaySupportsCurrency(?string $currencyName): bool
    {
        $digitalPayment = getWebConfig(name: 'digital_payment');

        if (empty($digitalPayment['status'])) {
            return true;
        }

        $activeGateways = $this->getActiveGateways(
            config('get_payment_publish_status') ? null : GlobalConstant::DEFAULT_PAYMENT_GATEWAYS,
        );

        foreach ($activeGateways->pluck('key_name') as $gateway) {
            $supportedCurrencies = $this->getPaymentGatewaySupportedCurrencies(key: $gateway);

            if ($supportedCurrencies && array_key_exists($currencyName, $supportedCurrencies)) {
                return true;
            }
        }

        return false;
    }

    public function getActivePaymentGateways(): Collection
    {
        return $this->getActiveGateways(
            config('get_payment_publish_status') ? null : GlobalConstant::DEFAULT_PAYMENT_GATEWAYS,
        );
    }

    /** @param  array<int, string>|null  $keyNames */
    private function getActiveGateways(?array $keyNames): Collection
    {
        return $this->getList(dataLimit: 'all', filters: array_filter([
            'settings_type' => self::PAYMENT_CONFIG_TYPE,
            'is_active' => 1,
            'key_name' => $keyNames,
        ], static fn ($value) => $value !== null));
    }
}
