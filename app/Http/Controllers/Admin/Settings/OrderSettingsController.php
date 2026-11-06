<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Business\OrderUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Traits\BusinessModuleScopeTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderSettingsController extends BaseController
{
    use BusinessModuleScopeTrait;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        return view(
            'admin-views.business-settings.order-settings.index',
            $this->getOrderSetup() + [
                'availableModules' => $this->getAvailableModulesWithCommon(),
                'businessModule' => $this->resolveModuleWithCommon($request['business_module'] ?? null),
            ],
        );
    }

    public function update(OrderUpdateRequest $request): RedirectResponse
    {
        $this->storeOrderSetup(data: $request->validated());

        clearWebConfigCacheKeys();
        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route('admin.settings.business-settings.order-settings.index');
    }

    /** @return array<string, mixed> */
    private function getOrderSetup(): array
    {
        $settings = $this->businessSettingService->getList(dataLimit: 'all', filters: ['type' => [
            BusinessSettingService::ORDER_VERIFICATION,
            BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS,
            BusinessSettingService::BILLING_INPUT_BY_CUSTOMER,
            BusinessSettingService::GUEST_CHECKOUT,
        ]])->pluck('value', 'type');

        return [
            'orderVerification' => (bool) $settings->get(BusinessSettingService::ORDER_VERIFICATION),
            'minimumOrderAmountStatus' => (bool) $settings->get(BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS),
            'billingInputByCustomer' => (bool) $settings->get(BusinessSettingService::BILLING_INPUT_BY_CUSTOMER),
            'guestCheckout' => (bool) $settings->get(BusinessSettingService::GUEST_CHECKOUT),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function storeOrderSetup(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::BILLING_INPUT_BY_CUSTOMER], data: ['value' => $data[BusinessSettingService::BILLING_INPUT_BY_CUSTOMER] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS], data: ['value' => $data[BusinessSettingService::MINIMUM_ORDER_AMOUNT_STATUS] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::ORDER_VERIFICATION], data: ['value' => $data[BusinessSettingService::ORDER_VERIFICATION] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::GUEST_CHECKOUT], data: ['value' => $data[BusinessSettingService::GUEST_CHECKOUT] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::FREE_DELIVERY_STATUS], data: ['value' => $data[BusinessSettingService::FREE_DELIVERY_STATUS] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY], data: ['value' => $data[BusinessSettingService::FREE_DELIVERY_RESPONSIBILITY] ?? null]);
            $this->businessSettingService->updateOrInsert(
                params: ['type' => BusinessSettingService::FREE_DELIVERY_OVER_AMOUNT_SELLER],
                data: ['value' => currencyConverter(amount: $data[BusinessSettingService::FREE_DELIVERY_OVER_AMOUNT_SELLER] ?? 0) ?? 0],
            );
        });
    }
}
