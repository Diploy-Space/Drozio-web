<?php

namespace App\Http\Controllers\Vendor\Shipping;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\Shipping\CategoryCostUpdateRequest;
use App\Services\New\CategoryShippingCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryShippingCostController extends BaseController
{
    public function __construct(
        private readonly CategoryShippingCostService $categoryShippingCostService,
    ) {}

    public function index(?Request $request, ?string $type = null): RedirectResponse
    {
        return redirect()->route('vendor.business-settings.shipping-method.index');
    }

    public function update(CategoryCostUpdateRequest $request): JsonResponse
    {
        $vendorId = auth('seller')->id();

        DB::transaction(function () use ($request, $vendorId): void {
            foreach ($request['ids'] as $index => $categoryId) {
                $this->categoryShippingCostService->updateBy(
                    params: ['category_id' => $categoryId, 'seller_id' => $vendorId],
                    data: [
                        'cost' => $request['cost'][$index],
                        'multiply_qty' => (int) filter_var($request['multiply_qty'][$index], FILTER_VALIDATE_BOOLEAN),
                    ],
                );
            }
        });

        return response()->json([
            'status' => 1,
            'message' => translate('Category wise shipping cost updated successfully'),
        ]);
    }
}
