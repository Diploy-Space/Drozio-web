<?php

namespace App\Http\Controllers\RestAPI\v6\Shared;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\MapApi\DistanceApiRequest;
use App\Http\Requests\Shared\MapApi\GeocodeApiRequest;
use App\Http\Requests\Shared\MapApi\PlaceApiAutocompleteRequest;
use App\Http\Requests\Shared\MapApi\PlaceApiDetailsRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\MapApiService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class MapController extends BaseApiController
{
    public function __construct(
        private readonly MapApiService $mapApiService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Reverse geocode a coordinate',
        description: 'Answers 503 when Google Maps is switched off in the admin panel or no server key is configured.',
        tag: 'Map',
        status: [422, 503],
        auth: false,
    )]
    public function geocode(GeocodeApiRequest $request): JsonResponse
    {
        $apiKey = $this->availableApiKey();

        if ($apiKey === null) {
            return $this->mapsUnavailable();
        }

        return $this->mapResponse($this->mapApiService->geocode(
            apiKey: $apiKey,
            latitude: (float) $request['lat'],
            longitude: (float) $request['lng'],
        ));
    }

    #[ApiDoc(
        summary: 'Search places by text',
        description: 'Pass the same session_token across the keystrokes of one address lookup and into the place details call, so Google bills the session once instead of per keystroke.',
        tag: 'Map',
        status: [422, 503],
        auth: false,
    )]
    public function searchPlaces(PlaceApiAutocompleteRequest $request): JsonResponse
    {
        $apiKey = $this->availableApiKey();

        if ($apiKey === null) {
            return $this->mapsUnavailable();
        }

        return $this->mapResponse($this->mapApiService->searchPlaces(
            apiKey: $apiKey,
            searchText: $request['search_text'],
            sessionToken: $request['session_token'],
        ));
    }

    #[ApiDoc(
        summary: 'Details of one place',
        tag: 'Map',
        status: [422, 503],
        auth: false,
    )]
    public function findPlace(PlaceApiDetailsRequest $request): JsonResponse
    {
        $apiKey = $this->availableApiKey();

        if ($apiKey === null) {
            return $this->mapsUnavailable();
        }

        return $this->mapResponse($this->mapApiService->findPlace(
            apiKey: $apiKey,
            placeId: $request['placeid'],
            sessionToken: $request['session_token'],
        ));
    }

    #[ApiDoc(
        summary: 'Driving distance and duration between two points',
        tag: 'Map',
        status: [422, 503],
        auth: false,
    )]
    public function getDistance(DistanceApiRequest $request): JsonResponse
    {
        $apiKey = $this->availableApiKey();

        if ($apiKey === null) {
            return $this->mapsUnavailable();
        }

        return $this->mapResponse($this->mapApiService->getDistanceMatrix(
            apiKey: $apiKey,
            originLatitude: (float) $request['origin_lat'],
            originLongitude: (float) $request['origin_lng'],
            destinationLatitude: (float) $request['destination_lat'],
            destinationLongitude: (float) $request['destination_lng'],
        ));
    }

    private function mapResponse(array $result): JsonResponse
    {
        if ($result['status'] !== 200) {
            return $this->authError(
                config: config('response.map_service_no_response'),
                errorCode: AuthErrorCode::MAP_SERVICE_UNAVAILABLE,
            );
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $result['body']],
        );
    }

    private function availableApiKey(): ?string
    {
        if (! (bool) $this->businessSettingService->getValueByType(type: 'map_api_status', default: 0)) {
            return null;
        }

        $apiKey = (string) $this->businessSettingService->getValueByType(type: 'map_api_key_server', default: '');

        return $apiKey === '' ? null : $apiKey;
    }

    private function mapsUnavailable(): JsonResponse
    {
        return $this->authError(
            config: config('response.map_service_unavailable'),
            errorCode: AuthErrorCode::MAP_SERVICE_UNAVAILABLE,
        );
    }
}
