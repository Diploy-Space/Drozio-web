<?php

namespace App\Http\Controllers\RestAPI\v6\Shared;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Shared\BusinessPageResource;
use App\Http\Resources\V6\Shared\ConfigurationResource;
use App\Http\Resources\V6\Shared\HelpTopicResource;
use App\Services\New\BusinessPageService;
use App\Services\New\ColorService;
use App\Services\New\CurrencyService;
use App\Services\New\HelpTopicService;
use App\Services\New\LanguageService;
use App\Services\New\MaintenanceModeService;
use App\Services\New\SettingService;
use App\Services\New\ShippingTypeService;
use App\Services\New\TagService;
use App\Services\New\ThemeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\app\Services\New\AISettingService;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\Wholesale\app\Http\Resources\V6\Shared\ConfigurationResource as WholesaleConfigurationResource;
use Modules\Wholesale\app\Services\HeroSectionService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService as WholesaleShippingMethodService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService as WholesaleShippingTypeService;
use Modules\Wholesale\app\Services\TrustPolicyService;

class ConfigController extends BaseApiController
{
    private const POPULAR_TAG_LIMIT = 15;

    private const TAX_MODULE = 'TaxModule';

    private const WHOLESALE_MODULE = 'Wholesale';

    private const MODULES = [
        'ai' => 'AI',
        'auction' => 'Auction',
        'blog' => 'Blog',
        'builder' => 'Builder',
        'courier' => 'Courier',
        'tax_module' => self::TAX_MODULE,
        'wholesale' => self::WHOLESALE_MODULE,
    ];

    private const IN_HOUSE_TYPE = 'inhouse';

    private const MAINTENANCE_SYSTEMS = [
        MaintenanceModeService::SYSTEM_USER_APP,
        MaintenanceModeService::SYSTEM_USER_WEBSITE,
        MaintenanceModeService::SYSTEM_VENDOR_APP,
        MaintenanceModeService::SYSTEM_DELIVERYMAN_APP,
        MaintenanceModeService::SYSTEM_VENDOR_PANEL,
    ];

