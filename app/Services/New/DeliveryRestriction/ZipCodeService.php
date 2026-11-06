<?php

namespace App\Services\New\DeliveryRestriction;

use App\Models\DeliveryZipCode;
use App\Services\BaseService;
use App\Services\Cache\AppCache;
use Illuminate\Support\Collection;

class ZipCodeService extends BaseService
{
    public const CACHE_KEY = 'shipping:zones:zip_codes';

    public function __construct(DeliveryZipCode $deliveryZipCode)
    {
        parent::__construct($deliveryZipCode);
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
        $zipCodes = collect($this->getAllowedZipCodes());

        if ($searchValue !== null && $searchValue !== '') {
            $zipCodes = $zipCodes->filter(
                static fn (string $zipCode): bool => stripos($zipCode, $searchValue) !== false
            );
        }

        return $zipCodes->sort()->values();
    }

    public function isAllowed(string $zipCode): bool
    {
        return in_array($zipCode, $this->getAllowedZipCodes(), true);
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedZipCodes(): array
    {
        return AppCache::rememberInGroup(
            self::CACHE_KEY,
            AppCache::GROUP_SHIPPING,
            fn (): array => $this->query()->pluck('zipcode')->all(),
        );
    }
}
