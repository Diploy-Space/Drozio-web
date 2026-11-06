<?php

namespace App\View\Composers;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Services\New\AnalyticScriptService;
use App\Services\New\AuthorService;
use App\Services\New\BannerService;
use App\Services\New\BrandService;
use App\Services\New\BusinessPageService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\CurrencyService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\LoginSetupService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\RobotsMetaContentService;
use App\Services\New\SettingService;
use App\Services\New\ShopService;
use App\Services\New\SocialMediaService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WebConfigComposer
{
    private const BUSINESS_MODULE = BusinessModule::RETAIL;

    public const BANNER_POPUP = 'Popup Banner';

    public const BANNER_HEADER = 'Header Banner';

    private const ABOUT_US_SLUG = 'about-us';

    private const META_DESCRIPTION_LENGTH = 160;

    private const SHOP_LIMIT = 9;

    private const FLASH_DEAL_LIMIT = 10;

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    private ?bool $isBusinessSettingsTableAvailable = null;

    private bool $isDatabaseBehindCode = false;

    public function __construct(
        private readonly BusinessSettingService $businessSettingService,
        private readonly CategoryService $retailCategoryService,
        private readonly BrandService $retailBrandService,
        private readonly BannerService $bannerService,
        private readonly CurrencyService $currencyService,
        private readonly SocialMediaService $socialMediaService,
        private readonly LoginSetupService $loginSetupService,
        private readonly SettingService $settingService,
        private readonly BusinessPageService $businessPageService,
        private readonly AnalyticScriptService $analyticScriptService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly ShopService $shopService,
        private readonly ProductRetailService $retailProductService,
        private readonly FlashDealService $flashDealService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly AuthorService $authorService,
    ) {}

    public function compose(View $view): void
    {
        if ($this->isDatabaseBehindCode || ! ($this->isBusinessSettingsTableAvailable ??= $this->businessSettingService->isTableAvailable())) {
            return;
        }

        try {
            $this->resolved ??= $this->build();
        } catch (QueryException $exception) {
            $this->isDatabaseBehindCode = true;
            Log::warning('Web config was not shared because the database query failed, run the pending migrations: '.$exception->getMessage());

            return;
        }

        $view->with('web_config', $this->resolved);
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $colors = $this->setting('colors') ?: [];
        $firebaseOtp = $this->setting('firebase_otp_verification');

        return [
            'primary_color' => $colors['primary'] ?? '',
            'secondary_color' => $colors['secondary'] ?? '',
            'primary_color_light' => $colors['primary_light'] ?? '',
            'panel_sidebar_color' => $colors['panel-sidebar'] ?? '',
            'name' => $this->settingRow('company_name'),
            'company_name' => $this->setting('company_name'),
            'phone' => $this->setting('company_phone'),
            'web_logo' => $this->setting('company_web_logo'),
            'mob_logo' => $this->setting('company_mobile_logo'),
            'fav_icon' => $this->setting('company_fav_icon'),
            'email' => $this->setting('company_email'),
            'about' => $this->settingRow('about_us'),
            'footer_logo' => $this->setting('company_footer_logo'),
            'copyright_text' => $this->setting('company_copyright_text'),
            'decimal_point_settings' => $this->setting('decimal_point_settings') ?: 0,
            'seller_registration' => $this->setting('seller_registration') ?? 0,
            'wallet_status' => $this->setting('wallet_status'),
            'loyalty_point_status' => $this->setting('loyalty_point_status'),
            'guest_checkout_status' => $this->setting('guest_checkout'),
            'digital_product_setting' => $this->setting('digital_product'),
            'language' => $this->setting('language'),
            'publishing_houses_header' => $this->publishingHouses(),
            'publishing_houses' => $this->publishingHouses(),
            'digital_product_authors' => $this->authors(),
            'firebase_otp_verification' => $firebaseOtp,
            'firebase_otp_verification_status' => (int) ($firebaseOtp && ($firebaseOtp['status'] ?? false) && ($firebaseOtp['web_api_key'] ?? false)),
            'meta_title' => $this->setting('company_name').' '.translate('Online Shopping').' | '.$this->setting('company_name').' '.translate('eCommerce'),
            'meta_description' => $this->metaDescription(),
            'cookie_setting' => $this->settingRow('cookie_setting'),
            'announcement' => $this->setting('announcement'),
            'wholesale_announcement' => $this->setting('wholesale_announcement'),
            'auction_announcement' => $this->setting('auction_announcement'),
            'currency_model' => $this->setting('currency_model'),
            'currencies' => $this->currencyService->getList(dataLimit: 'all', filters: ['status' => 1]),
            'main_categories' => $this->retailCategoryService->getList(
                dataLimit: 'all',
                position: CategoryService::POSITION_CATEGORY,
                withProductCounts: true,
            ),
            'priority_wise_brands' => $this->retailBrandService->getList(dataLimit: 'all', filters: ['status' => 1], sortBy: BrandService::SORT_PRIORITY),
            'business_mode' => $this->setting('business_mode'),
            'social_media' => $this->socialMediaService->getList(dataLimit: 'all', filters: ['active_status' => 1]),
            'ios' => $this->setting('download_app_apple_store'),
            'android' => $this->setting('download_app_google_store'),
            'refund_policy' => $this->setting('refund-policy'),
            'return_policy' => $this->setting('return-policy'),
            'cancellation_policy' => $this->setting('cancellation-policy'),
            'shipping_policy' => $this->setting('shipping-policy'),
            'flash_deals' => $this->flashDeal(),
            'flash_deals_products' => $this->flashDealProducts(),
            'shops' => $this->shopService->getList(dataLimit: self::SHOP_LIMIT, relations: ['seller'], filters: [ShopService::FILTER_SELLER_APPROVED => true]),
            'brand_setting' => $this->setting('product_brand'),
            'discount_product' => $this->discountProductCount(),
            'recaptcha' => $this->setting('recaptcha'),
            'socials_login' => $this->setting('social_login'),
            'social_login_text' => $this->hasEnabledSocialLogin(),
            'popup_banner' => $this->banner(self::BANNER_POPUP),
            'header_banner' => $this->banner(self::BANNER_HEADER),
            'payments_list' => $this->paymentGateways(),
            'ref_earning_status' => $this->setting('ref_earning_status'),
            'customer_login_options' => $this->customerLoginOptions(),
            'customer_social_login_options' => $this->socialLoginOptions(),
            'customer_phone_verification' => getLoginConfig(key: 'phone_verification'),
            'customer_email_verification' => getLoginConfig(key: 'email_verification'),
            'default_meta_content' => $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::DEFAULT_PAGE_NAME]),
            'analytic_scripts' => $this->analyticScriptService->getList(dataLimit: 'all', filters: ['is_active' => 1]),
            'clearance_sale_product_count' => $this->stockClearanceProductService->count([StockClearanceProductService::FILTER_RUNNING => true]),
            'business_pages' => $this->businessPageService->getList(dataLimit: 'all', filters: ['status' => 1]),
        ];
    }

    private function setting(string $type): mixed
    {
        return getWebConfig(name: $type);
    }

    private function publishingHouses(): mixed
    {
        return $this->publishingHouseService->getListRankedByProducts(
            unattachedProductCount: $this->unattachedDigitalCount($this->publishingHouseService->getAttachedProductIds()),
        );
    }

    private function authors(): mixed
    {
        return $this->authorService->getListRankedByProducts(
            unattachedProductCount: $this->unattachedDigitalCount($this->authorService->getAttachedProductIds()),
        );
    }

    /** @param  array<int, int>  $attachedProductIds */
    private function unattachedDigitalCount(array $attachedProductIds): int
    {
        return $this->retailProductService->count([
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_EXCLUDE_IDS => $attachedProductIds,
            'product_type' => ProductRetailService::PRODUCT_TYPE_DIGITAL,
        ]);
    }

    private function settingRow(string $type): mixed
    {
        return $this->businessSettingService->findBy(params: ['type' => $type]);
    }

    private function metaDescription(): string
    {
        $about = $this->businessPageService->findBy(params: ['slug' => self::ABOUT_US_SLUG])?->description ?? '';

        return substr(strip_tags(str_replace('&nbsp;', ' ', $about)), 0, self::META_DESCRIPTION_LENGTH);
    }

    private function banner(string $type): mixed
    {
        return $this->bannerService->findBy(
            params: [
                'banner_type' => $type,
                'published' => 1,
                'theme' => theme_root_path(),
                'business_module' => self::BUSINESS_MODULE,
            ],
            relations: ['storage'],
        );
    }

    private function flashDeal(): mixed
    {
        return $this->flashDealService->getRunningDeals(relations: ['seo'], withCount: ['products'], locale: getDefaultLanguage())->first();
    }

    private function flashDealProducts(): mixed
    {
        $deal = $this->flashDeal();

        if ($deal === null) {
            return [];
        }

        return $this->retailProductService->getList(
            dataLimit: self::FLASH_DEAL_LIMIT,
            relations: ['compareList', 'clearanceSale', 'seller.shop', 'rating', 'reviews'],
            withCount: ['orderDetails', 'reviews'],
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                ProductRetailService::FILTER_FLASH_DEAL => $deal->id,
            ],
            sortBy: FlashDealService::PRIORITY_TYPE,
        );
    }

    private function discountProductCount(): int
    {
        return $this->retailProductService->count([
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_DISCOUNTED_OR_IDS => $this->stockClearanceProductService->getProductIds(running: true),
        ]);
    }

    private function paymentGateways(): mixed
    {
        $filters = ['settings_type' => SettingService::PAYMENT_CONFIG_TYPE, 'is_active' => 1];

        if ((int) config('get_payment_publish_status') !== 1) {
            $filters['key_name'] = GlobalConstant::DEFAULT_PAYMENT_GATEWAYS;
        }

        return $this->settingService->getList(dataLimit: 'all', filters: $filters);
    }

    private function loginOption(string $key): mixed
    {
        return json_decode($this->loginSetupService->findBy(params: ['key' => $key])?->value ?? '', true);
    }

    private function customerLoginOptions(): array
    {
        $options = $this->loginOption('login_options') ?? [];

        if (! empty($options['social_login']) && ! $this->hasEnabledSocialLogin()) {
            $options['social_login'] = 0;
        }

        return $options;
    }

    /** @return array<string, int> */
    private function socialLoginOptions(): array
    {
        $configured = $this->loginOption('social_media_for_login') ?? [];
        $available = $this->loginSetupService->getSocialMediumAvailability();

        $options = [];
        foreach ($configured as $medium => $enabled) {
            $options[$medium] = $enabled && ! empty($available[$medium]) ? 1 : 0;
        }

        return $options;
    }

    private function hasEnabledSocialLogin(): bool
    {
        return in_array(1, $this->socialLoginOptions(), true);
    }
}
