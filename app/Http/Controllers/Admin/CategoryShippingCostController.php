<?php

namespace App\Http\Controllers\Admin;

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
        return redirect()->route('admin.settings.business-settings.shipping-method.index');
    }

    public function add(CategoryCostUpdateRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request['ids'] as $index => $categoryId) {
                $this->categoryShippingCostService->updateBy(
                    params: ['category_id' => $categoryId, 'seller_id' => CategoryShippingCostService::IN_HOUSE_SELLER_ID],
                    data: [
                        'cost' => $request['cost'][$index],
                        'multiply_qty' => (int) filter_var($request['multiply_qty'][$index], FILTER_VALIDATE_BOOLEAN),
                    ],
                );
            }
        });

        return response()->json([
            'status' => 1,
            'message' => translate('Category cost updated successfully'),
        ]);
    }
}
