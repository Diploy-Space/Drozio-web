<?php

namespace App\Services\BusinessModule;

use App\Utils\Helpers;
use Illuminate\Support\Facades\Route;

class BusinessModuleManager
{
    public const SESSION_KEY = 'admin_active_business_module';

    private const AREA_HOSTING_ADDON_PAGES = 'settings';

    public const DASHBOARD_PERMISSION = 'dashboard';

    /** @var array<string,BusinessModule>|null */
    private ?array $modules = null;

    /** @var array<string,BusinessModule>|null */
    private ?array $commonAreas = null;

    private ?BusinessModule $active = null;

    private ?BusinessModule $activeArea = null;

    private ?string $panel = null;

    private ?string $requestKey = null;

    private bool $requestKeyResolved = false;

    /** @var string[]|null */
    private ?array $requestSharedModules = null;

    private bool $requestSharedModulesResolved = false;

    public function panel(): string
    {
        if ($this->panel !== null) {
            return $this->panel;
        }

        $request = request();
        $isVendor = $request !== null && $request->is('vendor/*');

        return $this->panel = $isVendor ? 'vendor' : 'admin';
    }

    public function sessionKey(): string
    {
        return $this->panel().'_active_business_module';
    }

    /** @return array<string,BusinessModule> */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $definitions = (array) config('business_modules.modules', []);
        $panel = $this->panel();

        $modules = [];
        foreach ($definitions as $key => $definition) {
            $key = (string) $key;

            $override = (array) ($definition['panels'][$panel] ?? []);
            $sections = array_values(array_unique((array) ($override['sections'] ?? $definition['sections'] ?? [])));
            $landing = (string) ($override['landing'] ?? $definition['landing'] ?? ($panel.'/dashboard'));
            $requiresSetting = array_key_exists('requires_setting', $override)
                ? $override['requires_setting']
                : ($definition['requires_setting'] ?? null);

            $modules[$key] = new BusinessModule(
                key: $key,
                label: (string) ($definition['label'] ?? ucfirst($key)),
                icon: (string) ($definition['icon'] ?? ''),
                sections: $sections,
                requiresAddon: $definition['requires_addon'] ?? null,
                landing: $landing,
                requiresSetting: $requiresSetting,
                storefrontRequiresSetting: $definition['storefront_requires_setting'] ?? null,
                urlPatterns: array_values((array) ($override['url_patterns'] ?? $definition['url_patterns'] ?? [])),
                dashboardRoute: (string) ($override['dashboard_route'] ?? $definition['dashboard_route'] ?? ''),
            );
        }

