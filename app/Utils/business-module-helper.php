<?php

use App\Enums\BusinessModule as BusinessModuleKey;
use App\Services\BusinessModule\BusinessModule;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\Web\CustomerProfileMenuBuilder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

if (! function_exists('businessModuleManager')) {
    function businessModuleManager(): BusinessModuleManager
    {
        return app(BusinessModuleManager::class);
    }
}

if (! function_exists('activeBusinessModule')) {
    function activeBusinessModule(): BusinessModule
    {
        return businessModuleManager()->active();
    }
}

if (! function_exists('activeAdminArea')) {
    function activeAdminArea(): BusinessModule
    {
        return businessModuleManager()->activeArea();
    }
}

if (! function_exists('inCommonAdminArea')) {
    function inCommonAdminArea(): bool
    {
        return businessModuleManager()->isCommonArea();
    }
}

if (! function_exists('adminAreaUrl')) {
    function adminAreaUrl(string $key): string
    {
        $manager = businessModuleManager();
        $area = $manager->commonAreas()[$key] ?? null;

        return $area === null ? '' : $manager->landingUrlFor($area);
    }
}

if (! function_exists('businessModuleAvailable')) {
    function businessModuleAvailable(string $key): bool
    {
        return businessModuleManager()->isAvailable($key);
    }
}

if (! function_exists('businessModuleSectionVisible')) {
    function businessModuleSectionVisible(string $section): bool
    {
        return activeAdminArea()->hasSection($section);
    }
}

if (! function_exists('businessModuleDashboardUrl')) {
    function businessModuleDashboardUrl(): string
    {
        $manager = businessModuleManager();
        $route = $manager->active()->dashboardRoute;

        if ($route !== '' && Route::has($route)) {
            return route($route);
        }

        return route($manager->panel() === 'vendor' ? 'vendor.retail.dashboard.index' : 'admin.retail.dashboard.index');
    }
}

if (! function_exists('businessModuleItemVisible')) {
    function businessModuleItemVisible(string $itemKey): bool
    {
        return businessModuleManager()->itemVisible($itemKey);
    }
}

if (! function_exists('businessModuleFromSwitchPages')) {
    function businessModuleFromSwitchPages(string $configKey): ?string
    {
        $currentRoute = request()->route()?->getName();

        if ($currentRoute === null) {
            return null;
        }

        foreach ((array) config($configKey, []) as $pageRoutes) {
            foreach ((array) $pageRoutes as $moduleKey => $moduleRoute) {
                if ($moduleRoute === $currentRoute) {
                    return (string) $moduleKey;
                }
            }
        }

        return null;
    }
}

if (! function_exists('activeBusinessSettingsModule')) {
    function activeBusinessSettingsModule(): string
    {
        return businessModuleFromSwitchPages('business_modules.business_module_switch_pages')
            ?? BusinessModuleKey::COMMON;
    }
}

if (! function_exists('activeBusinessSettingsModuleLabel')) {
    function activeBusinessSettingsModuleLabel(): string
    {
        $moduleKey = activeBusinessSettingsModule();

        if ($moduleKey === BusinessModuleKey::COMMON) {
            return 'Common';
        }

        return businessModuleManager()->get($moduleKey)?->label ?? ucfirst($moduleKey);
    }
}

if (! function_exists('reportPanel')) {
    function reportPanel(): string
    {
        return request()->segment(1) === 'vendor' ? 'vendor' : 'admin';
    }
}

if (! function_exists('activeReportModule')) {
    function activeReportModule(): string
    {
        $moduleSegment = request()->segment(3);

        if (request()->segment(2) === 'report' && is_string($moduleSegment)
            && array_key_exists($moduleSegment, (array) config('business_modules.modules', []))) {
            return $moduleSegment;
        }

        return businessModuleFromSwitchPages('business_modules.report_module_switch_pages.'.reportPanel())
            ?? BusinessModuleKey::RETAIL;
    }
}

if (! function_exists('reportModuleLabel')) {
    function reportModuleLabel(): string
    {
        return (string) config('business_modules.modules.'.activeReportModule().'.label', '');
    }
}

