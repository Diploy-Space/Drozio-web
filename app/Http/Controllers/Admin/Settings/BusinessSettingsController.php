<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Events\MaintenanceModeNotificationEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Product\SettingsUpdateRequest;
use App\Http\Requests\Admin\Setting\Business\UpdateRequest;
use App\Http\Requests\Admin\Setting\ThirdParty\AnalyticsUpdateRequest;
use App\Http\Requests\Admin\Setting\Website\DeepLinkUpdateRequest;
use App\Services\New\AnalyticScriptService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CurrencyService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\SettingService;
use App\Traits\BusinessModuleScopeTrait;
use App\Traits\SettingsTrait;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BusinessSettingsController extends BaseController
{
    private const CURRENCY_SESSION_KEYS = ['usd', 'default', 'system_default_currency_info', 'currency_code', 'currency_symbol', 'currency_exchange_rate'];

    /** @var array<int, string> */
    private const MAINTENANCE_PLATFORMS = ['user_app', 'user_website', 'vendor_app', 'deliveryman_app', 'vendor_panel'];

    private const BUSINESS_MODE_MULTI = 'multi';

    private const MAINTENANCE_MESSAGE_LIMIT = 100;

    private const MAINTENANCE_MESSAGE_BODY_LIMIT = 255;

    private const MAINTENANCE_MODE_ON = 'on';

    private const MAINTENANCE_MODE_OFF = 'off';

    use BusinessModuleScopeTrait;
    use SettingsTrait;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly AnalyticScriptService $analyticScriptService,
        private readonly CurrencyService $currencyService,
        private readonly SettingService $settingService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $systemCurrency = $this->currencyService->find(id: $this->businessSettingService->findBy(params: ['type' => 'system_default_currency'])?->value ?? '');

        return view(
            'admin-views.business-settings.website-info',
            $this->getWebsiteInfoSetup(
                gatewayCurrencySupport: $this->settingService->activeGatewaySupportsCurrency(currencyName: $systemCurrency?->name),
            ) + [
                'CurrencyList' => $this->currencyService->getList(dataLimit: 'all'),
                'systemCurrency' => $systemCurrency,
                'checkMinimumOneDigitalPayment' => $this->settingService->hasActiveDigitalPaymentGateway(),
                'availableModules' => $this->getAvailableModulesWithCommon(),
                'businessModule' => $this->resolveModuleWithCommon($request['business_module'] ?? null),
            ],
        );
    }

    public function updateSettings(UpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disabled for demo'));

            return redirect()->route('admin.settings.business-settings.web-config.index');
        }

        $this->updateWebsiteInfoSetup(data: $request->all());
        updateSetupGuideCacheKey(key: 'general_setup', panel: 'admin');
        $this->updateSystemCurrency(currencyId: $request['currency_id']);

        $paymentResponse = $this->updatePaymentOption(request: $request);
        if ($paymentResponse instanceof RedirectResponse) {
            return $paymentResponse;
        }

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route('admin.settings.business-settings.web-config.index');
    }

    public function updateSystemMode(Request $request): RedirectResponse|JsonResponse
    {
        if (config('app.mode') == 'demo') {
            return $this->maintenanceModeResponse($request, translate('You cannot update this on demo mode'), updated: false);
        }

        if (! $this->updateMaintenanceMode(data: $request->all())) {
            return $this->maintenanceModeResponse($request, translate('Please select minimum one system'), updated: false);
        }

        return $this->maintenanceModeResponse(
            $request,
            $request->get('maintenance_mode', 0) ? translate('Maintenance mode is on') : translate('Maintenance mode is off'),
            updated: true,
        );
    }

    public function getAppSettingsView(): View
    {
        $userApp = $this->businessSettingService->findBy(params: ['type' => 'user_app_version_control']);
        $userAppVersionControl = $userApp ? json_decode($userApp['value'], true) : [];
        $sellerApp = $this->businessSettingService->findBy(params: ['type' => 'seller_app_version_control']);
        $sellerAppVersionControl = $sellerApp ? json_decode($sellerApp['value'], true) : [];
        $deliverymanApp = $this->businessSettingService->findBy(params: ['type' => 'delivery_man_app_version_control']);
        $deliverymanAppVersionControl = $deliverymanApp ? json_decode($deliverymanApp['value'], true) : [];

        return view('admin-views.system-setup.apps-settings', compact('userAppVersionControl', 'sellerAppVersionControl', 'deliverymanAppVersionControl'));
    }

    public function updateAppSettings(Request $request): RedirectResponse
    {
        if (in_array($request['type'], ['user_app_version_control', 'seller_app_version_control', 'delivery_man_app_version_control'])) {
            $this->businessSettingService->updateOrInsert(params: ['type' => $request['type']], data: ['value' => json_encode([
                'for_android' => $request['for_android'],
                'for_ios' => $request['for_ios'],
            ])]);
        }
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function getAppDeepLinkView(): View
    {
        $getDeeplink = $this->businessSettingService->findBy(params: ['type' => 'app_deep_link']);
        $deeplink = json_decode($getDeeplink?->value ?? '[]', true);
        $fileStatus = [
            'base_exists' => file_exists(base_path('.well-known/assetlinks.json')),
            'public_exists' => file_exists(public_path('.well-known/assetlinks.json')),
            'base_writable' => is_writable(base_path()),
            'public_writable' => is_writable(public_path()),
        ];

        return view('admin-views.system-setup.app-deep-link', compact('deeplink', 'fileStatus'));
    }

    public function updateAppDeepLink(DeepLinkUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return back();
        }
        try {
            $this->businessSettingService->updateOrInsert(
                params: ['type' => $request['type']],
                data: ['value' => json_encode([
                    'android_package_name' => $request['android_package_name'],
                    'ios_bundle_id' => $request['ios_bundle_id'],
                    'ios_team_id' => $request['ios_team_id'],
                    'android_sha256_fingerprint' => $request['android_sha256_fingerprint'],
                    'playstore_redirect_url' => $request['playstore_redirect_url'],
                    'app_store_redirect_url' => $request['app_store_redirect_url'],
                ])]);

            $this->createDeepLinkFiles($request);
            ToastMagic::success(translate('Updated successfully'));
        } catch (Exception $e) {
            ToastMagic::error(translate('Update failed').': '.$e->getMessage());
        }

        return back();
    }

    /** @throws Exception */
    protected function createDeepLinkFiles($request): void
    {
        $filePaths = [
            base_path('.well-known'),
            public_path('.well-known'),
        ];

        foreach ($filePaths as $wellKnownPath) {
            if (! file_exists($wellKnownPath)) {
                if (! mkdir($wellKnownPath, 0755, true)) {
                    throw new Exception('Failed to create .well-known directory at: '.$wellKnownPath);
                }
            }
            if (! is_writable($wellKnownPath)) {
                throw new Exception('.well-known directory is not writable at: '.$wellKnownPath);
            }
            $this->createAssetLinksFile($wellKnownPath, $request);
            $this->createAppleAppSiteAssociation($wellKnownPath, $request);
        }
    }

    protected function createAssetLinksFile($path, $request): void
    {
        $assetLinks = [
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => $request['android_package_name'],
                    'sha256_cert_fingerprints' => [
                        $request['android_sha256_fingerprint'],
                    ],
                ],
            ],
        ];
        $filePath = $path.'/assetlinks.json';
        if (file_put_contents($filePath, json_encode($assetLinks, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Failed to create assetlinks.json at: '.$filePath);
        }
        chmod($filePath, 0644);
    }

    protected function createAppleAppSiteAssociation($path, $request): void
    {
        $appleAppSiteAssociation = [
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => $request['ios_team_id'].'.'.$request['ios_bundle_id'],
                        'paths' => config('deeplinks.ios_paths', ['*']),
                    ],
                ],
            ],
        ];

        $filePath = $path.'/apple-app-site-association';
        if (file_put_contents($filePath, json_encode($appleAppSiteAssociation, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Failed to create apple-app-site-association at: '.$filePath);
        }
        chmod($filePath, 0644);
    }

    public function updateCookieSetting(Request $request): RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => 'cookie_setting'], data: ['value' => json_encode([
            'status' => $request->get('status', 0),
            'cookie_text' => $request['cookie_text'],
        ])]);
        ToastMagic::success(translate('Cookie settings updated successfully'));

        return redirect()->back();
    }

    public function getAnalyticsView(): View
    {
        $analytics = $this->analyticScriptService->getList(dataLimit: 'all');
        $analyticsData = [];
        foreach ($analytics as $analytic) {
            $analyticsData[$analytic['type']] = $analytic;
        }

        return view('admin-views.third-party.analytics.index', compact('analyticsData'));
    }

    public function updateAnalytics(AnalyticsUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::info(translate('This option is disabled for demo'));

            return back();
        }

        if (empty($request['script_id']) && $request['is_active'] == 1) {
            $type = str_replace(' ', '_', ucwords(str_replace('_', ' ', $request['type'])));
            ToastMagic::error(translate('Please ensure you have filled in the :type script ID', ['type' => $type]));

            return back();
        }

        $this->analyticScriptService->updateOrInsert(params: ['type' => $request['type']], data: [
            'name' => ucwords(str_replace('_', ' ', $request['type'])),
            'type' => $request['type'],
            'script_id' => $request['script_id'],
            'is_active' => $request['is_active'] ?? 0,
        ]);

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function getProductSettingsView(Request $request): View
    {
        $digitalProduct = $this->businessSettingService->findBy(params: ['type' => 'digital_product']);
        $brand = $this->businessSettingService->findBy(params: ['type' => 'product_brand']);
        $stockLimit = $this->businessSettingService->findBy(params: ['type' => 'stock_limit']);

        return view('admin-views.business-settings.product-settings', [
            'digitalProduct' => $digitalProduct,
            'brand' => $brand,
            'stockLimit' => $stockLimit,
            'availableModules' => $this->getAvailableModulesWithCommon(),
            'businessModule' => $this->resolveModuleWithCommon($request['business_module'] ?? null),
        ]);
    }

    public function updateProductSettings(SettingsUpdateRequest $request): RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => 'stock_limit'], data: ['value' => $request->get('stock_limit', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'product_brand'], data: ['value' => $request->get('product_brand', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'digital_product'], data: ['value' => $request->get('digital_product', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'new_product_approval'], data: ['value' => $request->get('new_product_approval', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'product_wise_shipping_cost_approval'], data: ['value' => $request->get('product_wise_shipping_cost_approval', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'product_recommendation_setup'], data: ['value' => json_encode([
            'status' => $request->boolean('product_recommendation_status') ? 1 : 0,
        ])]);

        clearWebConfigCacheKeys();
        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function getAnnouncementView(): View
    {
        $announcement = json_decode((string) $this->businessSettingService->findBy(params: ['type' => 'announcement'])?->value, true);

        return view('admin-views.business-settings.website-announcement', compact('announcement'));
    }

    public function updateAnnouncement(Request $request): RedirectResponse
    {
        $value = json_encode([
            'status' => $request['announcement_status'],
            'color' => $request['announcement_color'],
            'text_color' => $request['text_color'],
            'announcement' => $request['announcement'],
        ]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'announcement'], data: ['value' => $value]);
        ToastMagic::success(translate('Announcement updated successfully'));

        return back();
    }

    public function getRefundSetupView(): View
    {
        return view('admin-views.business-settings.refund-setup');
    }

    public function updateRefundSetup(Request $request): RedirectResponse
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => 'refund_day_limit'], data: ['value' => $request->get('refund_day_limit', 0)]);
        $this->businessSettingService->updateOrInsert(params: ['type' => 'wallet_add_refund'], data: ['value' => $request->get('wallet_add_refund', 0)]);
        ToastMagic::success(translate('Refund config updated successfully'));

        return back();
    }

    private function updateSystemCurrency(mixed $currencyId): void
    {
        $this->businessSettingService->updateOrInsert(params: ['type' => 'system_default_currency'], data: ['value' => $currencyId]);

        if ($this->businessSettingService->findBy(params: ['type' => 'currency_model'])?->value === 'multi_currency') {
            $this->currencyService->syncExchangeRatesWithDefault(defaultCurrencyId: $currencyId);
        }

        foreach (self::CURRENCY_SESSION_KEYS as $sessionKey) {
            session()->forget($sessionKey);
        }
    }

    private function updatePaymentOption(mixed $request): ?RedirectResponse
    {
        if ($request['digital_payment'] == 1 && ! $this->settingService->hasActiveDigitalPaymentGateway() && $request['offline_payment'] != 1) {
            ToastMagic::warning(translate('You must activate one of the digital payment methods'));

            return redirect()->back();
        }

        if ($request['offline_payment'] == 1 && ! $this->offlinePaymentMethodService->hasActiveMethod()) {
            ToastMagic::warning(translate('You must activate one of the offline payment methods'));

            return redirect()->back();
        }

        $this->updatePaymentOptions(data: $request->all());

        return null;
    }

    private function maintenanceModeResponse(Request $request, string $message, bool $updated): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json(['message' => $message, 'status' => $updated ? 'success' : 'error']);
        }

        $updated ? ToastMagic::success($message) : ToastMagic::error($message);

        return back();
    }

    /** @return array<string, mixed> */
    private function getWebsiteInfoSetup(bool $gatewayCurrencySupport): array
    {
        $web = $this->businessSettingService->getList(dataLimit: 'all');
        $colors = json_decode($this->settingValue($web, 'colors'), true) ?? [];
        $maintenanceDuration = json_decode($this->settingValue($web, 'maintenance_duration_setup'), true) ?? [];
        $maintenanceSystemSetup = json_decode($this->settingValue($web, 'maintenance_system_setup'), true) ?? [];
        $maintenanceSystems = $this->maintenanceSystems(
            businessMode: $this->settingValue($web, 'business_mode'),
            selected: $maintenanceSystemSetup,
        );

        return [
            'businessSetting' => [
                'primary_color' => $colors['primary'] ?? '',
                'secondary_color' => $colors['secondary'] ?? '',
                'primary_color_light' => $colors['primary_light'] ?? '',
                'company_name' => $this->settingValue($web, 'company_name'),
                'company_email' => $this->settingValue($web, 'company_email'),
                'company_phone' => $this->settingValue($web, 'company_phone'),
                'language' => $this->settingValue($web, 'language'),
                'web_logo' => $this->settingValue($web, 'company_web_logo'),
                'mob_logo' => $this->settingValue($web, 'company_mobile_logo'),
                'fav_icon' => $this->settingValue($web, 'company_fav_icon'),
                'footer_logo' => $this->settingValue($web, 'company_footer_logo'),
                'shop_address' => $this->settingValue($web, 'shop_address'),
                'company_copyright_text' => $this->settingValue($web, 'company_copyright_text'),
                'system_default_currency' => $this->settingValue($web, 'system_default_currency'),
                'gateway_currency_support' => $gatewayCurrencySupport,
                'currency_symbol_position' => $this->settingValue($web, 'currency_symbol_position'),
                'business_mode' => $this->settingValue($web, 'business_mode'),
                'email_verification' => $this->settingValue($web, 'email_verification'),
                'otp_verification' => $this->settingValue($web, 'otp_verification'),
                'guest_checkout' => $this->settingValue($web, 'guest_checkout'),
                'pagination_limit' => $this->settingValue($web, 'pagination_limit'),
                'copyright_text' => $this->settingValue($web, 'company_copyright_text'),
                'decimal_point_settings' => $this->settingValue($web, 'decimal_point_settings', 0),
                'loader_gif' => $this->settingValue($web, 'loader_gif'),
                'default_location' => json_decode($this->settingValue($web, 'default_location'), true) ?? [],
                'maintenance_mode' => $this->settingValue($web, 'maintenance_mode', 0),
            ],
            'maintenanceSystemSetup' => $maintenanceSystemSetup,
            'maintenanceSystems' => $maintenanceSystems,
            'maintenanceAllSystemsSelected' => $maintenanceSystems !== []
                && count(array_filter($maintenanceSystems, fn (array $system): bool => $system['is_selected'])) === count($maintenanceSystems),
            'selectedMaintenanceDuration' => $maintenanceDuration,
            'maintenanceDurations' => $this->maintenanceDurations(selected: $maintenanceDuration['maintenance_duration'] ?? null),
            'maintenanceMessageLimit' => self::MAINTENANCE_MESSAGE_LIMIT,
            'maintenanceMessageBodyLimit' => self::MAINTENANCE_MESSAGE_BODY_LIMIT,
            'maintenanceStartDate' => isset($maintenanceDuration['start_date']) ? Carbon::parse($maintenanceDuration['start_date']) : null,
            'maintenanceEndDate' => isset($maintenanceDuration['end_date']) ? Carbon::parse($maintenanceDuration['end_date']) : null,
            'selectedMaintenanceMessage' => json_decode($this->settingValue($web, 'maintenance_message_setup'), true) ?? [],
            'cashOnDelivery' => json_decode($this->settingValue($web, 'cash_on_delivery'), true),
            'digitalPayment' => json_decode($this->settingValue($web, 'digital_payment'), true),
            'offlinePayment' => json_decode($this->settingValue($web, 'offline_payment'), true),
            'cookieSetting' => json_decode($this->settingValue($web, 'cookie_setting'), true),
        ];
    }

    /** @return array<int, array{key: string, label: string, is_selected: bool}> */
    private function maintenanceDurations(?string $selected): array
    {
        $labels = [
            'one_day' => translate('For 24 Hours'),
            'one_week' => translate('For 1 Week'),
            'until_change' => translate('Until i Change'),
            'customize' => translate('Customize'),
        ];

        $durations = [];
        foreach ($labels as $key => $label) {
            $durations[] = [
                'key' => $key,
                'label' => $label,
                'is_selected' => $selected === $key,
            ];
        }

        return $durations;
    }

    /**
     * @param  array<string, mixed>  $selected
     * @return array<int, array{key: string, label: string, is_selected: bool}>
     */
    private function maintenanceSystems(mixed $businessMode, array $selected): array
    {
        $labels = [
            'user_website' => translate('User website'),
            'user_app' => translate('User app'),
            'deliveryman_app' => translate('Deliveryman App'),
        ];

        if ($businessMode === self::BUSINESS_MODE_MULTI) {
            $labels['vendor_panel'] = translate('Vendor panel');
            $labels['vendor_app'] = translate('Vendor app');
        }

        $systems = [];
        foreach ($labels as $key => $label) {
            $systems[] = [
                'key' => $key,
                'label' => $label,
                'is_selected' => (bool) ($selected[$key] ?? false),
            ];
        }

        return $systems;
    }

    private function settingValue(Collection $web, string $type, mixed $default = ''): mixed
    {
        return $this->getSettings($web, $type)?->value ?? $default;
    }

    /** @param  array<string, mixed>  $data */
    private function updateWebsiteInfoSetup(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => 'company_name'], data: ['value' => $data['company_name']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'company_email'], data: ['value' => $data['company_email']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'company_phone'], data: ['value' => $data['company_phone']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'country_code'], data: ['value' => $data['country_code']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'shop_address'], data: ['value' => $data['shop_address']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'pagination_limit'], data: ['value' => $data['pagination_limit']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'download_app_apple_store'], data: ['value' => json_encode([
                'status' => $data['app_store_download_status'] ?? 0,
                'link' => $data['app_store_download_url'] ?? '',
            ])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'download_app_google_store'], data: ['value' => json_encode([
                'status' => $data['play_store_download_status'] ?? 0,
                'link' => $data['play_store_download_url'] ?? '',
            ])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'default_location'], data: ['value' => json_encode([
                'lat' => $data['latitude'],
                'lng' => $data['longitude'],
            ])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'timezone'], data: ['value' => $data['timezone']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'currency_symbol_position'], data: ['value' => $data['currency_symbol_position']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'decimal_point_settings'], data: ['value' => $data['decimal_point_settings'] ?? 0]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'business_mode'], data: ['value' => $data['business_mode']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'company_copyright_text'], data: ['value' => $data['company_copyright_text']]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'cookie_setting'], data: ['value' => json_encode([
                'status' => $data['cookie_status'] ?? 0,
                'cookie_text' => $data['cookie_text'],
            ])]);
        });
    }

    /** @param  array<string, mixed>  $data */
    private function updatePaymentOptions(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => 'cash_on_delivery'], data: ['value' => json_encode(['status' => $data['cash_on_delivery'] ?? 0])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'digital_payment'], data: ['value' => json_encode(['status' => $data['digital_payment'] ?? 0])]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'offline_payment'], data: ['value' => json_encode(['status' => $data['offline_payment'] ?? 0])]);
        });
    }

    /** @param  array<string, mixed>  $data */
    private function updateMaintenanceMode(array $data): bool
    {
        $selectedSystems = [];
        foreach (self::MAINTENANCE_PLATFORMS as $platform) {
            $selectedSystems[$platform] = $data[$platform] ?? 0;
        }

        $status = $data['maintenance_mode'] ?? 0;

        if ($status == 1 && array_sum($selectedSystems) == 0) {
            return false;
        }

        $duration = [
            'maintenance_duration' => $data['maintenance_duration'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];
        $messageSetup = [
            'business_number' => isset($data['business_number']) ? 1 : 0,
            'business_email' => isset($data['business_email']) ? 1 : 0,
            'maintenance_message' => $data['maintenance_message'] ?? null,
            'message_body' => $data['message_body'] ?? null,
        ];

        DB::transaction(function () use ($selectedSystems, $status, $duration, $messageSetup): void {
            $this->businessSettingService->updateOrInsert(params: ['type' => 'maintenance_system_setup'], data: ['value' => json_encode($selectedSystems)]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'maintenance_mode'], data: ['value' => $status]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'maintenance_duration_setup'], data: ['value' => json_encode($duration)]);
            $this->businessSettingService->updateOrInsert(params: ['type' => 'maintenance_message_setup'], data: ['value' => json_encode($messageSetup)]);
        });

        $status == 1
            ? Cache::put('system_maintenance_mode', [
                'status' => $status,
                'start_date' => $duration['start_date'],
                'end_date' => $duration['end_date'],
                'selectedSystems' => $selectedSystems,
                'maintenance_duration' => $duration,
                'maintenance_message' => $messageSetup,
            ], now()->addYears(1))
            : Cache::forget('system_maintenance_mode');

        $this->sendMaintenanceModeNotifications(selectedSystems: $selectedSystems, status: $status);

        return true;
    }

    /** @param  array<string, mixed>  $selectedSystems */
    private function sendMaintenanceModeNotifications(array $selectedSystems, mixed $status): void
    {
        $statusValue = $status ? self::MAINTENANCE_MODE_ON : self::MAINTENANCE_MODE_OFF;

        if ($selectedSystems['user_app']) {
            $this->sendMaintenanceModeNotification(status: $statusValue, topic: 'maintenance_mode_start_user_app');
        }

        if ($this->businessSettingService->findBy(params: ['type' => 'business_mode'])?->value == 'multi' && $selectedSystems['vendor_app']) {
            $this->sendMaintenanceModeNotification(status: $statusValue, topic: 'maintenance_mode_start_vendor');
        }

        if ($selectedSystems['deliveryman_app']) {
            $this->sendMaintenanceModeNotification(status: $statusValue, topic: 'maintenance_mode_start_deliveryman');
        }
    }

    private function sendMaintenanceModeNotification(string $status, string $topic): void
    {
        event(new MaintenanceModeNotificationEvent(data: [
            'topic' => $topic,
            'key' => $topic,
            'subject' => translate('Maintenance Mode'),
            'title' => $status === self::MAINTENANCE_MODE_ON ? translate('Maintenance Mode start') : translate('Maintenance Mode End'),
            'description' => $status === self::MAINTENANCE_MODE_ON ? translate('We are currently undergoing maintenance') : translate('Maintenance mode turned off'),
            'type' => 'maintenance_mode',
            'user_name' => null,
            'userData' => null,
        ]));
    }
}
