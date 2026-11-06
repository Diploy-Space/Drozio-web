<?php

namespace App\Http\Controllers\Vendor\Shipping;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Shipping\MethodAddRequest;
use App\Http\Requests\Vendor\Shipping\MethodUpdateRequest;
use App\Services\New\CartShippingService;
use App\Services\New\CategoryService;
use App\Services\New\CategoryShippingCostService;
use App\Services\New\OrderService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class ShippingMethodController extends BaseController
{
    private const CONTEXT = BusinessModule::RETAIL;

    private const DEFAULT_SHIPPING_TYPE = 'order_wise';

    private const CATEGORY_COST_PAGE_NAME = 'category_page';

    private const METHOD_SEARCH_PARAM = 'search';

    public function __construct(
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly CategoryService $categoryService,
        private readonly CategoryShippingCostService $categoryShippingCostService,
        private readonly CartShippingService $cartShippingService,
        private readonly OrderService $orderService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $vendorId = auth('seller')->id();
        $methodSearchValue = $request[self::METHOD_SEARCH_PARAM];

        $this->syncCategoryShippingCosts(
            categoryIds: $this->categoryService->getList(dataLimit: 'all')->pluck('id')->all(),
            sellerId: $vendorId,
            context: self::CONTEXT,
        );

        return view('vendor-views.shipping-method.index', [
            'shippingMethods' => $this->shippingMethodService->getList(
                searchValue: $methodSearchValue,
                dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
                filters: [
                    'creator_id' => $vendorId,
                    'creator_type' => ShippingMethodService::CREATOR_SELLER,
                    'context' => self::CONTEXT,
                ],
                sortBy: ShippingMethodService::SORT_NEWEST,
            )->appends(['searchValue' => null, self::METHOD_SEARCH_PARAM => $methodSearchValue]),
            'methodSearchParam' => self::METHOD_SEARCH_PARAM,
            'methodSearchValue' => $methodSearchValue,
            'methodSearchClearUrl' => $request->fullUrlWithoutQuery([self::METHOD_SEARCH_PARAM, 'page']),
            'allCategoryShippingCost' => $this->categoryShippingCostService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
                page: (int) ($request[self::CATEGORY_COST_PAGE_NAME] ?? 1),
                relations: ['category'],
                filters: ['seller_id' => $vendorId, 'context' => self::CONTEXT],
                sortBy: CategoryShippingCostService::SORT_NEWEST,
            )->setPageName(self::CATEGORY_COST_PAGE_NAME),
            'shippingType' => $this->shippingTypeService->findBy(params: ['seller_id' => $vendorId, 'context' => self::CONTEXT])?->shipping_type
                ?? self::DEFAULT_SHIPPING_TYPE,
            'context' => self::CONTEXT,
            'routeNames' => $this->routeNames(),
            'wholesaleShippingAvailable' => $this->isWholesaleShippingAvailable(),
        ]);
    }

    public function add(MethodAddRequest $request): RedirectResponse
    {
        $this->shippingMethodService->create(
            data: $request->all(),
            creatorId: auth('seller')->id(),
            creatorType: ShippingMethodService::CREATOR_SELLER,
            context: self::CONTEXT,
        );
        ToastMagic::success(translate('Order wise shipping method added successfully'));

        return back();
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $shippingMethod = $this->ownMethod(id: $id);

        if ($shippingMethod === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        return view('vendor-views.shipping-method.update-view', [
            'shippingMethod' => $shippingMethod,
            'updateRouteName' => 'vendor.business-settings.shipping-method.update',
        ]);
    }

    public function update(MethodUpdateRequest $request, string|int $id): RedirectResponse
    {
        if ($this->ownMethod(id: $id) === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $this->shippingMethodService->update(id: $id, data: $request->all());
        $this->updateCartShippingCosts(shippingMethodId: $id, cost: $request['cost']);
        ToastMagic::success(translate('Shipping method status updated successfully'));

        return redirect()->route('vendor.business-settings.shipping-method.index');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        if ($this->ownMethod(id: $request['id']) === null) {
            return response()->json(['message' => translate('Not found'), 'success' => 0]);
        }

        $this->shippingMethodService->updateStatus(id: $request['id'], status: (bool) $request['status']);

        return response()->json([
            'message' => translate('Shipping method status updated successfully'),
            'success' => 1,
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        if ($this->ownMethod(id: $request['id']) === null) {
            return response()->json(['status' => 0, 'message' => translate('Not found')]);
        }

        if ($this->orderService->isShippingMethodUsedInAnyBusinessModule(shippingMethodId: (int) $request['id'])) {
            return response()->json([
                'status' => 0,
                'message' => translate('This shipping method is used by orders. Turn it off instead.'),
            ]);
        }

        $this->shippingMethodService->delete(id: $request['id']);

        return response()->json([
            'status' => 1,
            'message' => translate('Shipping method deleted successfully'),
        ]);
    }

    private function ownMethod(mixed $id): mixed
    {
        return $this->shippingMethodService->findBy(params: [
            'id' => $id,
            'creator_id' => auth('seller')->id(),
            'creator_type' => ShippingMethodService::CREATOR_SELLER,
            'context' => self::CONTEXT,
        ]);
    }

    private function updateCartShippingCosts(mixed $shippingMethodId, mixed $cost): void
    {
        $cartShipping = $this->cartShippingService->getList(
            dataLimit: 'all',
            filters: ['shipping_method_id' => $shippingMethodId],
        );

        if ($cartShipping->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($cartShipping, $cost): void {
            foreach ($cartShipping as $cartShippingItem) {
                $this->cartShippingService->update(id: $cartShippingItem->id, data: ['shipping_cost' => $cost]);
            }
        });
    }

    /** @param array<int, int> $categoryIds */
    private function syncCategoryShippingCosts(array $categoryIds, mixed $sellerId, string $context): void
    {
        $stored = $this->categoryShippingCostService
            ->getList(dataLimit: 'all', filters: ['seller_id' => $sellerId, 'context' => $context])
            ->pluck('category_id')
            ->all();

        $missing = array_diff($categoryIds, $stored);

        if (! $missing) {
            return;
        }

        DB::transaction(function () use ($missing, $sellerId, $context): void {
            foreach ($missing as $categoryId) {
                $this->categoryShippingCostService->create(data: [
                    'seller_id' => $sellerId,
                    'category_id' => $categoryId,
                    'context' => $context,
                ]);
            }
        });
    }

    /** @return array<string, string> */
    private function routeNames(): array
    {
        return [
            'type' => 'vendor.business-settings.shipping-type.index',
            'store' => 'vendor.business-settings.shipping-method.index',
            'update' => 'vendor.business-settings.shipping-method.update',
            'status' => 'vendor.business-settings.shipping-method.update-status',
            'delete' => 'vendor.business-settings.shipping-method.delete',
            'category_store' => 'vendor.business-settings.category-wise-shipping-cost.index',
        ];
    }

    private function isWholesaleShippingAvailable(): bool
    {
        return function_exists('businessModuleManager')
            && businessModuleManager()->isAvailableInPanel('wholesale')
            && Route::has('vendor.wholesale.shipping-method.wholesale');
    }
}
