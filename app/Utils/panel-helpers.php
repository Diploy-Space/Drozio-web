<?php

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Arr;

if (! function_exists('checkSetupGuideRequirements')) {
    function checkSetupGuideRequirements($panel = 'admin'): array
    {
        $steps = getSetupGuideSteps(panel: $panel);

        uasort($steps, function ($a, $b) {
            return (empty($b['checked']) ? 0 : 1) <=> (empty($a['checked']) ? 0 : 1);
        });

        $checkedCount = count(array_filter($steps, function ($step) {
            return ! empty($step['checked']);
        }));

        return [
            'totalSteps' => count($steps) - $checkedCount,
            'completePercent' => $checkedCount > 0 ? floor(($checkedCount / count($steps)) * 100) : 0,
            'steps' => $steps,
        ];
    }
}

if (! function_exists('checkSetupGuideCacheKey')) {
    function checkSetupGuideCacheKey($key = '', $panel = null)
    {
        $checkSetupGuideCacheKeys = [];
        if ($panel == 'admin') {
            $storedSetupGuideKeys = getWebConfig(name: 'setup_guide_requirements_for_admin') ?? [];

            if (isset($storedSetupGuideKeys[$key])) {
                return $storedSetupGuideKeys[$key];
            }

            return match ($key) {
                'category_setup' => Category::businessModule(BusinessModule::RETAIL)->exists(),
                'wholesale_category_setup' => Category::businessModule(BusinessModule::WHOLESALE)->exists(),
                'brand_setup' => Brand::businessModule(BusinessModule::RETAIL)->exists(),
                'wholesale_brand_setup' => Brand::businessModule(BusinessModule::WHOLESALE)->exists(),
                'add_new_product' => Product::businessModule(BusinessModule::RETAIL)->exists(),
                'wholesale_product_setup' => Product::businessModule(BusinessModule::WHOLESALE)->exists(),
                'auction_category_setup' => Category::businessModule(BusinessModule::AUCTION)->exists(),
                'auction_brand_setup' => Brand::businessModule(BusinessModule::AUCTION)->exists(),
                default => false,
            };
        } elseif ($panel == 'vendor') {
            $auth = auth('seller')->check() ? auth('seller')->user() : null;
            if ($auth) {

                $cacheKey = 'cache_for_vendor_wise_products_count_'.$auth['id'];
                $cacheKeys = Cache::get(CACHE_FOR_VENDOR_WISE_PRODUCTS_COUNT, []);
                if (! in_array($cacheKey, $cacheKeys)) {
                    $cacheKeys[] = $cacheKey;
                    Cache::put(CACHE_FOR_VENDOR_WISE_PRODUCTS_COUNT, $cacheKeys, CACHE_FOR_3_HOURS);
                }

                $newProductCount = Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($auth) {
                    return Product::where(['added_by' => 'seller', 'user_id' => $auth['id']])->count();
                });

                $setupGuide = session('setup_guide_requirements_for_vendor_'.$auth['id'], (array) ($auth?->shop?->setup_guide ?? []));
                $checkSetupGuideCacheKeys = ! empty($setupGuide) ? $setupGuide : [
                    'shop_setup' => 0,
                    'add_new_product' => $newProductCount > 0 ? 1 : 0,
                    'order_setup' => 0,
                    'withdraw_setup' => 0,
                    'payment_information' => 0,
                ];
            }
        }

        return $checkSetupGuideCacheKeys[$key] ?? false;
    }
}

