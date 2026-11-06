<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvancedSearchService
{
    public const TYPE_MENU = 'menu';

    public const TYPE_PAGE = 'page';

    public const PANEL_ADMIN = 'admin';

    public const PANEL_VENDOR = 'vendor';

    private const PANELS = [
        self::PANEL_ADMIN => [
            'uri_prefix' => 'admin/',
            'menus_config' => 'advanced_search.menus',
            'sources_config' => 'advanced_search.sources',
            'theme_skip_config' => 'advanced_search.theme_skip_uris',
            'module_sources_key' => 'advanced_search.sources',
            'module_common_sources_key' => 'advanced_search.common_sources',
            'module_menu_files_key' => 'advanced_search.menu_files',
            'default_addon_menu_files' => ['Addon/admin_routes.php'],
            'report_pages_config' => 'business_modules.report_module_switch_pages.admin',
            'report_uri_prefix' => 'admin/report/',
            'page_index_path' => 'json/admin/admin_formatted_routes.json',
            'page_keywords_directory' => 'json/admin/lang',
            'available_after_version_config' => null,
        ],
        self::PANEL_VENDOR => [
            'uri_prefix' => 'vendor/',
            'menus_config' => 'advanced_search.vendor.menus',
            'sources_config' => 'advanced_search.vendor.sources',
            'theme_skip_config' => 'advanced_search.vendor.theme_skip_uris',
            'module_sources_key' => 'advanced_search.vendor_sources',
            'module_common_sources_key' => 'advanced_search.vendor_common_sources',
            'module_menu_files_key' => 'advanced_search.vendor_menu_files',
            'default_addon_menu_files' => [],
            'report_pages_config' => 'business_modules.report_module_switch_pages.vendor',
            'report_uri_prefix' => 'vendor/report/',
            'page_index_path' => 'json/vendor/vendor_formatted_routes.json',
            'page_keywords_directory' => 'json/vendor/lang',
            'available_after_version_config' => 'advanced_search.vendor.available_after_version',
        ],
    ];

    private const PERMISSION_MIDDLEWARE_PREFIX = 'module:';

    private const TITLE_MATCH_SCORE = 1;

    private const MENU_TITLE_SEPARATOR = ' › ';

    private const ADDON_CHILD_KEYS = ['routes', 'sub_routes'];

    private const DEFAULT_LOCALE = 'en';

    private const KEYWORD_IGNORED_SEGMENTS = ['admin', 'vendor', 'products', 'list', 'business settings'];

    private const KEYWORD_MIN_SEGMENT_LENGTH = 3;

    private const SINGULAR_MIN_TOKEN_LENGTH = 3;

    private const EMPTY_PAGE_KEYWORDS = '{}';

    private const LOCALE_PATTERN = '/^[A-Za-z0-9_-]+$/';

    private const DEFAULT_PAGE_METHOD = 'GET';

    private const ADDON_INFO_FILE = 'Addon/info.php';

    private const MODULE_CONTROLLER_PATTERN = '/^Modules\\\\([^\\\\]+)\\\\/';

    private array $menus = [];

    private array $matchedRoutes = [];

    private array $sources = [];

    private string $panel = self::PANEL_ADMIN;

    public function forPanel(string $panel): self
    {
        $service = clone $this;
        $service->panel = array_key_exists($panel, self::PANELS) ? $panel : self::PANEL_ADMIN;
        $service->menus = [];
        $service->sources = [];

        return $service;
    }

    public function isAvailable(): bool
    {
        $configKey = $this->panelSetting(key: 'available_after_version_config');

        if ($configKey === null) {
            return true;
        }

        return version_compare(SOFTWARE_VERSION, (string) config($configKey), '>');
    }

    public function getUriPrefix(): string
    {
        return $this->panelSetting(key: 'uri_prefix');
    }

    public function getSources(string $businessModule): array
    {
        return $this->sources[$businessModule] ??= $this->loadSources(businessModule: $businessModule);
    }

    public function getResultTypes(): array
    {
        $types = [self::TYPE_MENU, self::TYPE_PAGE];

        foreach (BusinessModule::values() as $businessModule) {
            $types = [...$types, ...array_keys($this->getSources(businessModule: $businessModule))];
        }

        return array_values(array_unique($types));
    }

    public function getRequiredPermissions(string $uri): ?array
    {
        $route = $this->matchRoute(uri: $uri);

        if ($route === null) {
            return null;
        }

        $excludedMiddleware = $route->excludedMiddleware();
        $permissionGroups = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, self::PERMISSION_MIDDLEWARE_PREFIX) && ! in_array($middleware, $excludedMiddleware, true)) {
                $permissionGroups[] = explode(',', substr($middleware, strlen(self::PERMISSION_MIDDLEWARE_PREFIX)));
            }
        }

        return $permissionGroups === [] ? null : $permissionGroups;
    }

    public function rank(array $items, string $keyword): array
    {
        $searchTerm = mb_strtolower($keyword);
        $themeSkipUris = $this->getThemeSkipUris();

        return collect($items)
            ->filter(fn (array $item): bool => isset($item['priority'], $item['uri'])
                && ! in_array($item['uri'], $themeSkipUris, true)
                && $this->isReachable(uri: (string) $item['uri']))
            ->map(function (array $item) use ($searchTerm): array {
                $title = mb_strtolower((string) ($item['page_title'] ?? ''));
                $isTitleMatch = str_contains($title, $searchTerm) || str_contains(str_replace(' ', '', $title), str_replace(' ', '', $searchTerm));
                $item['match_score'] = $isTitleMatch ? self::TITLE_MATCH_SCORE : 0;

                return $item;
            })
            ->sort(fn (array $first, array $second): int => [$second['match_score'], $first['priority']] <=> [$first['match_score'], $second['priority']])
            ->values()
            ->groupBy('type')
            ->map(fn (Collection $group): Collection => $group->unique('uri')->values())
            ->toArray();
    }

    public function getMenuMatches(string $businessModule, string $keyword, string $locale, bool $excludeOtherModulePages = false): array
    {
        $searchTerm = $this->stripBusinessModulePrefix(searchTerm: $this->normalizeMenuText(text: $keyword), businessModule: $businessModule);

        if ($searchTerm === '') {
            return [];
        }

        $translatedTitles = $this->getTranslatedTitleKeys(searchTerm: $searchTerm, locale: $locale);
        $isBusinessModuleNamed = $this->namesBusinessModule(keyword: $this->normalizeMenuText(text: $keyword), businessModule: $businessModule);

        return collect($this->getMenus(businessModule: $businessModule))
            ->reject(fn (array $menu): bool => ($isBusinessModuleNamed || $excludeOtherModulePages) && $menu['is_other_module_page'])
            ->filter(fn (array $menu): bool => $this->isMatchingMenu(menu: $menu, searchTerm: $searchTerm, translatedTitles: $translatedTitles))
            ->filter(fn (array $menu): bool => ! $menu['requires_route_check'] || $this->isReachable(uri: $menu['uri']))
            ->unique('uri')
            ->map(fn (array $menu): array => collect($menu)->except(['requires_route_check', 'is_other_module_page'])->all())
            ->values()
            ->all();
    }

    public function getPageMatches(string $businessModule, string $keyword, string $locale): array
    {
        $searchTerm = $this->stripBusinessModulePrefix(
            searchTerm: mb_strtolower(trim((string) preg_replace('/[\s_]+/u', ' ', $keyword))),
            businessModule: $businessModule,
        );

        if ($searchTerm === '') {
            return [];
        }

        $matchingPages = $this->getMatchingPageKeys(
            searchTerm: $searchTerm,
            locale: $locale,
            menuTitlesByPageKey: $this->getMenuTitlesByPageKey(businessModule: $businessModule),
        );

        if ($matchingPages === []) {
            return [];
        }

        return collect($this->readJsonFile(path: public_path($this->panelSetting(key: 'page_index_path'))))
            ->filter(fn (mixed $page): bool => is_array($page)
                && isset($page['key'], $page['uri'], $page['page_title'], $matchingPages[$page['key']])
                && in_array($page['business_module'] ?? null, [$businessModule, BusinessModule::COMMON], true))
            ->sortByDesc(fn (array $page): bool => $matchingPages[$page['key']]['is_title_match'])
            ->map(fn (array $page): array => [
                'page_title' => $matchingPages[$page['key']]['title'] !== '' ? $matchingPages[$page['key']]['title'] : (string) $page['page_title'],
                'key' => (string) $page['key'],
                'uri' => (string) $page['uri'],
                'uri_count' => count(explode('/', (string) $page['uri'])),
                'method' => (string) ($page['method'] ?? self::DEFAULT_PAGE_METHOD),
                'business_module' => (string) $page['business_module'],
                'owner_business_module' => $page['business_module'] === BusinessModule::COMMON ? null : (string) $page['business_module'],
            ])
            ->values()
            ->all();
    }

    public function isReachable(string $uri): bool
    {
        return $this->matchRoute(uri: $uri) !== null;
    }

    public function getMenus(string $businessModule): array
    {
        return $this->menus[$businessModule] ??= array_merge(
            $this->getCoreMenus(businessModule: $businessModule),
            $this->getAddonMenuEntries(businessModule: $businessModule),
        );
    }

    public function getAddonMenuEntries(string $businessModule): array
    {
        $addonName = $this->getAddonName(businessModule: $businessModule);

        if ($addonName === null) {
            return [];
        }

        $entries = [];

        foreach ((array) config($businessModule.'.'.$this->panelSetting(key: 'module_menu_files_key'), $this->panelSetting(key: 'default_addon_menu_files')) as $file) {
            foreach ($this->loadArrayFile(path: base_path('Modules/'.$addonName.'/'.$file)) as $group) {
                $entries = array_merge($entries, $this->buildAddonGroupEntries(group: (array) $group, businessModule: $businessModule));
            }
        }

        return $entries;
    }

    private function getCoreMenus(string $businessModule): array
    {
        $menus = [];

        foreach ((array) config($this->panelSetting(key: 'menus_config'), []) as $menu) {
            if (! in_array($menu['business_module'], [$businessModule, BusinessModule::COMMON], true) || ! $this->isThemeMenu(menu: $menu)) {
                continue;
            }

            $uri = $this->resolveCoreMenuUri(menu: $menu, businessModule: $businessModule);

            if ($uri !== null) {
                $isOtherModulePage = isset($menu['report_page']) && ! $this->hasOwnReportPage(reportPage: $menu['report_page'], businessModule: $businessModule);
                $keywords = (array) $menu['keywords'];

                if (str_starts_with($uri, $this->panelSetting(key: 'report_uri_prefix').$businessModule.'/')) {
                    $keywords[] = businessModuleManager()->get($businessModule)?->label.' '.$menu['title'];
                }

                $menus[] = [
                    ...$this->makeMenu(
                        title: $menu['title'],
                        keywords: $keywords,
                        uri: $uri,
                        permission: (array) ($menu['permission'] ?? []),
                        businessModule: $menu['business_module'],
                        requiresRouteCheck: false,
                        parentTitle: $menu['parent_title'] ?? null,
                        visibility: (array) ($menu['visibility'] ?? []),
                    ),
                    'is_other_module_page' => $isOtherModulePage,
                    'owner_business_module' => $this->resolveCoreMenuOwner(menu: $menu, businessModule: $businessModule),
                ];
            }
        }

        return $menus;
    }

    private function isThemeMenu(array $menu): bool
    {
        return ! isset($menu['themes']) || in_array(theme_root_path(), (array) $menu['themes'], true);
    }

    private function resolveCoreMenuUri(array $menu, string $businessModule): ?string
    {
        if (isset($menu['report_page'])) {
            return $this->resolveRouteUri(routeName: $this->getReportPageRouteName(reportPage: $menu['report_page'], businessModule: $businessModule), parameters: []);
        }

        if ($menu['route'] === null) {
            return $this->getDashboardUri(businessModule: $businessModule);
        }

        $routeName = is_array($menu['route']) ? ($menu['route'][$businessModule] ?? null) : $menu['route'];

        return $this->resolveRouteUri(routeName: $routeName, parameters: (array) ($menu['route_parameters'] ?? []));
    }

    private function resolveCoreMenuOwner(array $menu, string $businessModule): ?string
    {
        if (isset($menu['owner_business_module'])) {
            return $menu['owner_business_module'];
        }

        if ($menu['business_module'] !== BusinessModule::COMMON) {
            return $menu['business_module'];
        }

        if (isset($menu['report_page'])) {
            $routeName = $this->getReportPageRouteName(reportPage: $menu['report_page'], businessModule: $businessModule);
            $owner = array_search($routeName, (array) config($this->panelSetting(key: 'report_pages_config').'.'.$menu['report_page'], []), true);

            return is_string($owner) ? $owner : null;
        }

        return $menu['route'] === null || is_array($menu['route']) ? $businessModule : null;
    }

    private function getReportPageRouteName(string $reportPage, string $businessModule): ?string
    {
        $moduleRoutes = (array) config($this->panelSetting(key: 'report_pages_config').'.'.$reportPage, []);

        foreach ([$moduleRoutes[$businessModule] ?? null, ...array_values($moduleRoutes)] as $routeName) {
            if (is_string($routeName) && Route::has($routeName)) {
                return $routeName;
            }
        }

        return null;
    }

    private function hasOwnReportPage(string $reportPage, string $businessModule): bool
    {
        $routeName = config($this->panelSetting(key: 'report_pages_config').'.'.$reportPage.'.'.$businessModule);

        return is_string($routeName) && Route::has($routeName);
    }

    private function getDashboardUri(string $businessModule): string
    {
        $dashboardRoute = businessModuleManager()->get($businessModule)?->dashboardRoute;

        return $this->resolveRouteUri(routeName: $dashboardRoute, parameters: [])
            ?? $this->relativeUri(url: businessModuleDashboardUrl());
    }

    private function resolveRouteUri(?string $routeName, array $parameters): ?string
    {
        if ($routeName === null || $routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        return ltrim(route($routeName, $parameters, false), '/');
    }

    private function relativeUri(string $url): string
    {
        return trim(str_replace(url('/'), '', $url), '/');
    }

    private function getAddonName(string $businessModule): ?string
    {
        if (! in_array($businessModule, BusinessModule::values(), true) || ! businessModuleManager()->isAvailableInPanel($businessModule)) {
            return null;
        }

        return businessModuleManager()->get($businessModule)?->requiresAddon;
    }

    private function loadArrayFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $contents = include $path;

        return is_array($contents) ? $contents : [];
    }

    private function buildAddonGroupEntries(array $group, string $businessModule): array
    {
        $groupName = trim((string) ($group['name'] ?? ''));
        $groupPermission = (array) ($group['permission'] ?? [$businessModule.'.dashboard']);
        $moduleLabel = (string) businessModuleManager()->get($businessModule)?->label;

        $entries = [];

        foreach (self::ADDON_CHILD_KEYS as $childKey) {
            foreach ((array) ($group[$childKey] ?? []) as $item) {
                $entries[] = $this->buildAddonEntry(
                    item: (array) $item,
                    groupName: $groupName,
                    permission: (array) ($item['permission'] ?? $groupPermission),
                    businessModule: $businessModule,
                    parentTitle: $groupName === '' ? null : $groupName,
                );
            }
        }

        $entries[] = $this->buildAddonEntry(item: $group, groupName: $moduleLabel, permission: $groupPermission, businessModule: $businessModule);

        return array_values(array_filter($entries));
    }

    private function buildAddonEntry(array $item, string $groupName, array $permission, string $businessModule, ?string $parentTitle = null): ?array
    {
        $name = trim((string) ($item['name'] ?? ''));

        if ($name === '' || ($item['searchable'] ?? true) === false) {
            return null;
        }

        $requiresRouteCheck = ! isset($item['route']);
        $uri = $requiresRouteCheck
            ? (isset($item['url']) ? $this->relativeUri(url: (string) $item['url']) : null)
            : $this->resolveRouteUri(routeName: (string) $item['route'], parameters: (array) ($item['route_parameters'] ?? []));

        if ($uri === null || $uri === '') {
            return null;
        }

        return $this->makeMenu(
            title: $name,
            keywords: [
                ...$this->buildAddonKeywords(name: $name, groupName: $groupName, uri: $uri, businessModule: $businessModule),
                ...(array) ($item['keywords'] ?? []),
            ],
            uri: $uri,
            permission: $permission,
            businessModule: $businessModule,
            requiresRouteCheck: $requiresRouteCheck,
            parentTitle: $parentTitle,
            visibility: (array) ($item['visibility'] ?? []),
        );
    }

    private function makeMenu(string $title, array $keywords, string $uri, array $permission, string $businessModule, bool $requiresRouteCheck, ?string $parentTitle = null, array $visibility = []): array
    {
        return [
            'title' => $title,
            'parent_title' => $parentTitle,
            'keywords' => array_values($keywords),
            'uri' => $uri,
            'permission' => array_values($permission),
            'business_module' => $businessModule,
            'requires_route_check' => $requiresRouteCheck,
            'is_other_module_page' => false,
            'owner_business_module' => $businessModule === BusinessModule::COMMON ? null : $businessModule,
            'visibility' => $visibility,
        ];
    }

    private function buildAddonKeywords(string $name, string $groupName, string $uri, string $businessModule): array
    {
        $variants = [];
        $addVariant = function (string $variant) use (&$variants): void {
            $variant = trim((string) preg_replace('/\s+/', ' ', mb_strtolower($variant)));

            if ($variant !== '') {
                $variants[$variant] = true;
            }
        };

        $nameVariant = mb_strtolower($name);
        $groupVariant = mb_strtolower($groupName);

        $addVariant($nameVariant);

        if ($groupVariant !== '') {
            $addVariant($groupVariant);
            $addVariant($nameVariant.' '.$groupVariant);
            $addVariant($groupVariant.' '.$nameVariant);
        }

        $singularName = $this->toSingularPhrase(phrase: $nameVariant);
        $singularGroup = $this->toSingularPhrase(phrase: $groupVariant);

        if ($singularName !== $nameVariant) {
            $addVariant($singularName);

            if ($singularGroup !== '') {
                $addVariant($singularName.' '.$singularGroup);
                $addVariant($singularGroup.' '.$singularName);
            }
        }

        foreach ([$nameVariant, $singularName] as $variant) {
            if ($variant !== '' && ! str_contains($variant, $businessModule)) {
                $addVariant($businessModule.' '.$variant);
                $addVariant($variant.' '.$businessModule);
            }
        }

        $ignoredSegments = [...self::KEYWORD_IGNORED_SEGMENTS, $businessModule];

        foreach (explode('/', explode('?', $uri)[0]) as $segment) {
            $segmentVariant = mb_strtolower(str_replace(['-', '_'], ' ', $segment));

            if (strlen($segmentVariant) >= self::KEYWORD_MIN_SEGMENT_LENGTH && ! in_array($segmentVariant, $ignoredSegments, true)) {
                $addVariant($businessModule.' '.$segmentVariant);
                $addVariant($segmentVariant.' '.$businessModule);
            }
        }

        return array_keys($variants);
    }

    private function toSingularPhrase(string $phrase): string
    {
        $tokens = preg_split('/\s+/', trim($phrase)) ?: [];

        return implode(' ', array_map(fn (string $token): string => $this->toSingularWord(word: $token), $tokens));
    }

    private function toSingularWord(string $word): string
    {
        if (strlen($word) <= self::SINGULAR_MIN_TOKEN_LENGTH) {
            return $word;
        }

        return match (true) {
            str_ends_with($word, 'ies') => substr($word, 0, -3).'y',
            str_ends_with($word, 'sses') => substr($word, 0, -2),
            str_ends_with($word, 's') && ! str_ends_with($word, 'ss') => substr($word, 0, -1),
            default => $word,
        };
    }

    private function isMatchingMenu(array $menu, string $searchTerm, array $translatedTitles): bool
    {
        $searchTokens = explode(' ', $searchTerm);

        $parentCandidates = $menu['parent_title'] === null ? [] : [$menu['parent_title'].' '.$menu['title']];

        foreach ([$menu['title'], ...$parentCandidates, ...$menu['keywords']] as $candidate) {
            $candidate = $this->normalizeMenuText(text: normalizeTranslationKey((string) $candidate));

            if (isset($translatedTitles[$candidate]) || $this->containsEverySearchToken(candidate: $candidate, searchTokens: $searchTokens)) {
                return true;
            }
        }

        return false;
    }

    private function containsEverySearchToken(string $candidate, array $searchTokens): bool
    {
        $candidateForms = [$candidate, $this->toSingularPhrase(phrase: $candidate)];

        return collect($searchTokens)->every(
            fn (string $token): bool => collect($candidateForms)
                ->contains(fn (string $candidateForm): bool => str_contains($candidateForm, $token))
        );
    }

    private function normalizeMenuText(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', str_replace('_', ' ', mb_strtolower($text))));
    }

    private function namesBusinessModule(string $keyword, string $businessModule): bool
    {
        foreach ($this->getBusinessModuleNames(businessModule: $businessModule) as $name) {
            if (str_contains(' '.$keyword.' ', ' '.$name.' ')) {
                return true;
            }
        }

        return false;
    }

    private function getBusinessModuleNames(string $businessModule): array
    {
        return array_unique(array_filter([
            mb_strtolower($businessModule),
            mb_strtolower((string) businessModuleManager()->get($businessModule)?->label),
        ]));
    }

    private function stripBusinessModulePrefix(string $searchTerm, string $businessModule): string
    {
        foreach ($this->getBusinessModuleNames(businessModule: $businessModule) as $prefix) {
            if (str_starts_with($searchTerm.' ', $prefix.' ')) {
                $remainder = trim(mb_substr($searchTerm, mb_strlen($prefix)));

                if ($remainder !== '') {
                    return $remainder;
                }
            }
        }

        return $searchTerm;
    }

    private function getTranslatedTitleKeys(string $searchTerm, string $locale): array
    {
        if ($locale === self::DEFAULT_LOCALE || preg_match(self::LOCALE_PATTERN, $locale) !== 1) {
            return [];
        }

        $titles = [];

        foreach (['messages.php', 'new-messages.php'] as $file) {
            foreach ($this->loadArrayFile(path: base_path('resources/lang/'.$locale.'/'.$file)) as $key => $value) {
                if (is_string($value) && str_contains($this->normalizeMenuText(text: $value), $searchTerm)) {
                    $titles[$this->normalizeMenuText(text: normalizeTranslationKey((string) $key))] = true;
                }
            }
        }

        return $titles;
    }

    private function matchRoute(string $uri): ?RegisteredRoute
    {
        if (! array_key_exists($uri, $this->matchedRoutes)) {
            $this->matchedRoutes[$uri] = $this->findRegisteredRoute(uri: $uri);
        }

        return $this->matchedRoutes[$uri];
    }

    private function findRegisteredRoute(string $uri): ?RegisteredRoute
    {
        try {
            $route = Route::getRoutes()->match(Request::create(url(ltrim($uri, '/')), 'GET'));
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return null;
        }

        return $route->isFallback || $this->isOwnedByUnavailableAddon(route: $route) ? null : $route;
    }

    private function isOwnedByUnavailableAddon(RegisteredRoute $route): bool
    {
        $controller = $route->getControllerClass();

        if (! is_string($controller) || preg_match(self::MODULE_CONTROLLER_PATTERN, $controller, $matches) !== 1) {
            return false;
        }

        $moduleName = $matches[1];

        return is_file(base_path('Modules/'.$moduleName.'/'.self::ADDON_INFO_FILE))
            && ! $this->isAddonAvailable(moduleName: $moduleName, moduleKey: mb_strtolower($moduleName));
    }

    private function getThemeSkipUris(): array
    {
        return (array) config($this->panelSetting(key: 'theme_skip_config').'.'.theme_root_path(), []);
    }

    private function getMatchingPageKeys(string $searchTerm, string $locale, array $menuTitlesByPageKey): array
    {
        $matchingPages = [];

        foreach ($this->readJsonFile(path: $this->getPageKeywordsPath(locale: $locale)) as $page) {
            if (! is_array($page) || ! isset($page['key'])) {
                continue;
            }

            $titles = [(string) ($page['page_title_value'] ?? ''), (string) ($page['page_title'] ?? '')];
            $menuTitles = $menuTitlesByPageKey[(string) $page['key']] ?? [];
            $searchableTitles = [...$titles, ...array_values($menuTitles)];

            if ($this->isMatchingPage(texts: $searchableTitles, searchTerm: $searchTerm, matchEachWord: true)) {
                $storedTitle = $titles[0] !== '' ? $titles[0] : $titles[1];
                $matchingPages[(string) $page['key']] = ['title' => $menuTitles[$storedTitle] ?? $storedTitle, 'is_title_match' => true];
            }
        }

        return $matchingPages;
    }

    private function getMenuTitlesByPageKey(string $businessModule): array
    {
        $menuTitles = [];

        foreach ($this->getMenus(businessModule: $businessModule) as $menu) {
            $menuTitles[base64_encode($menu['uri'])][$menu['title']] = $menu['parent_title'] === null
                ? $menu['title']
                : $menu['parent_title'].self::MENU_TITLE_SEPARATOR.$menu['title'];
        }

        return $menuTitles;
    }

    private function isMatchingPage(array $texts, string $searchTerm, bool $matchEachWord): bool
    {
        $words = array_filter(explode(' ', $searchTerm), fn (string $word): bool => $word !== '');
        $collapsedTerm = str_replace(' ', '', $searchTerm);

        foreach ($texts as $text) {
            $normalizedText = mb_strtolower(trim((string) preg_replace('/[\s_]+/u', ' ', (string) $text)));

            if ($normalizedText === '') {
                continue;
            }

            if (str_contains($normalizedText, $searchTerm)) {
                return true;
            }

            if ($collapsedTerm !== '' && $this->isWholeWordRun(text: $normalizedText, collapsedTerm: $collapsedTerm)) {
                return true;
            }

            if ($matchEachWord && count($words) > 1 && collect($words)->every(fn (string $word): bool => str_contains($normalizedText, $word))) {
                return true;
            }
        }

        return false;
    }

    private function isWholeWordRun(string $text, string $collapsedTerm): bool
    {
        $words = explode(' ', $text);

        foreach (array_keys($words) as $start) {
            $run = '';

            foreach (array_slice($words, $start) as $word) {
                $run .= $word;

                if ($run === $collapsedTerm) {
                    return true;
                }

                if (strlen($run) >= strlen($collapsedTerm)) {
                    break;
                }
            }
        }

        return false;
    }

    private function getPageKeywordsPath(string $locale): string
    {
        $locale = preg_match(self::LOCALE_PATTERN, $locale) === 1 ? $locale : self::DEFAULT_LOCALE;
        $path = public_path($this->panelSetting(key: 'page_keywords_directory').'/'.$locale.'.json');

        if (! File::exists($path)) {
            $defaultPath = public_path($this->panelSetting(key: 'page_keywords_directory').'/'.self::DEFAULT_LOCALE.'.json');

            File::ensureDirectoryExists(dirname($path));
            File::put($path, File::exists($defaultPath) ? File::get($defaultPath) : self::EMPTY_PAGE_KEYWORDS);
        }

        return $path;
    }

    private function readJsonFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $contents = json_decode((string) file_get_contents($path), true);

        return is_array($contents) ? $contents : [];
    }

    private function loadSources(string $businessModule): array
    {
        $sources = array_merge(
            $this->tagSourceOrigin(sources: $this->getBusinessModuleSources(businessModule: $businessModule), businessModule: $businessModule),
            $this->tagSourceOrigin(sources: (array) config($this->panelSetting(key: 'sources_config').'.'.BusinessModule::COMMON, []), businessModule: BusinessModule::COMMON),
            $this->tagSourceOrigin(sources: $this->getAddonCommonSources(), businessModule: BusinessModule::COMMON),
        );

        return array_filter($sources, fn (mixed $source): bool => $this->isResolvableSource(source: $source));
    }

    private function getBusinessModuleSources(string $businessModule): array
    {
        if ($businessModule === BusinessModule::RETAIL) {
            return (array) config($this->panelSetting(key: 'sources_config').'.'.BusinessModule::RETAIL, []);
        }

        if (! in_array($businessModule, BusinessModule::values(), true) || ! businessModuleManager()->isAvailableInPanel($businessModule)) {
            return [];
        }

        return (array) config($businessModule.'.'.$this->panelSetting(key: 'module_sources_key'), []);
    }

    private function getAddonCommonSources(): array
    {
        $sources = [];

        foreach (Module::allEnabled() as $module) {
            if ($this->isAddonAvailable(moduleName: $module->getName(), moduleKey: $module->getLowerName())) {
                $sources = array_merge($sources, (array) config($module->getLowerName().'.'.$this->panelSetting(key: 'module_common_sources_key'), []));
            }
        }

        return $sources;
    }

    private function isAddonAvailable(string $moduleName, string $moduleKey): bool
    {
        if (in_array($moduleKey, BusinessModule::values(), true)) {
            return businessModuleManager()->isAvailableInPanel($moduleKey);
        }

        return addon_published_status(moduleName: $moduleName);
    }

    private function tagSourceOrigin(array $sources, string $businessModule): array
    {
        return array_map(
            fn (mixed $source): mixed => is_array($source) ? [...$source, 'business_module' => $businessModule] : $source,
            $sources,
        );
    }

    private function panelSetting(string $key): mixed
    {
        return self::PANELS[$this->panel][$key];
    }

    private function isResolvableSource(mixed $source): bool
    {
        return is_array($source)
            && is_string($source['service'] ?? null)
            && is_string($source['route'] ?? null)
            && is_string($source['label'] ?? null)
            && class_exists($source['service'])
            && Route::has($source['route']);
    }
}