if (! function_exists('reportModuleSwitchOptions')) {
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, array{key: string, label: string, url: string, active: bool}>
     */
    function reportModuleSwitchOptions(string $switchPage, array $parameters = []): array
    {
        $available = businessModuleManager()->permittedBusinessModules();
        $activeModule = activeReportModule();
        $options = [];

        foreach ((array) config('business_modules.report_module_switch_pages.'.reportPanel().'.'.$switchPage, []) as $moduleKey => $routeName) {
            if (! isset($available[$moduleKey]) || ! is_string($routeName) || ! Route::has($routeName)) {
                continue;
            }

            $options[] = [
                'key' => (string) $moduleKey,
                'label' => $available[$moduleKey]->label,
                'url' => route($routeName, $parameters),
                'active' => $moduleKey === $activeModule,
            ];
        }

        return count($options) > 1 ? $options : [];
    }
}

if (! function_exists('reportModuleRoute')) {
    function reportModuleRoute(string $action, ?string $moduleKey = null): string
    {
        $panel = reportPanel();
        $routeName = $panel.'.report.'.($moduleKey ?? activeReportModule()).'.'.$action;

        return Route::has($routeName) ? $routeName : $panel.'.report.'.BusinessModuleKey::RETAIL.'.'.$action;
    }
}

