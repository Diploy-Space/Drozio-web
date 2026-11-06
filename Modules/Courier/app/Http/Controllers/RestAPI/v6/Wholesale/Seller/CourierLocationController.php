<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Http\Controllers\Admin\CourierLocationController as SharedCourierLocationController;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\ValueObjects\CourierOwner;

class CourierLocationController extends SharedCourierLocationController
{
    private const PROVIDER_HINT = 'the delivery partner id to look the locations up with, as returned by the wholesale providers endpoint';

    #[ApiDoc(
        summary: 'Wholesale pickup stores of a delivery partner',
        description: 'The pickup points registered on the seller\'s wholesale carrier account; carriers without pickup points report `supported: false`.',
        tag: 'Wholesale Delivery Partner',
        query: ['provider' => 'nullable|string'],
        hints: ['provider' => self::PROVIDER_HINT],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function stores(Request $request): JsonResponse
    {
        return parent::stores($request);
    }

    #[ApiDoc(
        summary: 'Wholesale delivery cities of a delivery partner',
        description: 'The top-level delivery areas the seller\'s wholesale carrier account serves.',
        tag: 'Wholesale Delivery Partner',
        query: ['provider' => 'nullable|string'],
        hints: ['provider' => self::PROVIDER_HINT],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function cities(Request $request): JsonResponse
    {
        return parent::cities($request);
    }

    #[ApiDoc(
        summary: 'Wholesale delivery zones of a city',
        description: 'The zones a delivery partner serves inside one city.',
        tag: 'Wholesale Delivery Partner',
        query: ['provider' => 'nullable|string'],
        hints: ['provider' => self::PROVIDER_HINT],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function zones(Request $request, string $cityId): JsonResponse
    {
        return parent::zones($request, $cityId);
    }

    #[ApiDoc(
        summary: 'Wholesale delivery areas of a zone',
        description: 'The areas a delivery partner serves inside one zone.',
        tag: 'Wholesale Delivery Partner',
        query: ['provider' => 'nullable|string'],
        hints: ['provider' => self::PROVIDER_HINT],
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function areas(Request $request, string $zoneId): JsonResponse
    {
        return parent::areas($request, $zoneId);
    }

    protected function registry(): ProviderRegistry
    {
        return parent::registry()->forBusinessModule(CourierOwner::BUSINESS_MODULE_WHOLESALE);
    }
}
