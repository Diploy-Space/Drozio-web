<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\BaseController;
use App\Services\New\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SystemController extends BaseController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse {}

    public function getOrderData(): JsonResponse
    {
        $newOrder = $this->orderService->count(filters: [
            'seller_is' => OrderService::SELLER_IS_SELLER,
            'seller_id' => auth('seller')->id(),
            'checked' => 0,
        ]);

        return response()->json([
            'success' => 1,
            'data' => ['new_order' => $newOrder],
        ]);
    }
}
