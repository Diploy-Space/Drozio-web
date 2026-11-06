<?php

namespace App\Services\New;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MapApiService
{
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const PLACE_AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';

    private const PLACE_DETAILS_URL = 'https://places.googleapis.com/v1/places/';

    private const DISTANCE_MATRIX_URL = 'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix';

    private const AUTOCOMPLETE_FIELD_MASK = 'suggestions.placePrediction.placeId,suggestions.placePrediction.text,suggestions.placePrediction.structuredFormat';

    private const PLACE_DETAILS_FIELD_MASK = 'id,displayName,formattedAddress,location,addressComponents';

    private const DISTANCE_FIELD_MASK = 'originIndex,destinationIndex,distanceMeters,duration,status,condition';

    private const GEOCODE_SUCCESS_STATUSES = ['OK', 'ZERO_RESULTS'];

    private const FAILURE = ['status' => 503, 'body' => null];

    private const CACHE_PREFIX = 'maps:';

    private const CACHE_SECONDS = 86400;

    private const CONNECT_TIMEOUT = 3;

    private const REQUEST_TIMEOUT = 8;

    /**
     * @return array{status: int, body: mixed}
     */
    public function geocode(string $apiKey, float $latitude, float $longitude): array
    {
        return $this->cached(key: "geocode:{$latitude},{$longitude}", resolver: fn (): array => $this->send(
            fn () => $this->client()->get(self::GEOCODE_URL, [
                'latlng' => $latitude.','.$longitude,
                'key' => $apiKey,
            ])
        ));
    }

    /**
     * @return array{status: int, body: mixed}
     */
    public function searchPlaces(string $apiKey, string $searchText, ?string $sessionToken = null): array
    {
        return $this->send(fn () => $this->client()
            ->withHeaders($this->placesHeaders(apiKey: $apiKey, fieldMask: self::AUTOCOMPLETE_FIELD_MASK))
            ->post(self::PLACE_AUTOCOMPLETE_URL, array_filter([
                'input' => $searchText,
                'sessionToken' => $sessionToken,
            ], static fn ($value) => $value !== null)));
    }

    /**
     * @return array{status: int, body: mixed}
     */
    public function findPlace(string $apiKey, string $placeId, ?string $sessionToken = null): array
    {
        return $this->cached(key: 'place:'.$placeId, resolver: fn (): array => $this->send(
            fn () => $this->client()
                ->withHeaders($this->placesHeaders(apiKey: $apiKey, fieldMask: self::PLACE_DETAILS_FIELD_MASK))
                ->get(self::PLACE_DETAILS_URL.rawurlencode($placeId), array_filter([
                    'sessionToken' => $sessionToken,
                ], static fn ($value) => $value !== null))
        ));
    }

    /**
     * @return array{status: int, body: mixed}
     */
    public function getDistanceMatrix(
        string $apiKey,
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
    ): array {
        return $this->send(fn () => $this->client()
            ->withHeaders($this->placesHeaders(apiKey: $apiKey, fieldMask: self::DISTANCE_FIELD_MASK))
            ->post(self::DISTANCE_MATRIX_URL, [
                'origins' => [$this->waypoint(latitude: $originLatitude, longitude: $originLongitude)],
                'destinations' => [$this->waypoint(latitude: $destinationLatitude, longitude: $destinationLongitude)],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
            ]));
    }

    /**
     * @return array{status: int, body: mixed}
     */
    private function send(callable $request): array
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return self::FAILURE;
        }

        $body = $response->json();

        if (! $response->successful() || $this->isProviderError($body)) {
            return self::FAILURE;
        }

        return ['status' => 200, 'body' => $body];
    }

    private function isProviderError(mixed $body): bool
    {
        if (! is_array($body)) {
            return true;
        }

        if (isset($body['error'])) {
            return true;
        }

        return isset($body['status'])
            && ! in_array($body['status'], self::GEOCODE_SUCCESS_STATUSES, true);
    }

    /**
     * @return array{status: int, body: mixed}
     */
    private function cached(string $key, callable $resolver): array
    {
        $cacheKey = self::CACHE_PREFIX.md5($key);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $resolver();

        if ($result['status'] === 200) {
            Cache::put($cacheKey, $result, self::CACHE_SECONDS);
        }

        return $result;
    }

    private function client(): PendingRequest
    {
        return Http::connectTimeout(self::CONNECT_TIMEOUT)->timeout(self::REQUEST_TIMEOUT);
    }

    /**
     * @return array<string, string>
     */
    private function placesHeaders(string $apiKey, string $fieldMask): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => $fieldMask,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function waypoint(float $latitude, float $longitude): array
    {
        return ['waypoint' => ['location' => ['latLng' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]]]];
    }
}
