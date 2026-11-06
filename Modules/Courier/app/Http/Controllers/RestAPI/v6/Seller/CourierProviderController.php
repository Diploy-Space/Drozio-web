<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Services\New\CourierConfigService;

class CourierProviderController extends Controller
{
    public function __construct(protected readonly CourierConfigService $config) {}

    #[ApiDoc(
        summary: 'Enabled delivery partners for the seller',
        description: 'The courier providers this seller has enabled on their retail carrier account, with their delivery-type options.',
        tag: 'Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->config->getEnabledProviderOptions()]);
    }
}
