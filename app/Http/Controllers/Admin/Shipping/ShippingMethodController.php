<?php

namespace App\Http\Controllers\Admin\Shipping;

use App\Enums\BusinessModule;
use App\Enums\DeliveryPartnerSetting;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Shipping\DeliveryModeUpdateRequest;
use App\Http\Requests\Admin\Shipping\MethodAddRequest;
use App\Http\Requests\Admin\Shipping\MethodUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartShippingService;
use App\Services\New\CategoryService;
use App\Services\New\CategoryShippingCostService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Traits\BusinessModuleScopeTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ShippingMethodController extends BaseController
{
    use BusinessModuleScopeTrait;

    private const CONTEXT = BusinessModule::RETAIL;

    private const IN_HOUSE_SELLER_ID = 0;

    private const INDEX_ROUTE = 'admin.settings.business-settings.shipping-method.index';

    private const SHIPPING_RESPONSIBILITY_SETTING_KEY = 'shipping_method';

    private const DEFAULT_SHIPPING_METHOD_ID = 1;

    private const CATEGORY_COST_PAGE_NAME = 'category_page';

    public function __construct(
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly CategoryService $categoryService,
        private readonly CategoryShippingCostService $categoryShippingCostService,
        private readonly CartShippingService $cartShippingService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $this->syncCategoryShippingCosts(
            categoryIds: $this->categoryService
                ->getList(dataLimit: 'all', filters: ['position' => CategoryService::POSITION_CATEGORY])
                ->pluck('id')
                ->all(),
        );

        $isCourierAddonPublished = getCheckAddonPublishedStatus(moduleName: 'Courier');

        return view('admin-views.shipping-method.index', [
            'allCategoryShippingCost' => $this->categoryShippingCosts(
                searchValue: $request['category_search'],
                page: (int) ($request[self::CATEGORY_COST_PAGE_NAME] ?? 1),
            ),
            'shippingMethods' => $this->shippingMethodService->getList(
                searchValue: $request['order_search'],
                dataLimit: getWebConfig(name: 'pagination_limit'),
                filters: ['creator_type' => ShippingMethodService::CREATOR_ADMIN, 'context' => self::CONTEXT],
                sortBy: ShippingMethodService::SORT_NEWEST,
            ),
            'adminShipping' => $this->shippingTypeService->findBy(params: [
                'seller_id' => self::IN_HOUSE_SELLER_ID,
                'context' => self::CONTEXT,
            ]),
            'isCourierAddonPublished' => $isCourierAddonPublished,
            'isThirdPartyDeliveryEnabled' => $isCourierAddonPublished
                && (bool) $this->businessSettingService->getValueByType(type: DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: self::CONTEXT)),
            'isVendorDeliveryPartnerSetupEnabled' => (bool) $this->businessSettingService->getValueByType(
                type: DeliveryPartnerSetting::vendorSetupKey(businessModule: self::CONTEXT),
                default: 1,
            ),
            'availableModules' => $this->getAvailableModules(),
            'businessModule' => $this->resolveModuleWithCommon($request['business_module'] ?? null),
            'context' => self::CONTEXT,
            'responsibilityValue' => $this->businessSettingService->getValueByType(type: self::SHIPPING_RESPONSIBILITY_SETTING_KEY),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function add(MethodAddRequest $request): RedirectResponse
    {
        $this->shippingMethodService->create(
            data: $request->all(),
            creatorId: auth('admin')->id(),
            creatorType: ShippingMethodService::CREATOR_ADMIN,
            context: self::CONTEXT,
        );

        updateSetupGuideCacheKey(key: 'shipping_method', panel: 'admin');
        ToastMagic::success(translate('Added successfully'));

        return redirect()->route(self::INDEX_ROUTE);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->shippingMethodService->updateStatus(id: $request['id'], status: (bool) $request['status']);
        updateSetupGuideCacheKey(key: 'shipping_method', panel: 'admin');

        return response()->json(['success' => 1, 'message' => translate('Status updated successfully')], status: 200);
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        if ($id == self::DEFAULT_SHIPPING_METHOD_ID) {
            return back();
        }

        $method = $this->shippingMethodService->find(id: $id);

        if ($method === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        return view('admin-views.shipping-method.update-view', [
            'method' => $method,
        ]);
    }

    public function update(MethodUpdateRequest $request, string|int $id): RedirectResponse
    {
        $this->shippingMethodService->update(id: $id, data: $request->all());
        $this->cartShippingService->updateWhere(
            params: ['shipping_method_id' => $id],
            data: ['shipping_cost' => currencyConverter($request['cost'])],
        );

        updateSetupGuideCacheKey(key: 'shipping_method', panel: 'admin');
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route(self::INDEX_ROUTE);
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->shippingMethodService->delete(id: $request['id']);

        return back();
    }

    public function updateShippingResponsibility(Request $request): RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(
            params: ['type' => self::SHIPPING_RESPONSIBILITY_SETTING_KEY],
            data: ['value' => $request['shipping_method']],
        );

        updateSetupGuideCacheKey(key: 'shipping_method', panel: 'admin');
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route(self::INDEX_ROUTE);
    }

    public function updateThirdPartyDeliveryMode(DeliveryModeUpdateRequest $request): RedirectResponse
    {
        return $this->updateDeliveryModeSetting(
            type: DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: self::CONTEXT),
            isEnabled: $request->boolean('status'),
        );
    }

    public function updateVendorDeliveryPartnerSetup(DeliveryModeUpdateRequest $request): RedirectResponse
    {
        return $this->updateDeliveryModeSetting(
            type: DeliveryPartnerSetting::vendorSetupKey(businessModule: self::CONTEXT),
            isEnabled: $request->boolean('status'),
        );
    }

    private function updateDeliveryModeSetting(string $type, bool $isEnabled): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route(self::INDEX_ROUTE);
        }

        $this->businessSettingService->updateOrInsert(
            params: ['type' => $type],
            data: ['value' => $isEnabled ? 1 : 0],
        );

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route(self::INDEX_ROUTE);
    }

    private function categoryShippingCosts(?string $searchValue, int $page): LengthAwarePaginator
    {
        return $this->categoryShippingCostService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: 'pagination_limit'),
            page: $page,
            relations: ['category'],
            filters: ['seller_id' => self::IN_HOUSE_SELLER_ID, 'context' => self::CONTEXT],
            sortBy: CategoryShippingCostService::SORT_OLDEST,
        )->setPageName(self::CATEGORY_COST_PAGE_NAME)->appends(['category_search' => $searchValue]);
    }

    /** @param array<int, int> $categoryIds
     * @throws Throwable
     */
    private function syncCategoryShippingCosts(array $categoryIds): void
    {
        $stored = $this->categoryShippingCostService
            ->getList(
                dataLimit: 'all',
                filters: ['seller_id' => self::IN_HOUSE_SELLER_ID, 'context' => self::CONTEXT],
            )
            ->pluck('category_id')
            ->all();

        $missing = array_diff($categoryIds, $stored);

        if ($missing === []) {
            return;
        }

        DB::transaction(function () use ($missing): void {
            foreach ($missing as $categoryId) {
                $this->categoryShippingCostService->create(data: [
                    'seller_id' => self::IN_HOUSE_SELLER_ID,
                    'category_id' => $categoryId,
                    'context' => self::CONTEXT,
                ]);
            }
        });
    }

    /** @return array<string, string> */
    private function routeNames(): array
    {
        return [
            'self' => self::INDEX_ROUTE,
            'responsibility' => 'admin.settings.business-settings.shipping-method.update-shipping-responsibility',
            'type' => 'admin.settings.business-settings.shipping-type.index',
            'store' => self::INDEX_ROUTE,
            'update' => 'admin.settings.business-settings.shipping-method.update',
            'status' => 'admin.settings.business-settings.shipping-method.update-status',
            'delete' => 'admin.settings.business-settings.shipping-method.delete',
            'category_store' => 'admin.settings.business-settings.category-shipping-cost.store',
            'third_party_mode' => 'admin.settings.business-settings.shipping-method.update-third-party-mode',
            'vendor_partner_setup' => 'admin.settings.business-settings.shipping-method.update-vendor-partner-setup',
        ];
    }
}