if (! function_exists('updateSetupGuideCacheKey')) {
    function updateSetupGuideCacheKey($key = '', $panel = null): void
    {
        if ($panel == 'admin') {
            $checkSetupGuideCacheKeys = getWebConfig(name: 'setup_guide_requirements_for_admin') ?? [];
            $checkSetupGuideCacheKeys = empty($checkSetupGuideCacheKeys) ? [] : $checkSetupGuideCacheKeys;

            if (! isset($checkSetupGuideCacheKeys[$key]) || $checkSetupGuideCacheKeys[$key] !== true) {
                $newCacheKeys = [];
                $newCacheKeys[$key] = true;
                $checkSetupGuideCacheKeys = array_merge($checkSetupGuideCacheKeys, $newCacheKeys);
                if (BusinessSetting::where(['type' => 'setup_guide_requirements_for_admin'])->first()) {
                    BusinessSetting::where(['type' => 'setup_guide_requirements_for_admin'])->update([
                        'value' => json_encode($checkSetupGuideCacheKeys),
                    ]);
                } else {
                    BusinessSetting::create([
                        'type' => 'setup_guide_requirements_for_admin',
                        'value' => json_encode($checkSetupGuideCacheKeys),
                        'updated_at' => now(),
                    ]);
                }
                cacheRemoveByType(type: 'business_settings');
            }
        } elseif ($panel == 'vendor') {
            $auth = auth('seller')->check() ? auth('seller')->user() : null;
            if ($auth) {
                $checkSetupGuideKeys = session('setup_guide_requirements_for_vendor_'.$auth['id'], (array) ($auth?->shop?->setup_guide ?? []));
                if (! isset($checkSetupGuideKeys[$key]) || $checkSetupGuideKeys[$key] !== true) {
                    $newCacheKeys = [];
                    $newCacheKeys[$key] = true;
                    $newCacheKeys['add_new_product'] = checkSetupGuideCacheKey(key: 'add_new_product', panel: 'vendor');
                    $checkSetupGuideKeys = array_merge($checkSetupGuideKeys, $newCacheKeys);
                    Shop::where(['seller_id' => $auth['id']])->update([
                        'setup_guide' => json_encode($checkSetupGuideKeys),
                    ]);
                    session()->put('setup_guide_requirements_for_vendor_'.$auth['id'], $checkSetupGuideKeys);
                }
            }
        }
    }
}

