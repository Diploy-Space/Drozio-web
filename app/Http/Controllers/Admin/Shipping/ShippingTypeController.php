<?php

namespace App\Http\Controllers\Admin\Shipping;

use App\Enums\BusinessModule;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Shipping\TypeUpdateRequest;
use App\Services\New\ShippingTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShippingTypeController extends BaseController
{
    private const CONTEXT = BusinessModule::RETAIL;

    private const IN_HOUSE_SELLER_ID = 0;

    public function __construct(
        private readonly ShippingTypeService $shippingTypeService,
    ) {}

    public function index(?Request $request, ?string $type = null): RedirectResponse|JsonResponse
    {
        return $this->addOrUpdate($request);
    }

    public function addOrUpdate(TypeUpdateRequest $request): JsonResponse
    {
        $shippingType = $this->shippingTypeService->findBy(params: [
            'seller_id' => self::IN_HOUSE_SELLER_ID,
            'context' => self::CONTEXT,
        ]);

        $shippingType === null
            ? $this->shippingTypeService->create(data: [
                'seller_id' => self::IN_HOUSE_SELLER_ID,
                'shipping_type' => $request['shippingType'],
                'context' => self::CONTEXT,
            ])
            : $this->shippingTypeService->update(id: $shippingType->id, data: [
                'shipping_type' => $request['shippingType'],
            ]);

        cacheRemoveByType(type: 'business_settings');
        updateSetupGuideCacheKey(key: 'shipping_method', panel: 'admin');

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Shipping type')]),
        ]);
    }
}