    public function __construct(
        private readonly BusinessPageService $businessPageService,
        private readonly HelpTopicService $helpTopicService,
        private readonly CurrencyService $currencyService,
        private readonly ColorService $colorService,
        private readonly TagService $tagService,
        private readonly LanguageService $languageService,
        private readonly SettingService $settingService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly MaintenanceModeService $maintenanceModeService,
        private readonly ThemeService $themeService,
        private readonly SystemTaxSetupService $systemTaxSetupService,
        private readonly AISettingService $aiSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Storefront configuration',
        description: 'Grouped storefront configuration the apps boot with: company, theme, business, currency, languages, vendor, commission, order, shipping, payment, wallet, loyalty point, referral, authentication, maintenance, app versions, in-house shop, tax, AI, media, urls, modules (every module keyed by name with its active status), auction (null when the Auction addon is not published) and wholesale (null when the Wholesale addon is not published).',
        tag: 'Config',
    )]
    public function configuration(): JsonResponse
    {
        $configuration = [
            'currencies' => $this->currencyService->getList(dataLimit: 'all'),
            'colors' => $this->colorService->getList(dataLimit: 'all'),
            'popularTags' => $this->tagService->getList(dataLimit: self::POPULAR_TAG_LIMIT, page: 1, sortBy: TagService::SORT_POPULAR)->getCollection(),
            'languages' => $this->languageService->getList(),
            'paymentGateways' => $this->settingService->getActivePaymentGateways(),
            'inHouseShippingType' => $this->shippingTypeService->resolveTypeForSeller(sellerId: ShippingTypeService::ADMIN_SELLER_ID),
            'systemTax' => $this->findSystemTax(businessModule: BusinessModule::RETAIL),
            'isAiProviderActive' => $this->aiSettingService->hasActiveProvider(),
            'isUnderMaintenance' => $this->isAnySystemUnderMaintenance(),
            'maintenanceSystems' => $this->maintenanceModeService->getSelectedSystems(),
            'maintenanceMessages' => $this->maintenanceModeService->getMessages(),
            'maintenanceDuration' => $this->maintenanceModeService->getDuration(),
            'themePanelVersion' => $this->themeService->find(id: theme_root_path())['comfortable_panel_version'] ?? '',
            'inHouseShop' => getInHouseShopConfig(),
            'modules' => $this->getModuleStatuses(),
            'wholesale' => $this->getWholesaleConfiguration(),
        ];

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ConfigurationResource::make($configuration),
        );
    }

    #[ApiDoc(
        summary: 'Business page list',
        tag: 'Config',
        query: ['type' => 'nullable|string|in:default,pages'],
    )]
    public function businessPages(Request $request): JsonResponse
    {
        $type = $request->query('type');

        $businessPages = $this->businessPageService->getList(
            dataLimit: 'all',
            relations: ['banner'],
            filters: $this->pageFilters(type: $type),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => BusinessPageResource::collection($businessPages),
                'params' => $this->paramsFormatter(['type' => $type]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Frequently asked questions',
        tag: 'Config',
    )]
    public function faq(): JsonResponse
    {
        $helpTopics = $this->helpTopicService->getList(
            dataLimit: 'all',
            filters: ['type' => HelpTopicService::TYPE_DEFAULT, 'status' => 1],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => HelpTopicResource::collection($helpTopics)],
        );
    }

    /** @return array<string, int> */
    private function pageFilters(?string $type): array
    {
        $filters = ['status' => 1];

        return match ($type) {
            BusinessPageService::TYPE_DEFAULT => $filters + ['default_status' => 1],
            BusinessPageService::TYPE_PAGES => $filters + ['default_status' => 0],
            default => $filters,
        };
    }

    private function findSystemTax(string $businessModule): ?Model
    {
        if (! getCheckAddonPublishedStatus(moduleName: self::TAX_MODULE)) {
            return null;
        }

        return $this->systemTaxSetupService->findBy(
            params: ['is_active' => 1, 'business_module' => $businessModule, 'is_default' => 1],
            relations: [],
        );
    }

    /** @return array<string, bool> */
    private function getModuleStatuses(): array
    {
        return array_map(
            fn (string $moduleName): bool => addon_published_status(moduleName: $moduleName),
            self::MODULES,
        );
    }

    private function getWholesaleConfiguration(): ?WholesaleConfigurationResource
    {
        if (! getCheckAddonPublishedStatus(moduleName: self::WHOLESALE_MODULE)) {
            return null;
        }

        return WholesaleConfigurationResource::make([
            'inHouseShippingType' => app(WholesaleShippingTypeService::class)->resolveTypeForSeller(sellerId: WholesaleShippingTypeService::ADMIN_SELLER_ID),
            'inHouseShippingMethods' => app(WholesaleShippingMethodService::class)->getActiveMethodsForSeller(
                sellerId: WholesaleShippingMethodService::IN_HOUSE_CREATOR_ID,
                sellerIs: WholesaleShippingMethodService::CREATOR_ADMIN,
            ),
            'systemTax' => $this->findSystemTax(businessModule: BusinessModule::WHOLESALE),
            'inHouseShop' => getInHouseShopConfig(),
            'isInHouseTemporaryClosed' => (bool) checkVendorAbility(type: self::IN_HOUSE_TYPE, status: 'temporary_close', businessModule: BusinessModule::WHOLESALE),
            'isInHouseOnVacation' => (bool) checkVendorAbility(type: self::IN_HOUSE_TYPE, status: 'vacation_status', businessModule: BusinessModule::WHOLESALE),
            'trustPolicyCards' => app(TrustPolicyService::class)->getStorefrontCards(),
            'heroSections' => $this->getWholesaleHeroSections(),
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function getWholesaleHeroSections(): array
    {
        $heroSectionService = app(HeroSectionService::class);
        $locale = app()->getLocale();
        $sections = [];

        foreach (HeroSectionService::SECTIONS as $section) {
            $sections[$section] = $heroSectionService->getStorefrontSection(section: $section, locale: $locale);
        }

        return $sections;
    }

    private function isAnySystemUnderMaintenance(): bool
    {
        foreach (self::MAINTENANCE_SYSTEMS as $system) {
            if ($this->maintenanceModeService->isUnderMaintenance(system: $system)) {
                return true;
            }
        }

        return false;
    }
}