if (! function_exists('reportMenuDefinitions')) {
    /** @return array<int, array{key: string, title: string, items: array<int, array<string, mixed>>}>  */
    function reportMenuDefinitions(): array
    {
        return [
            [
                'key' => 'report-finance',
                'permission' => 'reports.finance',
                'title' => 'Finance',
                'items' => [
                    [
                        'key' => 'earnings',
                        'label' => 'Earning',
                        'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                        'action' => 'admin-earning',
                        'paths' => ['admin-earning', 'vendor-earning'],
                    ],
                    [
                        'key' => 'sales-report',
                        'label' => 'Sales & Transaction',
                        'children' => [
                            [
                                'key' => 'inhouse-sales',
                                'label' => 'In-house Sales',
                                'tone' => 'primary',
                                'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                                'action' => 'inhouse-product-sale',
                                'paths' => ['inhouse-product-sale'],
                            ],
                            [
                                'key' => 'vendor-sales',
                                'label' => 'Vendor Sales',
                                'tone' => 'primary',
                                'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                                'action' => 'vendor-report',
                                'paths' => ['vendor-report'],
                            ],
                            [
                                'key' => 'transactions',
                                'label' => 'Transactions',
                                'tone' => 'success',
                                'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                                'action' => 'transaction.order-transaction-list',
                                'paths' => [
                                    'transaction/order-transaction-list',
                                    'transaction/expense-transaction-list',
                                    'transaction/refund-transaction-list',
                                    'transaction/wallet-bonus',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'report-operations',
                'permission' => 'reports.operations',
                'title' => 'Operations',
                'items' => [
                    [
                        'key' => 'product-report',
                        'label' => 'Product Report',
                        'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                        'action' => 'all-product',
                        'paths' => ['all-product', 'stock/product-stock', 'stock/product-in-wishlist'],
                    ],
                    [
                        'key' => 'order-report',
                        'label' => 'Order Report',
                        'modules' => [BusinessModuleKey::RETAIL, BusinessModuleKey::WHOLESALE],
                        'action' => 'order',
                        'paths' => ['order'],
                    ],
                    [
                        'key' => 'auction-report',
                        'label' => 'Auction Report',
                        'modules' => [BusinessModuleKey::AUCTION],
                        'action' => 'inhouse-auction-sales',
                        'paths' => ['inhouse-auction-sales', 'vendor-auction-sales'],
                    ],
                ],
            ],
            [
                'key' => 'report-tax',
                'permission' => 'reports.tax',
                'title' => 'Tax',
                'items' => [
                    [
                        'key' => 'tax-admin',
                        'label' => 'Admin Tax Report',
                        'action' => 'get-tax-report',
                        'paths' => ['get-tax-report', 'get-tax-details'],
                    ],
                    [
                        'key' => 'tax-vendor',
                        'label' => 'Vendor Vat Report',
                        'action' => 'vendor-wise-taxes',
                        'paths' => ['vendor-wise-taxes', 'vendor-tax-report'],
                    ],
                    [
                        'key' => 'tax-customer',
                        'label' => 'Customer Vat Report',
                        'action' => 'customer-wise-taxes',
                        'paths' => ['customer-wise-taxes', 'customer-tax-report'],
                    ],
                ],
            ],
        ];
    }
}

if (! function_exists('reportMenuItemModules')) {
    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    function reportMenuItemModules(array $definition): array
    {
        return $definition['modules'] ?? BusinessModuleKey::values();
    }
}

if (! function_exists('reportMenuItemRouteFor')) {
    /** @param  array<string, mixed>  $definition  */
    function reportMenuItemRouteFor(array $definition, string $panel, string $module): ?string
    {
        if (! in_array($module, reportMenuItemModules($definition), true)) {
            return null;
        }

        $routeName = $panel.'.report.'.$module.'.'.$definition['action'];

        return Route::has($routeName) ? $routeName : null;
    }
}

if (! function_exists('reportMenuSections')) {
    /** @return array<int, array{key: string, permission: string, title: string, items: array<int, array<string, mixed>>}>  */
    function reportMenuSections(?string $module = null): array
    {
        $panel = reportPanel();
        $module ??= activeReportModule();

        $resolve = static function (array $definition) use ($panel, $module): ?array {
            $routeName = reportMenuItemRouteFor($definition, $panel, $module);
            $scoped = $routeName !== null;

            if ($routeName === null) {
                foreach (reportMenuItemModules($definition) as $candidate) {
                    if (! businessModuleManager()->isAvailableInPanel($candidate)) {
                        continue;
                    }

                    $routeName = reportMenuItemRouteFor($definition, $panel, $candidate);

                    if ($routeName !== null) {
                        break;
                    }
                }
            }

            if ($routeName === null) {
                return null;
            }

            return [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'tone' => $definition['tone'] ?? 'primary',
                'scoped' => $scoped,
                'url' => route($routeName),
                'patterns' => array_map(
                    static fn (string $path): string => $panel.'/report/*/'.$path,
                    $definition['paths'],
                ),
            ];
        };

        $sections = [];

        foreach (reportMenuDefinitions() as $definition) {
            $items = [];

            foreach ($definition['items'] as $item) {
                if (isset($item['children'])) {
                    $children = array_values(array_filter(array_map($resolve, $item['children'])));

                    if ($children === []) {
                        continue;
                    }

                    $items[] = [
                        'key' => $item['key'],
                        'label' => $item['label'],
                        'url' => $children[0]['url'],
                        'patterns' => array_merge(...array_column($children, 'patterns')),
                        'children' => $children,
                    ];

                    continue;
                }

                $resolved = $resolve($item);

                if ($resolved !== null) {
                    $items[] = $resolved + ['children' => []];
                }
            }

            if ($items !== []) {
                $sections[] = [
                    'key' => $definition['key'],
                    'permission' => $definition['permission'],
                    'title' => $definition['title'],
                    'items' => $items,
                ];
            }
        }

        return $sections;
    }
}

if (! function_exists('reportCurrentMenuItemKey')) {
    function reportCurrentMenuItemKey(): ?string
    {
        foreach (reportMenuSections() as $section) {
            foreach ($section['items'] as $item) {
                foreach ($item['children'] ?: [$item] as $candidate) {
                    if (request()->is(...$candidate['patterns'])) {
                        return $candidate['key'];
                    }
                }
            }
        }

        return null;
    }
}

if (! function_exists('reportMenuItemUrl')) {
    /** @param  array<int, array<string, mixed>>  $sections  */
    function reportMenuItemUrl(array $sections, ?string $itemKey, bool $ownedOnly = false): ?string
    {
        foreach ($sections as $section) {
            foreach ($section['items'] as $item) {
                foreach ($item['children'] ?: [$item] as $candidate) {
                    if ($candidate['key'] !== $itemKey) {
                        continue;
                    }

                    if ($ownedOnly && ! ($candidate['scoped'] ?? true)) {
                        return null;
                    }

                    return $candidate['url'];
                }
            }
        }

        return null;
    }
}

if (! function_exists('reportPageModuleOptions')) {
    /** @return array<int, array{key: string, label: string, url: string, active: bool}>  */
    function reportPageModuleOptions(): array
    {
        $itemKey = reportCurrentMenuItemKey();

        if ($itemKey === null) {
            return [];
        }

        $activeModule = activeReportModule();
        $options = [];

        foreach (businessModuleManager()->availableInPanel() as $module) {
            $url = reportMenuItemUrl(reportMenuSections($module->key), $itemKey, ownedOnly: true);

            if ($url === null) {
                continue;
            }

            $options[] = [
                'key' => $module->key,
                'label' => $module->label,
                'icon' => $module->icon,
                'url' => $url,
                'active' => $module->key === $activeModule,
            ];
        }

        return count($options) > 1 && in_array(true, array_column($options, 'active'), true) ? $options : [];
    }
}

if (! function_exists('businessSettingsModuleOptions')) {
    /** @return array<int, array{key: string, label: string, icon: string, url: string, active: bool}>  */
    function businessSettingsModuleOptions(): array
    {
        $installed = businessModuleManager()->installed();
        $selected = activeBusinessSettingsModule();
        $placeholder = (string) config('business_modules.switcher_placeholder_icon', 'select-business.svg');
        $options = [];

        foreach ((array) config('business_modules.business_module_switch_pages.business-settings', []) as $moduleKey => $routeName) {
            if (! is_string($routeName) || ! Route::has($routeName)) {
                continue;
            }

            if ($moduleKey === BusinessModuleKey::COMMON) {
                $options[] = [
                    'key' => $moduleKey,
                    'label' => 'Common',
                    'icon' => $placeholder,
                    'url' => route($routeName),
                    'active' => $selected === $moduleKey,
                ];

                continue;
            }

            if (! isset($installed[$moduleKey])) {
                continue;
            }

            $options[] = [
                'key' => $moduleKey,
                'label' => $installed[$moduleKey]->label,
                'icon' => $installed[$moduleKey]->icon,
                'url' => route($routeName),
                'active' => $selected === $moduleKey,
            ];
        }

        return $options;
    }
}

if (! function_exists('businessSetupTabs')) {
    /** @return array<int, array{key: string, label: string, url: string, is_active: bool}>  */
    function businessSetupTabs(): array
    {
        $activeModule = activeBusinessSettingsModule();
        $config = (array) config('business_modules.business_setup_tabs', []);
        $definitions = (array) ($config['definitions'] ?? []);
        $tabKeys = (array) ($config['scopes'][$activeModule] ?? $config['scopes'][BusinessModuleKey::COMMON] ?? []);

        $labels = [
            'general' => translate('General'),
            'website-setup' => translate('Website Setup'),
            'vendors' => translate('Vendors'),
            'products' => translate('Products'),
            'delivery-men' => translate('Delivery Men'),
            'customer' => translate('Customer'),
            'orders' => translate('Orders'),
            'others' => translate('Others'),
            'refund' => translate('Refund'),
            'shipping-method' => translate('Shipping Method'),
            'delivery-zone' => translate('Delivery Zone'),
            'invoice' => translate('Invoice'),
        ];

        $tabs = [];

        foreach ($tabKeys as $tabKey) {
            $tab = $definitions[$tabKey] ?? null;

            if ($tab === null || ! businessSetupTabAvailable($tab)) {
                continue;
            }

            $routeName = businessSetupTabRoute($tab, $activeModule);

            if ($routeName === null) {
                continue;
            }

            $tabs[] = [
                'key' => $tabKey,
                'label' => $labels[$tabKey] ?? $tabKey,
                'url' => route($routeName),
                'is_active' => businessSetupTabIsActive($tab),
            ];
        }

        return $tabs;
    }
}

if (! function_exists('businessSetupTabRoute')) {
    /** @param  array<string, mixed>  $tab  */
    function businessSetupTabRoute(array $tab, string $moduleKey): ?string
    {
        $routeName = config(
            'business_modules.business_module_switch_pages.'.($tab['switch_page'] ?? '').'.'.$moduleKey,
            $tab['route'] ?? null,
        );

        return is_string($routeName) && Route::has($routeName) ? $routeName : null;
    }
}

if (! function_exists('businessSetupTabAvailable')) {
    /** @param  array<string, mixed>  $tab  */
    function businessSetupTabAvailable(array $tab): bool
    {
        if (isset($tab['requires_route']) && ! Route::has($tab['requires_route'])) {
            return false;
        }

        return ! isset($tab['requires_addon']) || getCheckAddonPublishedStatus(moduleName: $tab['requires_addon']);
    }
}

if (! function_exists('businessSetupTabIsActive')) {
    /** @param  array<string, mixed>  $tab  */
    function businessSetupTabIsActive(array $tab): bool
    {
        foreach ((array) ($tab['patterns'] ?? []) as $pattern) {
            if (request()->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('isBusinessSetupPage')) {
    function isBusinessSetupPage(): bool
    {
        foreach ((array) config('business_modules.business_setup_tabs.definitions', []) as $tab) {
            if (businessSetupTabIsActive($tab)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('storefrontBusinessModules')) {
    /** @return array<int, array{key: string, label: string, description: string, icon: string, url: string, active: bool}>  */
    function storefrontBusinessModules(): array
    {
        static $resolved = null;

        if ($resolved !== null) {
            return $resolved;
        }

        $vocabulary = [
            'retail' => [
                'route' => 'home',
                'pattern' => null,
                'requires_setting' => null,
                'label' => translate('Retail'),
                'description' => translate('Perfect for personal and everyday shopping'),
            ],
            'auction' => [
                'route' => 'auction.index',
                'pattern' => 'auction*',
                'requires_setting' => 'auction_feature_status',
                'label' => translate('Auction'),
                'description' => translate('Best for bidding and creating auctions'),
            ],
            'wholesale' => [
                'route' => 'wholesale.products.index',
                'pattern' => 'wholesale*',
                'requires_setting' => null,
                'label' => translate('Wholesale'),
                'description' => translate('Ideal for bulk purchasing and business sourcing'),
            ],
        ];

        $available = businessModuleManager()->available();
        $activeKey = 'retail';

        foreach ($vocabulary as $key => $entry) {
            if ($entry['pattern'] !== null && request()->is($entry['pattern'])) {
                $activeKey = $key;
            }
        }

        $modules = [];

        foreach ($vocabulary as $key => $entry) {
            if (! isset($available[$key]) || ! Route::has($entry['route'])) {
                continue;
            }

            if ($entry['requires_setting'] !== null && ! getWebConfig(name: $entry['requires_setting'])) {
                continue;
            }

            $modules[] = [
                'key' => $key,
                'label' => $entry['label'],
                'description' => $entry['description'],
                'icon' => $available[$key]->icon,
                'url' => route($entry['route']),
                'active' => $key === $activeKey,
            ];
        }

        return $resolved = count($modules) > 1 ? $modules : [];
    }
}

if (! function_exists('storefrontHomeUrl')) {
    function storefrontHomeUrl(mixed ...$candidateUrls): string
    {
        $vocabulary = [
            BusinessModuleKey::AUCTION => [
                'route' => 'auction.index',
                'patterns' => ['auction', 'auction/*'],
            ],
            BusinessModuleKey::WHOLESALE => [
                'route' => 'wholesale.products.index',
                'patterns' => ['wholesale', 'wholesale/*', 'customer/*/wholesale', 'customer/*/wholesale/*'],
            ],
        ];

        $available = businessModuleManager()->available();

        foreach ($candidateUrls as $candidateUrl) {
            $path = storefrontUrlPath(url: $candidateUrl);

            if ($path === null) {
                continue;
            }

            foreach ($vocabulary as $key => $entry) {
                if (! isset($available[$key]) || ! Route::has($entry['route'])) {
                    continue;
                }

                if (Str::is($entry['patterns'], $path)) {
                    return route($entry['route']);
                }
            }
        }

        return route('home');
    }
}

if (! function_exists('storefrontUrlPath')) {
    function storefrontUrlPath(mixed $url): ?string
    {
        $url = (string) $url;

        if ($url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== request()->getHost()) {
            return null;
        }

        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}

if (! function_exists('activeStorefrontBusinessModule')) {
    /** @return array{key: string, label: string, description: string, icon: string, url: string, active: bool}|null  */
    function activeStorefrontBusinessModule(): ?array
    {
        foreach (storefrontBusinessModules() as $module) {
            if ($module['active']) {
                return $module;
            }
        }

        return null;
    }
}

if (! function_exists('activeStorefrontBusinessModuleKey')) {
    function activeStorefrontBusinessModuleKey(): string
    {
        $active = activeStorefrontBusinessModule();

        if ($active !== null) {
            return (string) $active['key'];
        }

        $request = request();

        if ($request !== null) {
            $patterns = [
                BusinessModuleKey::WHOLESALE => ['wholesale', 'wholesale/*', 'customer/*/wholesale', 'customer/*/wholesale/*'],
                BusinessModuleKey::AUCTION => ['auction', 'auction/*'],
            ];

            foreach ($patterns as $moduleKey => $modulePatterns) {
                if ($request->is(...$modulePatterns) && businessModuleManager()->isAvailable($moduleKey)) {
                    return $moduleKey;
                }
            }
        }

        return BusinessModuleKey::RETAIL;
    }
}

if (! function_exists('customerProfileMenuItems')) {
    /** @return array<int, array<string, mixed>>  */
    function customerProfileMenuItems(?string $moduleKey = null): array
    {
        static $resolved = [];

        $cacheKey = $moduleKey ?? '';

        return $resolved[$cacheKey] ??= app(CustomerProfileMenuBuilder::class)->build($moduleKey);
    }
}

if (! function_exists('storefrontAccountHeaderView')) {
    function storefrontAccountHeaderView(): string
    {
        return themeViewName(key: storefrontBusinessModules() === [] ? 'storefront_header' : 'storefront_account_header');
    }
}

if (! function_exists('storefrontAccountAppBarView')) {
    function storefrontAccountAppBarView(): string
    {
        return themeViewName(key: storefrontBusinessModules() === [] ? 'storefront_app_bar' : 'storefront_account_app_bar');
    }
}

if (! function_exists('storefrontShoppingAssistantView')) {
    function storefrontShoppingAssistantView(): string
    {
        $isWholesaleStorefront = (request()->is('wholesale*') || request()->routeIs('wholesale.*'))
            && businessModuleManager()->isAvailable(BusinessModuleKey::WHOLESALE);

        return $isWholesaleStorefront
            ? 'wholesale::partials.shopping-assistant._sidebar'
            : 'ai::partials.shopping-assistant._sidebar';
    }
}

if (! function_exists('storefrontUsesModuleHeaderPanel')) {
    function storefrontUsesModuleHeaderPanel(): bool
    {
        return request()->is('customer', 'customer/*')
            && storefrontBusinessModules() !== []
            && businessModuleManager()->isAvailable(BusinessModuleKey::WHOLESALE);
    }
}

if (! function_exists('vendorDetailTabLabels')) {
    /** @return array<string, string>  */
    function vendorDetailTabLabels(): array
    {
        return [
            'overview' => translate('Vendor overview'),
            'order' => translate('Order'),
            'product' => translate('Product'),
            'clearance_sale' => translate('Clearance sale product'),
            'auction_list' => translate('Auction list'),
            'review' => translate('Review'),
            'setting' => translate('Setting'),
            'transaction' => translate('Transaction'),
        ];
    }
}

if (! function_exists('vendorDetailScopeLabel')) {
    function vendorDetailScopeLabel(string $moduleKey): string
    {
        return $moduleKey === BusinessModuleKey::COMMON
            ? translate('Common')
            : translate(businessModuleManager()->get($moduleKey)?->label ?? ucfirst($moduleKey));
    }
}

if (! function_exists('vendorDetailScopeRoute')) {
    function vendorDetailScopeRoute(string $moduleKey): ?string
    {
        $routeName = config('business_modules.vendor_detail_tabs.routes.'.$moduleKey);

        return is_string($routeName) && Route::has($routeName) ? $routeName : null;
    }
}

if (! function_exists('vendorDetailTabUrl')) {
    function vendorDetailTabUrl(int|string $vendorId, string $moduleKey, string $tabKey): ?string
    {
        $landingRoute = config('business_modules.vendor_detail_tabs.landing_routes.'.$moduleKey);

        if ($tabKey === 'overview' && is_string($landingRoute) && Route::has($landingRoute)) {
            return route($landingRoute, ['id' => $vendorId]);
        }

        $routeName = vendorDetailScopeRoute($moduleKey);

        return $routeName === null ? null : route($routeName, ['id' => $vendorId, 'tab' => $tabKey]);
    }
}

if (! function_exists('vendorDetailScopeAvailable')) {
    function vendorDetailScopeAvailable(string $moduleKey): bool
    {
        if (vendorDetailScopeRoute($moduleKey) === null) {
            return false;
        }

        return $moduleKey === BusinessModuleKey::COMMON
            || businessModuleManager()->isAvailableInPanel($moduleKey);
    }
}

if (! function_exists('vendorDetailScopeOptions')) {
    /** @return array<int, array{key: string, label: string, icon: string, url: string, active: bool}>  */
    function vendorDetailScopeOptions(int|string $vendorId, string $activeModule): array
    {
        $placeholderIcon = (string) config('business_modules.switcher_placeholder_icon', 'select-business.svg');
        $options = [];

        foreach (array_keys((array) config('business_modules.vendor_detail_tabs.scopes', [])) as $moduleKey) {
            $moduleKey = (string) $moduleKey;

            if (! vendorDetailScopeAvailable($moduleKey)) {
                continue;
            }

            $firstTab = (array) config('business_modules.vendor_detail_tabs.scopes.'.$moduleKey, []);
            $url = $firstTab === [] ? null : vendorDetailTabUrl($vendorId, $moduleKey, (string) $firstTab[0]);

            if ($url === null) {
                continue;
            }

            $options[] = [
                'key' => $moduleKey,
                'label' => $moduleKey === BusinessModuleKey::COMMON ? 'Common' : (businessModuleManager()->get($moduleKey)?->label ?? ucfirst($moduleKey)),
                'icon' => $moduleKey === BusinessModuleKey::COMMON ? $placeholderIcon : (businessModuleManager()->get($moduleKey)?->icon ?? $placeholderIcon),
                'url' => $url,
                'active' => $moduleKey === $activeModule,
            ];
        }

        return $options;
    }
}

if (! function_exists('vendorDetailTabs')) {
    /** @return array<int, array{key: string, label: string, url: string, is_active: bool}>  */
    function vendorDetailTabs(int|string $vendorId, string $moduleKey, ?string $activeTab): array
    {
        $labels = vendorDetailTabLabels();
        $tabs = [];

        foreach ((array) config('business_modules.vendor_detail_tabs.scopes.'.$moduleKey, []) as $tabKey) {
            $tabKey = (string) $tabKey;
            $url = vendorDetailTabUrl($vendorId, $moduleKey, $tabKey);

            if ($url === null) {
                continue;
            }

            $tabs[] = [
                'key' => $tabKey,
                'label' => $labels[$tabKey] ?? $tabKey,
                'url' => $url,
                'is_active' => $tabKey === $activeTab,
            ];
        }

        return $tabs;
    }
}

if (! function_exists('customerDetailModuleTabs')) {
    /** @return array<int, array{key: string, label: string, url: string, is_active: bool}>  */
    function customerDetailModuleTabs(int|string $customerId, string $activeModule): array
    {
        $manager = businessModuleManager();
        $tabs = [];

        foreach ((array) config('business_modules.customer_detail_tabs.routes', []) as $moduleKey => $routeName) {
            $moduleKey = (string) $moduleKey;

            if (! $manager->isAvailableInPanel($moduleKey) || ! Route::has($routeName)) {
                continue;
            }

            $tabs[] = [
                'key' => $moduleKey,
                'label' => vendorDetailScopeLabel($moduleKey),
                'url' => route($routeName, ['user_id' => $customerId]),
                'is_active' => $moduleKey === $activeModule,
            ];
        }

        return count($tabs) > 1 ? $tabs : [];
    }
}

if (! function_exists('customerProfileModuleTabs')) {
    /**
     * @param  array<string, int|null>  $counts
     * @param  array<int, string>  $historyModules
     * @return array<int, array{key: string, label: string, url: string, is_active: bool, count: int|null}>
     */
    function customerProfileModuleTabs(string $menuKey, string $activeModule, array $counts = [], array $queryParameters = [], array $historyModules = []): array
    {
        $routes = (array) config('business_modules.customer_profile_menu.'.$menuKey.'.routes', []);

        if ($routes === []) {
            return [];
        }

        $manager = businessModuleManager();
        $tabs = [];

        foreach ($routes as $moduleKey => $routeName) {
            $moduleKey = (string) $moduleKey;

            $hasReachableHistory = in_array($moduleKey, $historyModules, true) && $manager->isInstalled($moduleKey);

            if ((! $manager->isAvailable($moduleKey) && ! $hasReachableHistory) || ! Route::has($routeName)) {
                continue;
            }

            $tabs[] = [
                'key' => $moduleKey,
                'label' => $manager->get($moduleKey)?->label ?? ucfirst($moduleKey),
                'url' => route($routeName, $queryParameters),
                'is_active' => $moduleKey === $activeModule,
                'count' => $counts[$moduleKey] ?? null,
            ];
        }

        return count($tabs) > 1 ? $tabs : [];
    }
}

if (! function_exists('customerOrderPageRoutes')) {
    /** @return array<string, string>  */
    function customerOrderPageRoutes(string $businessModule): array
    {
        $routes = (array) config('business_modules.customer_order_pages.'.$businessModule, []);

        if ($routes === []) {
            $routes = (array) config('business_modules.customer_order_pages.'.BusinessModuleKey::RETAIL, []);
        }

        return array_filter($routes, static fn (string $routeName): bool => Route::has($routeName));
    }
}
