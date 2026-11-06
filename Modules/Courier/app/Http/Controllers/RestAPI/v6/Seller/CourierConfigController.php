<?php

namespace Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\CourierConfigService;

class CourierConfigController extends Controller
{
    public function __construct(protected readonly CourierConfigService $config) {}

    #[ApiDoc(
        summary: 'Delivery partner catalog for the seller',
        description: 'Every registered courier provider with this owner\'s saved settings, enabled state and credential fields.',
        tag: 'Delivery Partner',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->config->getProviderCatalog()]);
    }

    #[ApiDoc(
        summary: 'Save a delivery partner\'s settings for the seller',
        description: 'Creates or updates this owner\'s credentials and enabled state for one courier provider.',
        tag: 'Delivery Partner',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(SaveCourierProviderRequest $request): JsonResponse
    {
        $this->config->save(
            data: $request->validated(),
            owner: $this->config->getOwner(),
        );

        return response()->json([
            'message' => translate(':item updated successfully', ['item' => translate('Delivery partner settings')]),
        ]);
    }
}
