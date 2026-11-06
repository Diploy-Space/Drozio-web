<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller;

use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierProviderController as RetailCourierProviderController;
use Modules\Courier\app\Services\New\Wholesale\CourierConfigService;

class CourierProviderController extends RetailCourierProviderController
{
    public function __construct(CourierConfigService $config)
    {
        parent::__construct(config: $config);
    }

    #[ApiDoc(
        summary: 'Enabled wholesale delivery partners for the seller',
        description: 'The courier providers this seller has enabled on their wholesale carrier account, with their delivery-type options.',
        tag: 'Wholesale Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function index(): JsonResponse
    {
        return parent::index();
    }
}