        return $this->modules = $modules;
    }

    /** @return array<string,BusinessModule> */
    public function commonAreas(): array
    {
        if ($this->commonAreas !== null) {
            return $this->commonAreas;
        }

        $panel = $this->panel();

        $areas = [];
        foreach ((array) config('business_modules.common', []) as $key => $definition) {
            $key = (string) $key;

            if (($definition['panels'][$panel] ?? null) === false) {
                continue;
            }

            $override = (array) ($definition['panels'][$panel] ?? []);

            $overridesLanding = array_key_exists('landing', $override) || array_key_exists('landing_route', $override);
            if ($overridesLanding) {
                $definition['landing'] = $override['landing'] ?? '';
                $definition['landing_route'] = $override['landing_route'] ?? '';
                $definition['landing_routes'] = $override['landing_routes'] ?? [];
            }

            $areas[$key] = new BusinessModule(
                key: $key,
                label: (string) ($override['label'] ?? $definition['label'] ?? ucfirst($key)),
                icon: (string) ($override['icon'] ?? $definition['icon'] ?? ''),
                sections: array_values(array_unique((array) ($override['sections'] ?? $definition['sections'] ?? []))),
                requiresAddon: null,
                landing: (string) ($definition['landing'] ?? ''),
                urlPatterns: array_values((array) ($override['url_patterns'] ?? $definition['url_patterns'] ?? [])),
                isBusiness: false,
                landingRoute: (string) ($definition['landing_route'] ?? ''),
                requiresPermission: array_values((array) ($override['requires_permission'] ?? $definition['requires_permission'] ?? [])),
                landingRoutes: (array) ($override['landing_routes'] ?? $definition['landing_routes'] ?? []),
            );
        }

        return $this->commonAreas = $areas;
    }

    /** @return array<string,BusinessModule> */
    public function permittedCommonAreas(): array
    {
        return array_filter($this->commonAreas(), function (BusinessModule $area): bool {
            if (! $area->requiresPermission) {
                return true;
            }

            foreach ($area->requiresPermission as $permission) {
                if (Helpers::module_permission_check($permission)) {
                    return true;
                }
            }

            return false;
        });
    }

    /** @return array<string,BusinessModule> */
    public function areas(): array
    {
        return $this->all() + $this->commonAreas();
    }

    public function get(string $key): ?BusinessModule
    {
        return $this->all()[$key] ?? null;
    }

    public function isInstalled(string $key): bool
    {
        $module = $this->get($key);
        if (! $module) {
            return false;
        }

        if ($module->requiresAddon === null) {
            return true;
        }

        return function_exists('getCheckAddonPublishedStatus')
            && (bool) getCheckAddonPublishedStatus(moduleName: $module->requiresAddon);
    }

    /** @return array<string,BusinessModule> */
    public function installed(): array
    {
        return array_filter(
            $this->all(),
            fn (BusinessModule $module) => $this->isInstalled($module->key)
        );
    }

    public function isAvailableInPanel(string $key): bool
    {
        return $this->isInstalled($key)
            && $this->areSettingsEnabled(settings: $this->get($key)->requiresSetting);
    }

    /** @return array<string,BusinessModule> */
    public function availableInPanel(): array
    {
        return array_filter(
            $this->all(),
            fn (BusinessModule $module) => $this->isAvailableInPanel($module->key)
        );
    }

    public function isAvailable(string $key): bool
    {
        return $this->isAvailableInPanel($key)
            && $this->areSettingsEnabled(settings: $this->get($key)->storefrontRequiresSetting);
    }

    /** @return array<string,BusinessModule> */
    public function available(): array
    {
        return array_filter(
            $this->all(),
            fn (BusinessModule $module) => $this->isAvailable($module->key)
        );
    }

    private function areSettingsEnabled(string|array|null $settings): bool
    {
        if ($settings === null || ! function_exists('getWebConfig')) {
            return true;
        }

        foreach ((array) $settings as $setting) {
            $value = getWebConfig(name: $setting);
            if (! is_null($value) && ! $value) {
                return false;
            }
        }

        return true;
    }

    public function defaultKey(): string
    {
        $default = (string) config('business_modules.default', 'retail');
        $permitted = $this->permittedBusinessModules();

        if (isset($permitted[$default])) {
            return $default;
        }

        if ($permitted !== []) {
            return (string) array_key_first($permitted);
        }

        if ($this->isAvailableInPanel($default)) {
            return $default;
        }

        $available = $this->availableInPanel();

        return $available ? (string) array_key_first($available) : $default;
    }

    /** @return array<string,BusinessModule> */
    public function permittedBusinessModules(): array
    {
        $available = $this->availableInPanel();

        if ($this->panel() !== 'admin' || ! auth('admin')->check()) {
            return $available;
        }

        return array_filter(
            $available,
            fn (BusinessModule $module) => Helpers::module_permission_check($module->key.'.'.self::DASHBOARD_PERMISSION)
        );
    }

    public function landingUrlFor(BusinessModule $area): string
    {
        foreach ($area->landingRoutes as $permission => $routeName) {
            if (Route::has($routeName) && Helpers::module_permission_check($permission)) {
                return route($routeName);
            }
        }

        if ($area->landingRoute !== '' && Route::has($area->landingRoute)) {
            return route($area->landingRoute);
        }

        return $area->landing !== '' ? url($area->landing) : '';
    }

    public function permittedLandingUrl(): string
    {
        foreach ($this->permittedBusinessModules() as $module) {
            if ($module->dashboardRoute !== '' && Route::has($module->dashboardRoute)) {
                return route($module->dashboardRoute);
            }
        }

        foreach ($this->permittedCommonAreas() as $area) {
            $url = $this->landingUrlFor($area);

            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    public function activeKey(): string
    {
        $fromUrl = $this->keyFromRequest();
        if ($fromUrl !== null) {
            $this->rememberActiveKey($fromUrl);

            return $fromUrl;
        }

        $stored = session($this->sessionKey());
        $stored = is_string($stored) && $this->isAvailableInPanel($stored) ? $stored : $this->defaultKey();

        $sharedWith = $this->sharedModulesFromRequest();
        if ($sharedWith !== null && ! in_array($stored, $sharedWith, true)) {
            $owner = $this->firstAvailableOf($sharedWith);
            if ($owner !== null) {
                $this->rememberActiveKey($owner);

                return $owner;
            }
        }

        return $stored;
    }

    public function keyFromRequest(): ?string
    {
        if ($this->requestKeyResolved) {
            return $this->requestKey;
        }

        $this->requestKeyResolved = true;
        $request = request();
        if ($request === null) {
            return $this->requestKey = null;
        }

        foreach ($this->all() as $key => $module) {
            if ($module->urlPatterns && $request->is(...$module->urlPatterns) && $this->isAvailableInPanel($key)) {
                return $this->requestKey = $key;
            }
        }

        return $this->requestKey = null;
    }

    /** @return string[]|null */
    public function sharedModulesFromRequest(): ?array
    {
        if ($this->requestSharedModulesResolved) {
            return $this->requestSharedModules;
        }

        $this->requestSharedModulesResolved = true;
        $request = request();
        if ($request === null) {
            return $this->requestSharedModules = null;
        }

        foreach ((array) config('business_modules.shared_urls', []) as $group) {
            $patterns = (array) ($group['patterns'] ?? []);
            if ($patterns && $request->is(...$patterns)) {
                return $this->requestSharedModules = array_values((array) ($group['modules'] ?? []));
            }
        }

        return $this->requestSharedModules = null;
    }

    /** @param  string[]  $keys */
    private function firstAvailableOf(array $keys): ?string
    {
        $default = $this->defaultKey();
        if (in_array($default, $keys, true) && $this->isAvailableInPanel($default)) {
            return $default;
        }

        foreach ($keys as $key) {
            if ($this->isAvailableInPanel($key)) {
                return $key;
            }
        }

        return null;
    }

    private function rememberActiveKey(string $key): void
    {
        $request = request();
        if ($request === null || ! $request->hasSession()) {
            return;
        }

        $sessionKey = $this->sessionKey();
        if (session($sessionKey) !== $key) {
            session()->put($sessionKey, $key);
        }
    }

    public function active(): BusinessModule
    {
        $key = $this->activeKey();
        if ($this->active && $this->active->key === $key) {
            return $this->active;
        }

        return $this->active = $this->get($key) ?? $this->fallback();
    }

    public function activeArea(): BusinessModule
    {
        if ($this->activeArea !== null) {
            return $this->activeArea;
        }

        if ($this->keyFromRequest() !== null) {
            return $this->activeArea = $this->active();
        }

        $request = request();
        if ($request !== null) {
            foreach ($this->commonAreas() as $area) {
                if ($area->urlPatterns && $request->is(...$area->urlPatterns)) {
                    return $this->activeArea = $area;
                }
            }

            $addonPatterns = $this->addonAdminUrlPatterns();
            $settings = $this->commonAreas()[self::AREA_HOSTING_ADDON_PAGES] ?? null;

            if ($settings !== null && $addonPatterns && $request->is(...$addonPatterns)) {
                return $this->activeArea = $settings;
            }
        }

        return $this->activeArea = $this->active();
    }

    /** @return array<int, string> */
    private function addonAdminUrlPatterns(): array
    {
        $patterns = [];

        foreach ((array) config('addon_admin_routes', []) as $group) {
            foreach ((array) $group as $route) {
                $path = trim((string) ($route['path'] ?? ''), '/');

                if ($path !== '') {
                    $patterns[] = $path;
                    $patterns[] = $path.'/*';
                }
            }
        }

        return $patterns;
    }

    public function isCommonArea(): bool
    {
        return ! $this->activeArea()->isBusiness;
    }

    public function itemVisible(string $itemKey): bool
    {
        return $this->itemVisibleFor(itemKey: $itemKey, businessModule: $this->activeKey());
    }

    public function itemVisibleFor(string $itemKey, string $businessModule): bool
    {
        $map = (array) config('business_modules.module_items', []);
        if (! array_key_exists($itemKey, $map)) {
            return true;
        }

        return in_array($businessModule, (array) $map[$itemKey], true);
    }

    public function setActive(string $key): bool
    {
        if (! $this->isAvailableInPanel($key)) {
            return false;
        }

        session()->put($this->sessionKey(), $key);
        $this->active = $this->get($key);
        $this->activeArea = null;

        return true;
    }

    private function fallback(): BusinessModule
    {
        $available = $this->availableInPanel();
        if ($available) {
            return reset($available);
        }

        return new BusinessModule(
            key: 'retail',
            label: 'Retail',
            icon: 'retails-icon.svg',
            sections: ['home'],
            requiresAddon: null,
            landing: $this->panel().'/dashboard',
        );
    }
}
