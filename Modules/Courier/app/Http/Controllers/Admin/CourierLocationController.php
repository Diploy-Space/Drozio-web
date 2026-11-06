<?php

namespace Modules\Courier\app\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Courier\app\DataTransferObjects\Responses\Location;
use Modules\Courier\app\Exceptions\CourierException;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\CourierProviders\Contracts\ProvidesStores;
use Modules\Courier\CourierProviders\Contracts\ResolvesLocations;

class CourierLocationController extends Controller
{
    public function __construct(private readonly ProviderRegistry $providers) {}

    public function cities(Request $request): JsonResponse
    {
        return $this->respond($request, fn (ResolvesLocations $d) => $d->getCities());
    }

    public function zones(Request $request, string $cityId): JsonResponse
    {
        return $this->respond($request, fn (ResolvesLocations $d) => $d->getZones($cityId));
    }

    public function areas(Request $request, string $zoneId): JsonResponse
    {
        return $this->respond($request, fn (ResolvesLocations $d) => $d->getAreas($zoneId));
    }

    public function stores(Request $request): JsonResponse
    {
        return $this->respond($request, fn (ProvidesStores $d) => $d->getStores(), ProvidesStores::class);
    }

    private function respond(Request $request, callable $fetch, string $capability = ResolvesLocations::class): JsonResponse
    {
        $provider = (string) $request->query('provider', '');

        if (! $this->registry()->isProviderEnabled($provider)) {
            return response()->json(['supported' => false, 'options' => []]);
        }

        $driver = $this->registry()->driver($provider);

        if (! $driver instanceof $capability) {
            return response()->json(['supported' => false, 'options' => []]);
        }

        try {
            $locations = $fetch($driver);
        } catch (CourierException $exception) {
            return response()->json(['supported' => true, 'options' => [], 'message' => $exception->getMessage()]);
        }

        $options = array_map(
            static fn (Location $location) => ['id' => $location->id, 'name' => $location->name],
            $locations,
        );

        return response()->json(['supported' => true, 'options' => $options]);
    }

    protected function registry(): ProviderRegistry
    {
        return $this->providers;
    }
}