if (! function_exists('getSetupGuideSteps')) {
    function getSetupGuideSteps($panel = 'admin'): array
    {
        $steps = [];
        if ($panel === 'admin') {
            $isWholesaleAvailable = businessModuleManager()->isAvailableInPanel(key: BusinessModule::WHOLESALE);
            $isAuctionAvailable = businessModuleManager()->isAvailableInPanel(key: BusinessModule::AUCTION);
            $hasOtherBusinessModule = $isWholesaleAvailable || $isAuctionAvailable;

            $steps['general_setup'] = [
                'title' => translate('General Setup'),
                'route' => route('admin.settings.business-settings.web-config.index'),
                'checked' => checkSetupGuideCacheKey(key: 'general_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'general_setup', panel: 'admin'),
            ];

            $steps['shipping_method'] = [
                'title' => translate('Shipping Method'),
                'route' => route('admin.settings.business-settings.shipping-method.index'),
                'checked' => checkSetupGuideCacheKey(key: 'shipping_method', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'shipping_method', panel: 'admin'),
            ];

            $steps['language_setup'] = [
                'title' => translate('Language setup'),
                'route' => route('admin.settings.system-setup.language.index'),
                'checked' => checkSetupGuideCacheKey(key: 'language_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'language_setup', panel: 'admin'),
            ];

            $steps['currency_setup'] = [
                'title' => translate('Currency'),
                'route' => route('admin.settings.system-setup.currency.view'),
                'checked' => checkSetupGuideCacheKey(key: 'currency_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'currency_setup', panel: 'admin'),
            ];

            $steps['customer_login'] = [
                'title' => translate('Customer Login'),
                'route' => route('admin.settings.system-setup.login-settings.customer-login-setup'),
                'checked' => checkSetupGuideCacheKey(key: 'customer_login', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'customer_login', panel: 'admin'),
            ];

            $steps['google_map_apis'] = [
                'title' => translate('Google Map APIs'),
                'route' => route('admin.settings.third-party.map-api'),
                'checked' => checkSetupGuideCacheKey(key: 'google_map_apis', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'google_map_apis', panel: 'admin'),
            ];

            $steps['notification_configuration'] = [
                'title' => translate('Notification Configuration'),
                'route' => route('admin.settings.system-setup.push-notification.index'),
                'checked' => checkSetupGuideCacheKey(key: 'notification_configuration', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'notification_configuration', panel: 'admin'),
            ];

            $steps['digital_payment_setup'] = [
                'title' => translate('Digital Payment Setup'),
                'route' => route('admin.settings.third-party.payment-method.index'),
                'checked' => checkSetupGuideCacheKey(key: 'digital_payment_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'digital_payment_setup', panel: 'admin'),
            ];

            $steps['offline_payment_setup'] = [
                'title' => translate('Offline Payment Setup'),
                'route' => route('admin.settings.third-party.offline-payment-method.index'),
                'checked' => checkSetupGuideCacheKey(key: 'offline_payment_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'offline_payment_setup', panel: 'admin'),
            ];

            $steps['category_setup'] = [
                'title' => $hasOtherBusinessModule ? translate('Retail Category Setup') : translate('Category Setup'),
                'route' => route('admin.retail.category.view'),
                'checked' => checkSetupGuideCacheKey(key: 'category_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'category_setup', panel: 'admin'),
            ];

            if ($isWholesaleAvailable) {
                $steps['wholesale_category_setup'] = [
                    'title' => translate('Wholesale Category Setup'),
                    'route' => route('admin.wholesale.category.view'),
                    'checked' => checkSetupGuideCacheKey(key: 'wholesale_category_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'wholesale_category_setup', panel: 'admin'),
                ];
            }

            if ($isAuctionAvailable) {
                $steps['auction_category_setup'] = [
                    'title' => translate('Auction Category Setup'),
                    'route' => route('admin.auction.category.view'),
                    'checked' => checkSetupGuideCacheKey(key: 'auction_category_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'auction_category_setup', panel: 'admin'),
                ];
            }

            $steps['brand_setup'] = [
                'title' => $hasOtherBusinessModule ? translate('Retail Brand Setup') : translate('Brand Setup'),
                'route' => route('admin.retail.brand.add-new'),
                'checked' => checkSetupGuideCacheKey(key: 'brand_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'brand_setup', panel: 'admin'),
            ];

            if ($isWholesaleAvailable) {
                $steps['wholesale_brand_setup'] = [
                    'title' => translate('Wholesale Brand Setup'),
                    'route' => route('admin.wholesale.brand.add-new'),
                    'checked' => checkSetupGuideCacheKey(key: 'wholesale_brand_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'wholesale_brand_setup', panel: 'admin'),
                ];
            }

            if ($isAuctionAvailable) {
                $steps['auction_brand_setup'] = [
                    'title' => translate('Auction Brand Setup'),
                    'route' => route('admin.auction.brand.add-new'),
                    'checked' => checkSetupGuideCacheKey(key: 'auction_brand_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'auction_brand_setup', panel: 'admin'),
                ];
            }

            $steps['inhouse_shop_setup'] = [
                'title' => translate('In-house Shop'),
                'route' => route('admin.settings.business-settings.inhouse-shop'),
                'checked' => checkSetupGuideCacheKey(key: 'inhouse_shop_setup', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'inhouse_shop_setup', panel: 'admin'),
            ];

            $steps['add_new_product'] = [
                'title' => $hasOtherBusinessModule ? translate('Retail Product Setup') : translate('Add New Product'),
                'route' => route('admin.retail.products.add'),
                'checked' => checkSetupGuideCacheKey(key: 'add_new_product', panel: 'admin'),
                'disabled' => checkSetupGuideCacheKey(key: 'add_new_product', panel: 'admin'),
            ];

            if ($isWholesaleAvailable) {
                $steps['wholesale_product_setup'] = [
                    'title' => translate('Wholesale Product Setup'),
                    'route' => route('admin.wholesale.products.add'),
                    'checked' => checkSetupGuideCacheKey(key: 'wholesale_product_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'wholesale_product_setup', panel: 'admin'),
                ];
            }

            if ($isAuctionAvailable) {
                $steps['auction_product_setup'] = [
                    'title' => translate('Auction Product Setup'),
                    'route' => route('admin.auction.products.add'),
                    'checked' => checkSetupGuideCacheKey(key: 'auction_product_setup', panel: 'admin'),
                    'disabled' => checkSetupGuideCacheKey(key: 'auction_product_setup', panel: 'admin'),
                ];
            }
        } elseif ($panel == 'vendor') {
            $steps['shop_setup'] = [
                'title' => translate('Shop Setup'),
                'route' => route('vendor.shop.index'),
                'checked' => checkSetupGuideCacheKey(key: 'shop_setup', panel: 'vendor'),
                'disabled' => checkSetupGuideCacheKey(key: 'shop_setup', panel: 'vendor'),
            ];

            $steps['add_new_product'] = [
                'title' => translate('Add New Product'),
                'route' => route('vendor.retail.products.add'),
                'checked' => checkSetupGuideCacheKey(key: 'add_new_product', panel: 'vendor'),
                'disabled' => checkSetupGuideCacheKey(key: 'add_new_product', panel: 'vendor'),
            ];

            $steps['order_setup'] = [
                'title' => translate('Other Setup'),
                'route' => route('vendor.shop.other-setup'),
                'checked' => checkSetupGuideCacheKey(key: 'order_setup', panel: 'vendor'),
                'disabled' => checkSetupGuideCacheKey(key: 'order_setup', panel: 'vendor'),
            ];

            $steps['payment_information'] = [
                'title' => translate('Payment Information'),
                'route' => route('vendor.shop.payment-information.index'),
                'checked' => checkSetupGuideCacheKey(key: 'payment_information', panel: 'vendor'),
                'disabled' => checkSetupGuideCacheKey(key: 'payment_information', panel: 'vendor'),
            ];

            $steps['withdraw_setup'] = [
                'title' => translate('Withdraw setup'),
                'route' => route('vendor.business-settings.withdraw.index'),
                'checked' => checkSetupGuideCacheKey(key: 'withdraw_setup', panel: 'vendor'),
                'disabled' => checkSetupGuideCacheKey(key: 'withdraw_setup', panel: 'vendor'),
            ];
        }

        return $steps;
    }
}

if (! function_exists('highlightKeyword')) {
    function highlightKeyword(?string $text, ?string $keyword): string
    {
        $text = (string) $text;
        $keyword = (string) $keyword;

        if ($keyword === '') {
            return $text;
        }

        return preg_replace('/('.preg_quote($keyword, '/').')/i', '<mark>$1</mark>', $text);
    }
}

if (! function_exists('decimalStepValue')) {
    function decimalStepValue(int $decimalPoints): string
    {
        return $decimalPoints > 0 ? '0.'.str_repeat('0', $decimalPoints - 1).'1' : '1';
    }
}

if (! function_exists('decimalInputValue')) {
    function decimalInputValue(mixed $value, int $decimalPoints): string
    {
        return $value === null || $value === '' ? '' : (string) round((float) $value, $decimalPoints);
    }
}

if (! function_exists('tableSortLinks')) {
    /** @return array<string, array{label: string, url: string, direction: ?string}> */
    function tableSortLinks(array $columns, ?string $activeSort, string $url, array $query): array
    {
        $links = [];

        foreach ($columns as $column => $sort) {
            $links[$column] = [
                'label' => $sort['label'],
                'url' => $url.'?'.Arr::query([
                    ...Arr::except($query, ['page', 'sort']),
                    'sort' => $activeSort === $sort['ascending'] ? $sort['descending'] : $sort['ascending'],
                ]),
                'direction' => match ($activeSort) {
                    $sort['ascending'] => 'asc',
                    $sort['descending'] => 'desc',
                    default => null,
                },
            ];
        }

        return $links;
    }
}
