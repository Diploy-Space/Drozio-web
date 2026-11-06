<?php

namespace App\Services\New;

use App\Models\Currency;
use App\Services\BaseService;
use App\Traits\CalculatorTrait;
use App\Utils\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurrencyService extends BaseService
{
    use CalculatorTrait;

    public const SORT_LATEST = 'latest';

    public const SORT_NAME_ASC = 'a-z';

    public const STATUS_ACTIVE = 1;

    public const PAYMENT_GATEWAY_FALLBACK_CODE = 'USD';

    public const PAYMENT_GATEWAY_PAYSTACK = 'paystack';

    public const PAYSTACK_CURRENCY_CODES = ['GHS', 'NGN'];

    private const CURRENCY_MODEL_SETTING = 'currency_model';

    private const CURRENCY_MODEL_MULTI = 'multi_currency';

    private const DEFAULT_CURRENCY_SETTING = 'system_default_currency';

    public function __construct(Currency $currency)
    {
        parent::__construct($currency);
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
                    ->orWhere('code', 'like', "%{$searchValue}%");
            }))
            ->when($sortBy === self::SORT_LATEST, fn (Builder $builder) => $builder->latest())
            ->when($sortBy === self::SORT_NAME_ASC, fn (Builder $builder) => $builder->orderBy('name'));

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
        return (bool) $this->query()->where('id', $id)->update(['status' => $status]);
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

    public function resolveBringChangeAmountCurrencyCode(?string $currentCurrencyCode): ?string
    {
        $defaultCurrencyCode = $this->find(id: getWebConfig(name: self::DEFAULT_CURRENCY_SETTING))?->code;

        return getWebConfig(name: self::CURRENCY_MODEL_SETTING) === self::CURRENCY_MODEL_MULTI
            ? ($currentCurrencyCode ?? $defaultCurrencyCode)
            : $defaultCurrencyCode;
    }

    public function resolvePaymentGatewayCurrencyCode(array $supportedCurrencies, ?string $currentCurrency = null, ?string $gatewayKey = null): string
    {
        $supportedCodes = array_keys($supportedCurrencies);

        if ($currentCurrency && array_key_exists($currentCurrency, $supportedCurrencies) && $this->exists(params: ['code' => $currentCurrency, 'status' => self::STATUS_ACTIVE])) {
            return $currentCurrency;
        }

        if (count($supportedCodes) === 1) {
            return $this->exists(params: ['code' => $supportedCodes[0], 'status' => self::STATUS_ACTIVE])
                ? $supportedCodes[0]
                : self::PAYMENT_GATEWAY_FALLBACK_CODE;
        }

        if (count($supportedCodes) > 1) {
            $candidateCodes = $gatewayKey === self::PAYMENT_GATEWAY_PAYSTACK ? self::PAYSTACK_CURRENCY_CODES : $supportedCodes;

            $currency = $this->query()
                ->whereIn('code', $candidateCodes)
                ->where('status', self::STATUS_ACTIVE)
                ->first();

            return $currency?->code ?? self::PAYMENT_GATEWAY_FALLBACK_CODE;
        }

        return self::PAYMENT_GATEWAY_FALLBACK_CODE;
    }

    public function convertToBaseUsd(float $amount, string $currencyCode): float
    {
        if ($currencyCode === '') {
            return (float) Helpers::convert_currency_to_usd($amount);
        }

        if ($currencyCode === 'USD') {
            return $amount;
        }

        $currency = $this->findBy(params: ['code' => $currencyCode]);
        if ($currency === null || (float) $currency->exchange_rate === 0.0) {
            Log::warning('AI search: unrecognized or zero-rate currency for budget', [
                'currency_code' => $currencyCode,
            ]);

            return (float) Helpers::convert_currency_to_usd($amount);
        }

        $priceInBase = $amount / (float) $currency->exchange_rate;

        $usdCurrency = $this->findBy(params: ['code' => 'USD']);
        if ($usdCurrency !== null && (float) $usdCurrency->exchange_rate !== 0.0) {
            $priceInBase = (float) $usdCurrency->exchange_rate < 1
                ? $priceInBase * (float) $usdCurrency->exchange_rate
                : $priceInBase / (float) $usdCurrency->exchange_rate;
        }

        return $priceInBase;
    }

    public function syncExchangeRatesWithDefault(mixed $defaultCurrencyId): bool
    {
        $default = $this->find(id: $defaultCurrencyId);

        if ($default === null) {
            return false;
        }

        DB::transaction(function () use ($default): void {
            foreach ($this->getList(dataLimit: 'all') as $currency) {
                $this->update(id: $currency->id, data: [
                    'exchange_rate' => $this->getDivideWithDynamicPrecision(
                        numerator: $currency->exchange_rate,
                        denominator: $default->exchange_rate,
                        maxPrecision: 20,
                    ),
                ]);
            }
        });

        return true;
    }
}
