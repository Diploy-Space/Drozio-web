<?php

namespace Modules\Courier\app\Http\Controllers\SellerApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\ProviderRegistry;

class CourierConfigController extends Controller
{
    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly CourierConfigService $config,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['providers' => $this->providers->catalog()]);
    }

    public function update(SaveCourierProviderRequest $request): JsonResponse
    {
        $this->config->save(
            data: $request->validated(),
            owner: $this->config->getOwner(),
        );

        return response()->json([
            'ok' => true,
            'message' => translate(':item updated successfully', ['item' => translate('Delivery partner settings')]),
        ]);
    }
}
