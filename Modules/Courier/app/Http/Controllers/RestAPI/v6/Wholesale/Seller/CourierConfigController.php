<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller;

use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierConfigController as RetailCourierConfigController;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\Wholesale\CourierConfigService;

class CourierConfigController extends RetailCourierConfigController
{
    public function __construct(CourierConfigService $config)
    {
        parent::__construct(config: $config);
    }

    #[ApiDoc(
        summary: 'Wholesale delivery partner catalog for the seller',
        description: 'Every registered courier provider with this seller\'s saved wholesale settings, enabled state and credential fields. The wholesale carrier account is separate from the retail one.',
        tag: 'Wholesale Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function index(): JsonResponse
    {
        return parent::index();
    }

    #[ApiDoc(
        summary: 'Save a wholesale delivery partner\'s settings for the seller',
        description: 'Creates or updates this seller\'s wholesale credentials and enabled state for one courier provider, leaving their retail account untouched.',
        tag: 'Wholesale Delivery Partner',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(SaveCourierProviderRequest $request): JsonResponse
    {
        return parent::update($request);
    }
}
