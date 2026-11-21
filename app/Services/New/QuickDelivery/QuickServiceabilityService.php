<?php

namespace App\Services\New\QuickDelivery;

use App\Enums\BusinessModule;
use App\Models\Shop;
use App\Services\New\ShopService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Uses existing shops.address. No shop latitude/longitude columns or new tables.
 * Exactly matching address text can pass discovery without a geocoding API.
 * Different addresses are geocoded (Photon) and checked within the 5 km
 * straight-line radius. These checks are NOT proof of physical delivery location.
 */
class QuickServiceabilityService
{
    public function __construct(
        private readonly ShopService $shopService,
    ) {}

    public function getEligibleStores(array $customerAddress): Collection
    {
        $addressText = trim((string) ($customerAddress['address'] ?? ''));
        $customerCoordinates = $this->validCoordinates(
            $customerAddress['latitude'] ?? null,
            $customerAddress['longitude'] ?? null,
        );

        if ($addressText === '' && $customerCoordinates === null) {
            return new Collection();
        }

        $shopIds = (array) config('quick_delivery.enabled_shop_ids', []);
        $radiusKm = (float) config('quick_delivery.default_radius_km', 5);

        if ($shopIds === [] || $radiusKm <= 0) {
            return new Collection();
        }

        // IMPORTANT: require approval, and do not invert this condition.
        $shops = Shop::query()
            ->with('seller')
            ->whereIn('id', $shopIds)
            ->where('author_type', ShopService::AUTHOR_TYPE_VENDOR)
            ->whereHas('seller', fn ($query) => $query->where('status', 'approved'))
            ->limit((int) config('quick_delivery.max_candidate_stores', 100))
            ->get();

        $eligible = $shops->filter(function (Shop $shop) use ($addressText, &$customerCoordinates, $radiusKm): bool {
            if ($this->shopService->isTemporarilyClosed($shop, BusinessModule::RETAIL)
                || $this->shopService->isOnVacationNow($shop, BusinessModule::RETAIL)) {
                return false;
            }

            $shopAddress = trim((string) $shop->address);
            if ($shopAddress === '') {
                return false;
            }

            if (! $shop->products()->active()->businessModule(BusinessModule::RETAIL)->exists()) {
                return false;
            }

            // Solves the reported issue: "Kazipara, Mirpur-10, Dhaka" matches
            // "Kazipara Mirpur 10 Dhaka" WITHOUT a Google/Photon lookup.
            if ($addressText !== '' && self::sameAddress($addressText, $shopAddress)) {
                $km = 0.0;
            } else {
                $customerCoordinates ??= $addressText === '' ? null : $this->geocode($addressText);
                $shopCoordinates = $this->geocode($shopAddress);

                if ($customerCoordinates === null || $shopCoordinates === null) {
                    return false; // Do not show unrelated Retail products.
                }

                $km = self::kilometres($customerCoordinates, $shopCoordinates);
            }

            if ($km > $radiusKm) {
                return false;
            }

            $shop->setAttribute('quick_distance_km', round($km, 2));

            return true;
        })->sortBy('quick_distance_km')->values();

        return new Collection($eligible->all());
    }

    /** Normalization handles casing, punctuation, hyphens and country suffix. */
    private static function sameAddress(string $first, string $second): bool
    {
        return self::normalizeAddress($first) !== ''
            && self::normalizeAddress($first) === self::normalizeAddress($second);
    }

    private static function normalizeAddress(string $address): string
    {
        $address = function_exists('mb_strtolower')
            ? mb_strtolower(trim($address)) : strtolower(trim($address));
        $address = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $address) ?? '';
        $address = trim(preg_replace('/\s+/u', ' ', $address) ?? '');
        // Country may be absent from shops.address but appended to the form.
        return trim(preg_replace('/\s+bangladesh$/u', '', $address) ?? $address);
    }

    /**
     * Public Photon demo API: key-free but not guaranteed, suitable only for
     * low-volume discovery. Cache successful results for 30 days.
     *
     * @return array{latitude:float,longitude:float}|null
     */
    private function geocode(string $address): ?array
    {
        if (config('quick_delivery.geocoding.driver', 'none') !== 'photon') {
            return null;
        }

        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $query = $address;
        $suffix = trim((string) config('quick_delivery.geocoding.address_suffix', 'Bangladesh'));
        if ($suffix !== '' && stripos($query, $suffix) === false) {
            $query .= ', '.$suffix;
        }

        $cacheKey = 'quick:shop:geocode:v4:'.hash('sha256', function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return ($cached['found'] ?? false) ? $cached['coordinates'] : null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => (string) config('quick_delivery.geocoding.user_agent', 'DROJIO-Quick/1.0')])
                ->connectTimeout(3)
                ->timeout(8)
                ->get((string) config('quick_delivery.geocoding.endpoint', 'https://photon.komoot.io/api/'), [
                    'q' => $query,
                    'countrycode' => config('quick_delivery.geocoding.country_code', 'BD'),
                    'limit' => 5,
                    'lang' => 'en',
                ]);

            if (! $response->successful()) {
                Log::warning('Quick: geocoder HTTP failure', ['status' => $response->status()]);
                return null;
            }

            foreach ((array) $response->json('features', []) as $feature) {
                $properties = (array) ($feature['properties'] ?? []);
                $country = strtoupper((string) ($properties['countrycode'] ?? ''));
                if ($country !== '' && $country !== strtoupper((string) config('quick_delivery.geocoding.country_code', 'BD'))) {
                    continue;
                }

                // Do not use city/country centroids for a strict 5 km decision.
                $type = strtolower((string) ($properties['osm_value'] ?? $properties['type'] ?? ''));
                if (in_array($type, ['country', 'state', 'county', 'city', 'town'], true)) {
                    continue;
                }

                $lon = $feature['geometry']['coordinates'][0] ?? null;
                $lat = $feature['geometry']['coordinates'][1] ?? null;
                $coordinates = $this->validCoordinates($lat, $lon);
                if ($coordinates !== null) {
                    Cache::put($cacheKey, ['found' => true, 'coordinates' => $coordinates], now()->addDays(30));
                    return $coordinates;
                }
            }

            Cache::put($cacheKey, ['found' => false], now()->addMinutes(3));
            return null;
        } catch (Throwable $exception) {
            Log::warning('Quick: geocoder request failed', ['type' => get_class($exception)]);
            return null;
        }
    }

    /** @return array{latitude:float,longitude:float}|null */
    private function validCoordinates(mixed $latitude, mixed $longitude): ?array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;
        if (! is_finite($lat) || ! is_finite($lon)
            || abs($lat) > 90 || abs($lon) > 180
            || ($lat === 0.0 && $lon === 0.0)) {
            return null;
        }

        return ['latitude' => $lat, 'longitude' => $lon];
    }

    /** Straight-line distance, not a road-distance or 30-minute ETA. */
    private static function kilometres(array $a, array $b): float
    {
        $lat1 = deg2rad($a['latitude']);
        $lat2 = deg2rad($b['latitude']);
        $deltaLat = deg2rad($b['latitude'] - $a['latitude']);
        $deltaLon = deg2rad($b['longitude'] - $a['longitude']);
        $h = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return 6371.0088 * 2 * asin(min(1.0, sqrt(max(0.0, $h))));
    }
}
