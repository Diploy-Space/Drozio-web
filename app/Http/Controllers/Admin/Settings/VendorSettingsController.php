<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Business\RetailVendorUpdateRequest;
use App\Http\Requests\Admin\Setting\Business\VendorUpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Traits\BusinessModuleScopeTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorSettingsController extends BaseController
{
    private const RETAIL_COMMISSION_KEY = 'sales_commission';

    private const RETAIL_VENDOR_WISE_COMMISSION_KEY = 'retail_vendor_wise_commission_status';

    private const BUSINESS_MODE_MULTI = 'multi';

    use BusinessModuleScopeTrait;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $sales_commission = $this->businessSettingService->findBy(params: ['type' => 'sales_commission']);
        if (! isset($sales_commission)) {
            $this->businessSettingService->create(data: ['type' => 'sales_commission', 'value' => 0]);
        }

        $seller_registration = $this->businessSettingService->findBy(params: ['type' => 'seller_registration']);
        if (! isset($seller_registration)) {
            $this->businessSettingService->create(data: ['type' => 'seller_registration', 'value' => 1]);
        }

        return view('admin-views.business-settings.seller-settings', [
            'availableModules' => $this->getAvailableModulesWithCommon(),
            'businessModule' => $this->resolveModule($request['business_module'] ?? null),
            'verifiedBadgeStatus' => (bool) $this->businessSettingService->getValueByType(type: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS, default: 0),
        ]);
    }

    public function update(VendorUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(params: ['type' => 'seller_registration'], data: ['value' => $request['seller_registration'] ?? 0]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'vendor_forgot_password_method'], data: ['value' => $request['vendor_forgot_password_method'] ?? 'phone']);
        $this->businessSettingService->updateOrInsert(params: ['type' => WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS], data: ['value' => $request[WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS] ?? 0]);

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }

    public function retailIndex(): View
    {
        return view('admin-views.business-settings.retail-vendor-settings', [
            'availableModules' => $this->getAvailableModulesWithCommon(),
            'businessModule' => BusinessModule::RETAIL,
            'isMultiVendorMode' => $this->businessSettingService->getValueByType(type: 'business_mode') === self::BUSINESS_MODE_MULTI,
            'retailCommissionSetup' => [
                'commissionValue' => $this->businessSettingService->getValueByType(type: self::RETAIL_COMMISSION_KEY, default: 0),
                'vendorWiseStatus' => (bool) $this->businessSettingService->getValueByType(type: self::RETAIL_VENDOR_WISE_COMMISSION_KEY),
            ],
        ]);
    }

    public function updateRetail(RetailVendorUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return back();
        }

        $this->businessSettingService->updateOrInsert(params: ['type' => 'seller_pos'], data: ['value' => $request['seller_pos'] ?? 0]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'minimum_order_amount_by_seller'], data: ['value' => $request['minimum_order_amount_by_seller'] ?? 0]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'vendor_review_reply_status'], data: ['value' => $request['vendor_review_reply_status'] ?? 0]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'vendor_can_edit_order'], data: ['value' => $request['vendor_can_edit_order'] ?? 0]);

        if ($this->businessSettingService->getValueByType(type: 'business_mode') === self::BUSINESS_MODE_MULTI) {
            $this->businessSettingService->updateOrInsert(params: ['type' => self::RETAIL_COMMISSION_KEY], data: ['value' => $request[self::RETAIL_COMMISSION_KEY] ?? 0]);
            $this->businessSettingService->updateOrInsert(
                params: ['type' => self::RETAIL_VENDOR_WISE_COMMISSION_KEY],
                data: ['value' => empty($request[self::RETAIL_VENDOR_WISE_COMMISSION_KEY]) ? 0 : 1],
            );
        }

        if (function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Builder')) {
            $this->businessSettingService->updateOrInsert(params: ['type' => 'admin_website_builder_status'], data: ['value' => $request['admin_website_builder_status'] ?? 0]);
        }

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->back();
    }
}
