<?php

namespace App\Http\Controllers\Vendor\Shipping;

use App\Enums\BusinessModule;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Shipping\TypeUpdateRequest;
use App\Services\New\ShippingTypeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ShippingTypeController extends BaseController
{
    public function __construct(
        private readonly ShippingTypeService $shippingTypeService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        return null;
    }

    public function addOrUpdate(TypeUpdateRequest $request): JsonResponse
    {
        $this->shippingTypeService->updateOrInsert(
            params: ['seller_id' => auth('seller')->id(), 'context' => BusinessModule::RETAIL],
            data: ['shipping_type' => $request['shippingType']],
        );

        return response()->json([
            'status' => 1,
            'message' => translate(':item updated successfully', ['item' => translate('Shipping type')]),
        ]);
    }
}
