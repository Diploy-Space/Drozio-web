<?php

namespace App\Services\New\DeliveryRestriction;

use App\Models\DeliveryCountryCode;
use App\Services\BaseService;
use App\Services\Cache\AppCache;
use Illuminate\Support\Collection;

class CountryCodeService extends BaseService
{
    public const CACHE_KEY = 'shipping:zones:country_codes';

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
    ): Collection {
        $countries = $this->getAllowedCountries();

        if ($searchValue !== null && $searchValue !== '') {
            $countries = $countries->filter(
                static fn (array $country): bool => stripos($country['name'], $searchValue) !== false
                    || strcasecmp($country['code'], $searchValue) === 0
            );
        }

        return $countries->sortBy('name')->values();
    }

    public function isAllowed(string $countryName): bool
    {
        return $this->getAllowedCountries()
            ->contains(static fn (array $country): bool => strcasecmp($country['name'], $countryName) === 0);
    }

    /**
     * @return Collection<int, array{code: string, name: string}>
     */
    public function getAllowedCountries(): Collection
    {
        $namesByCode = collect(COUNTRIES)->pluck('name', 'code');

        return collect($this->getAllowedCodes())
            ->filter(static fn (string $code): bool => $namesByCode->has($code))
            ->map(static fn (string $code): array => [
                'code' => $code,
                'name' => $namesByCode[$code],
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function getAllowedCodes(): array
    {
        return AppCache::rememberInGroup(
            self::CACHE_KEY,
            AppCache::GROUP_SHIPPING,
            fn (): array => $this->query()->pluck('country_code')->all(),
        );
    }
}
