<?php

namespace App\Http\Resources\V6\Shared;

use App\Enums\WebConfigKey;
use App\Utils\ProductManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class ConfigurationResource extends JsonResource
{
    private const BLOG_ROUTE = 'app.blog.index';

    private const OFFLINE_PAYMENT_IMAGE = 'public/assets/back-end/img/pay-offline.png';

    private const GATEWAY_IMAGE_DIRECTORY = 'storage/app/public/payment_modules/gateway_image/';

    private const DEFAULT_TAX_TYPE = 'order_wise';

    private const DEFAULT_CURRENCY_SYMBOL_POSITION = 'right';

    private const DEFAULT_FORGOT_PASSWORD_METHOD = 'phone';

    private const DEFAULT_OTP_RESEND_SECONDS = 60;

    private const APP_PLATFORMS = ['android' => 'for_android', 'ios' => 'for_ios'];

    private const APP_VERSION_SETTINGS = [
        'customer' => 'user_app_version_control',
        'vendor' => 'seller_app_version_control',
        'deliveryman' => 'delivery_man_app_version_control',
    ];

    private const LOGIN_OPTIONS = ['manual' => 'manual_login', 'otp' => 'otp_login', 'social' => 'social_login'];

    public function toArray(Request $request): array
    {
        return [
            'company' => $this->company(),
            'theme' => $this->theme(),
            'business' => $this->business(),
            'currency' => $this->currency(),
            'languages' => $this->languages(),
            'vendor' => $this->vendor(),
            'commission' => $this->commission(),
            'order' => $this->order(),
            'shipping' => $this->shipping(),
            'payment' => $this->payment(),
            'wallet' => $this->wallet(),
            'loyalty_point' => $this->loyaltyPoint(),
            'referral' => $this->referral(),
            'authentication' => $this->authentication(),
            'maintenance' => $this->maintenance(),
            'app_versions' => $this->appVersions(),
            'in_house_shop' => $this->inHouseShop(),
            'tax' => $this->tax(),
            'ai' => $this->ai(),
            'media' => $this->media(),
            'urls' => $this->urls(),
            'modules' => $this->resource['modules'],
            'auction' => $this->auction(),
            'wholesale' => $this->resource['wholesale'],
        ];
    }

    private function company(): array
    {
        return [
            'name' => (string) getWebConfig(name: 'company_name'),
            'phone' => (string) getWebConfig(name: 'company_phone'),
            'email' => (string) getWebConfig(name: 'company_email'),
            'country_code' => getWebConfig(name: 'country_code'),
            'timezone' => getWebConfig(name: 'timezone'),
            'logo_full_url' => getWebConfig(name: 'company_web_logo'),
            'fav_icon_full_url' => getWebConfig(name: 'company_fav_icon'),
            'cover_image_full_url' => getWebConfig(name: 'shop_banner'),
            'reliability' => $this->highlightItems(items: getWebConfig(name: 'company_reliability')),
        ];
    }

    private function theme(): array
    {
        $colors = getWebConfig(name: 'colors') ?? [];

        return [
            'active' => theme_root_path(),
            'panel_version' => (string) $this->resource['themePanelVersion'],
            'colors' => [
                'primary' => $colors['primary'] ?? null,
                'secondary' => $colors['secondary'] ?? null,
                'primary_light' => $colors['primary_light'] ?? null,
            ],
        ];
    }

    private function business(): array
    {
        $announcement = getWebConfig(name: 'announcement');

        return [
            'mode' => getWebConfig(name: 'business_mode'),
            'software_version' => SOFTWARE_VERSION,
            'brand_enabled' => (bool) getWebConfig(name: 'product_brand'),
            'digital_product_enabled' => (bool) getWebConfig(name: 'digital_product'),
            'decimal_point' => (int) getWebConfig(name: 'decimal_point_settings'),
            'units' => units(),
            'colors' => $this->resource['colors']->map(fn ($color) => [
                'id' => $color->id,
                'name' => $color->name,
                'code' => $color->code,
            ])->values(),
            'popular_tags' => $this->resource['popularTags']->map(fn ($tag) => [
                'id' => $tag->id,
                'tag' => $tag->tag,
            ])->values(),
            'product_unit_price_range' => [
                'min' => getProductMinUnitPriceRange(),
                'max' => getProductMaxUnitPriceRange(),
            ],
            'announcement' => is_array($announcement) ? [
                'status' => (bool) ($announcement['status'] ?? 0),
                'color' => $announcement['color'] ?? null,
                'text_color' => $announcement['text_color'] ?? null,
                'text' => $announcement['announcement'] ?? null,
            ] : null,
            'pixel_analytics' => getWebConfig(name: 'pixel_analytics'),
        ];
    }

    private function currency(): array
    {
        return [
            'default_id' => (int) getWebConfig(name: 'system_default_currency'),
            'model' => getWebConfig(name: 'currency_model'),
            'symbol_position' => getWebConfig(name: 'currency_symbol_position') ?? self::DEFAULT_CURRENCY_SYMBOL_POSITION,
            'list' => $this->resource['currencies']->map(fn ($currency) => [
                'id' => $currency->id,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'code' => $currency->code,
                'exchange_rate' => (float) $currency->exchange_rate,
                'status' => (bool) $currency->status,
            ])->values(),
        ];
    }

    private function languages(): array
    {
        $languages = collect($this->resource['languages'])->keyBy('code');

        return collect(getWebConfig(name: 'pnc_language') ?? [])
            ->map(fn (string $code) => [
                'code' => $code,
                'name' => $languages[$code]['name'] ?? $code,
                'direction' => $languages[$code]['direction'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function vendor(): array
    {
        return [
            'registration' => (bool) getWebConfig(name: 'seller_registration'),
            'pos' => (bool) getWebConfig(name: 'seller_pos'),
            'review_reply' => (bool) getWebConfig(name: 'vendor_review_reply_status'),
            'can_edit_order_status' => (bool) getWebConfig(name: 'vendor_can_edit_order'),
            'delivery_partner_setup' => vendorDeliveryPartnerSetupAvailable(),
            'verified_badge' => (bool) getWebConfig(name: WebConfigKey::VENDOR_VERIFIED_BADGE_STATUS),
        ];
    }

    private function commission(): array
    {
        return [
            'percentage' => (float) getWebConfig(name: 'sales_commission'),
            'vendor_wise' => (bool) getWebConfig(name: 'retail_vendor_wise_commission_status'),
        ];
    }

    private function order(): array
    {
        return [
            'minimum_order_limit' => (int) getWebConfig(name: 'minimum_order_limit'),
            'minimum_order_amount' => [
                'status' => (bool) getWebConfig(name: 'minimum_order_amount_status'),
                'amount' => (float) getWebConfig(name: 'minimum_order_amount'),
                'set_by_vendor' => (bool) getWebConfig(name: 'minimum_order_amount_by_seller'),
            ],
            'billing_input_by_customer' => (bool) getWebConfig(name: 'billing_input_by_customer'),
            'guest_checkout' => (bool) getWebConfig(name: 'guest_checkout'),
            'order_verification' => (bool) getWebConfig(name: 'order_verification'),
            'refund_day_limit' => (int) getWebConfig(name: 'refund_day_limit'),
            'upload_picture_on_delivery' => (bool) getWebConfig(name: 'upload_picture_on_delivery'),
        ];
    }

    private function shipping(): array
    {
        $defaultLocation = getWebConfig(name: 'default_location');

        return [
            'method' => getWebConfig(name: 'shipping_method'),
            'in_house_shipping_type' => $this->resource['inHouseShippingType'],
            'country_restriction' => (bool) getWebConfig(name: 'delivery_country_restriction'),
            'zip_code_restriction' => (bool) getWebConfig(name: 'delivery_zip_code_area_restriction'),
            'free_delivery' => [
                'status' => (bool) getWebConfig(name: 'free_delivery_status'),
                'responsibility' => getWebConfig(name: 'free_delivery_responsibility'),
                'over_amount' => (float) getWebConfig(name: 'free_delivery_over_amount'),
                'over_amount_vendor' => (float) getWebConfig(name: 'free_delivery_over_amount_seller'),
            ],
            'map' => [
                'status' => (bool) getWebConfig(name: 'map_api_status'),
                'default_location' => is_array($defaultLocation) ? [
                    'lat' => (float) ($defaultLocation['lat'] ?? 0),
                    'lng' => (float) ($defaultLocation['lng'] ?? 0),
                ] : null,
            ],
        ];
    }

    private function payment(): array
    {
        return [
            'digital_payment' => (bool) (getWebConfig(name: 'digital_payment')['status'] ?? 0),
            'cash_on_delivery' => (bool) (getWebConfig(name: 'cash_on_delivery')['status'] ?? 0),
            'offline_payment' => [
                'status' => (bool) (getWebConfig(name: 'offline_payment')['status'] ?? 0),
                'image_url' => dynamicAsset(path: self::OFFLINE_PAYMENT_IMAGE),
            ],
            'methods' => $this->resource['paymentGateways']->map(function ($gateway) {
                $additionalData = json_decode((string) $gateway->additional_data, true) ?? [];
                $image = $additionalData['gateway_image'] ?? null;

                return [
                    'key_name' => $gateway->key_name,
                    'title' => $additionalData['gateway_title'] ?? $gateway->key_name,
                    'image_url' => $image ? dynamicStorage(path: self::GATEWAY_IMAGE_DIRECTORY.$image) : null,
                ];
            })->values(),
        ];
    }

    private function wallet(): array
    {
        return [
            'status' => (bool) getWebConfig(name: 'wallet_status'),
            'add_funds' => [
                'status' => (bool) getWebConfig(name: 'add_funds_to_wallet'),
                'minimum_amount' => (float) getWebConfig(name: 'minimum_add_fund_amount'),
                'maximum_amount' => (float) getWebConfig(name: 'maximum_add_fund_amount'),
            ],
        ];
    }

    private function loyaltyPoint(): array
    {
        return [
            'status' => (bool) getWebConfig(name: 'loyalty_point_status'),
            'exchange_rate' => (int) getWebConfig(name: 'loyalty_point_exchange_rate'),
            'minimum_point' => (int) getWebConfig(name: 'loyalty_point_minimum_point'),
        ];
    }

    private function referral(): array
    {
        return [
            'status' => (bool) getWebConfig(name: 'ref_earning_status'),
            'signup_url' => route('home').'?referral_code=',
        ];
    }

    private function authentication(): array
    {
        $firebaseOtp = getWebConfig(name: 'firebase_otp_verification') ?? [];
        $isFirebaseOtpActive = ! empty($firebaseOtp['status']);

        return [
            'customer' => [
                'login_options' => $this->loginOptions(),
                'social_mediums' => $this->socialMediums(),
                'verification' => $this->customerVerification(isFirebaseReady: $isFirebaseOtpActive && ! empty($firebaseOtp['web_api_key'])),
                'forgot_password_method' => getWebConfig(name: 'forgot_password_verification'),
            ],
            'vendor' => [
                'forgot_password_method' => getWebConfig(name: 'vendor_forgot_password_method') ?? self::DEFAULT_FORGOT_PASSWORD_METHOD,
                'forgot_password_sms_method' => $isFirebaseOtpActive ? 'firebase' : 'sms',
            ],
            'deliveryman' => [
                'forgot_password_method' => getWebConfig(name: 'deliveryman_forgot_password_method') ?? self::DEFAULT_FORGOT_PASSWORD_METHOD,
            ],
            'otp_resend_seconds' => (int) (getWebConfig(name: 'otp_resend_time') ?? self::DEFAULT_OTP_RESEND_SECONDS),
        ];
    }

    private function loginOptions(): array
    {
        $options = getLoginConfig(key: 'login_options') ?? [];

        return array_map(fn (string $key) => (bool) ($options[$key] ?? 0), self::LOGIN_OPTIONS);
    }

    private function socialMediums(): array
    {
        $enabledForLogin = getLoginConfig(key: 'social_media_for_login') ?? [];

        return collect(getWebConfig(name: 'social_login') ?? [])
            ->merge(getWebConfig(name: 'apple_login') ?? [])
            ->map(fn (array $medium) => [
                'medium' => $medium['login_medium'],
                'status' => (bool) $medium['status'],
                'enabled_for_login' => (bool) ($enabledForLogin[$medium['login_medium']] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function customerVerification(bool $isFirebaseReady): array
    {
        $email = (bool) getLoginConfig(key: 'email_verification');
        $phone = (bool) getLoginConfig(key: 'phone_verification');

        return [
            'required' => $email || $phone,
            'email' => $email,
            'phone' => $phone,
            'firebase' => $isFirebaseReady,
        ];
    }

    private function maintenance(): array
    {
        $messages = $this->resource['maintenanceMessages'];
        $duration = $this->resource['maintenanceDuration'];

        return [
            'status' => (bool) $this->resource['isUnderMaintenance'],
            'systems' => array_map('boolval', $this->resource['maintenanceSystems']),
            'message' => [
                'title' => $messages['maintenance_message'] ?? null,
                'body' => $messages['message_body'] ?? null,
                'show_business_number' => (bool) ($messages['business_number'] ?? 0),
                'show_business_email' => (bool) ($messages['business_email'] ?? 0),
            ],
            'duration' => [
                'type' => $duration['maintenance_duration'] ?? null,
                'start_date' => $duration['start_date'] ?? null,
                'end_date' => $duration['end_date'] ?? null,
            ],
        ];
    }

    private function appVersions(): array
    {
        return array_map(function (string $settingName) {
            $versionControl = getWebConfig(name: $settingName) ?? [];

            return array_map(fn (string $platformKey) => [
                'status' => (bool) ($versionControl[$platformKey]['status'] ?? 0),
                'version' => (string) ($versionControl[$platformKey]['version'] ?? ''),
                'link' => (string) ($versionControl[$platformKey]['link'] ?? ''),
            ], self::APP_PLATFORMS);
        }, self::APP_VERSION_SETTINGS);
    }

    private function inHouseShop(): array
    {
        $shop = $this->resource['inHouseShop'];
        $vacation = getWebConfig(name: 'vacation_add') ?? [];

        return [
            'id' => $shop?->id,
            'name' => $shop?->name,
            'slug' => $shop?->slug,
            'address' => $shop?->address,
            'contact' => $shop?->contact,
            'image_full_url' => $shop?->image_full_url,
            'banner_full_url' => $shop?->banner_full_url,
            'verified_badge' => isInHouseShopVerified(),
            'temporary_close' => (bool) (getWebConfig(name: 'temporary_close')['status'] ?? 0),
            'vacation' => [
                'status' => (bool) ($vacation['status'] ?? 0),
                'start_date' => $vacation['vacation_start_date'] ?? null,
                'end_date' => $vacation['vacation_end_date'] ?? null,
                'note' => $vacation['vacation_note'] ?? null,
            ],
        ];
    }

    private function tax(): array
    {
        $systemTax = $this->resource['systemTax'];

        return [
            'type' => $systemTax?->tax_type ?? self::DEFAULT_TAX_TYPE,
            'is_included' => (bool) $systemTax?->is_included,
        ];
    }

    private function ai(): array
    {
        return [
            'enabled' => (bool) $this->resource['isAiProviderActive'],
            'shopping_assistant' => (bool) getWebConfig(name: 'ai_shopping_assistant_status'),
        ];
    }

    private function media(): array
    {
        return [
            'base_urls' => [
                'product' => ProductManager::product_image_path('product'),
                'product_thumbnail' => ProductManager::product_image_path('thumbnail'),
                'digital_product' => dynamicStorage(path: 'storage/app/public/product/digital-product'),
                'brand' => dynamicStorage(path: 'storage/app/public/brand'),
                'customer' => dynamicStorage(path: 'storage/app/public/profile'),
                'banner' => dynamicStorage(path: 'storage/app/public/banner'),
                'category' => dynamicStorage(path: 'storage/app/public/category'),
                'review' => dynamicStorage(path: 'storage/app/public'),
                'vendor' => dynamicStorage(path: 'storage/app/public/seller'),
                'shop' => dynamicStorage(path: 'storage/app/public/shop'),
                'notification' => dynamicStorage(path: 'storage/app/public/notification'),
                'deliveryman' => dynamicStorage(path: 'storage/app/public/delivery-man'),
                'support_ticket' => dynamicStorage(path: 'storage/app/public/support-ticket'),
                'chatting' => dynamicStorage(path: 'storage/app/public/chatting'),
            ],
            'upload_limits' => [
                'server_upload_max_filesize' => ini_get('upload_max_filesize'),
                'server_post_max_size' => ini_get('post_max_size'),
                'image_max_size_mb' => getFileUploadMaxSize(type: 'image'),
                'file_max_size_mb' => getFileUploadMaxSize(type: 'file'),
            ],
        ];
    }

    private function urls(): array
    {
        return [
            'contact_us' => route('contacts'),
            'brands' => route('brands'),
            'categories' => route('categories'),
            'customer_account' => route('user-account'),
            'blog' => Route::has(self::BLOG_ROUTE) && getWebConfig(name: 'blog_feature_active_status') ? route(self::BLOG_ROUTE) : null,
        ];
    }

    private function auction(): ?array
    {
        $featureStatus = getWebConfig(name: 'auction_feature_status');

        if ($featureStatus === null) {
            return null;
        }

        $isFeatureActive = (bool) $featureStatus;
        $homePage = getWebConfig(name: 'auction_home_page_setup') ?? [];

        return [
            'status' => $isFeatureActive,
            'active_for_vendor' => $isFeatureActive && (bool) getWebConfig(name: 'active_auction_for_vendor'),
            'active_for_customer' => $isFeatureActive && (bool) getWebConfig(name: 'active_auction_for_customer'),
            'commission_percentage' => (float) getWebConfig(name: 'auction_commission_percentage'),
            'vendor_wise_commission' => (bool) getWebConfig(name: 'auction_vendor_wise_commission_status'),
            'entry_fee' => [
                'status' => (bool) getWebConfig(name: 'auction_entry_fee_amount_status'),
                'amount' => (float) getWebConfig(name: 'auction_entry_fee_amount_value'),
            ],
            'expired_visibility_duration' => $this->duration(setting: getWebConfig(name: 'auction_expired_visibility_duration')),
            'upcoming_visibility_duration' => $this->duration(setting: getWebConfig(name: 'auction_upcoming_visibility_duration')),
            'winner_claim_time_limit' => $this->duration(setting: getWebConfig(name: 'auction_winner_claim_time_limit')),
            'billing_address' => (bool) getWebConfig(name: 'auction_billing_address_status'),
            'home_page' => [
                'title' => $homePage['title'] ?? null,
                'subtitle' => $homePage['subtitle'] ?? null,
                'bg_image_full_url' => getWebConfig(name: 'auction_home_bg_image'),
            ],
            'commitments' => $this->highlightItems(items: getWebConfig(name: 'auction_commitments')),
        ];
    }

    private function duration(mixed $setting): ?array
    {
        if (! is_array($setting)) {
            return null;
        }

        return [
            'status' => (bool) ($setting['status'] ?? 0),
            'value' => (int) ($setting['value'] ?? 0),
            'unit' => $setting['unit'] ?? null,
        ];
    }

    private function highlightItems(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn (array $item) => [
                'item' => $item['item'] ?? null,
                'title' => $item['title'] ?? null,
                'status' => (bool) ($item['status'] ?? 0),
                'image_full_url' => $item['image_full_url'] ?? null,
            ])
            ->values()
            ->all();
    }
}
