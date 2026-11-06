<?php

namespace App\Http\Controllers;

use App\Enums\BusinessModule;
use App\Http\Requests\Shared\AdvancedSearch\RecentRequest;
use App\Http\Requests\Shared\AdvancedSearch\SearchRequest;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\AdvancedSearchService;
use App\Services\New\RecentSearchService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class BaseAdvancedSearchController extends Controller
{
    protected const PRODUCT_SOURCE_TYPE = 'products';

    private const RECENT_SEARCH_LIMIT = 10;

    private const MODEL_RESULT_PRIORITY = 3;

    private const MENU_RESULT_PRIORITY = 1;

    private const PAGE_RESULT_PRIORITY = 2;

    private const PAGE_RESULT_ITEMS_COUNT = 1;

    private const PLACEHOLDER_IMAGE_TYPE = 'backend-basic';

    private const UNTITLED_RESULT = 'N/A';

    private const MENU_TITLE_SEPARATOR = ' › ';

    private const RESULT_VIEW = 'layouts.back-end.partials.advanced-search._result';

    protected readonly AdvancedSearchService $advancedSearchService;

    public function __construct(
        protected readonly RecentSearchService $recentSearchService,
        AdvancedSearchService $advancedSearchService,
        protected readonly BusinessModuleManager $businessModuleManager,
    ) {
        $this->advancedSearchService = $advancedSearchService->forPanel(panel: $this->panel());

        $this->middleware(function (Request $request, Closure $next): mixed {
            abort_unless($this->advancedSearchService->isAvailable(), 404);

            return $next($request);
        });
    }

    abstract protected function panel(): string;

    abstract protected function searcherId(): ?int;

    abstract protected function searcherType(): string;

    abstract protected function recentSearchRouteName(): string;

    abstract protected function resolveSource(array $source): ?array;

    abstract protected function canSeeMenu(array $menu): bool;

    abstract protected function isAllowedUri(string $uri): bool;

    public function getSearch(SearchRequest $request): JsonResponse
    {
        $keyword = $request->searchKeyword();
        $businessModule = $request->resolvedBusinessModule(manager: $this->businessModuleManager);

        if ($keyword === '') {
            return $this->getRecentSearchResponse(businessModule: $businessModule);
        }

        $locale = getDefaultLanguage();
        $recordKeyword = $request->recordSearchKeyword(manager: $this->businessModuleManager);
        $activeBusinessModules = $this->getActiveBusinessModules();
        $isBusinessModuleActive = in_array($businessModule, $activeBusinessModules, true);
        $searchBusinessModules = $request->isBusinessModuleNamed(manager: $this->businessModuleManager)
            ? array_values(array_intersect([$businessModule], $activeBusinessModules))
            : array_values(array_unique([...($isBusinessModuleActive ? [$businessModule] : []), ...$activeBusinessModules]));
        $navigationBusinessModules = $searchBusinessModules === [] ? [$businessModule] : $searchBusinessModules;

        $result = $this->advancedSearchService->rank(
            items: $this->labelOwnerBusinessModule(items: array_merge(
                $this->getModelMatches(businessModule: $businessModule, keyword: $recordKeyword, isBusinessModuleActive: $isBusinessModuleActive),
                $this->getProductMatches(businessModules: $searchBusinessModules, keyword: $recordKeyword),
                $this->orderCurrentModuleFirst(
                    items: $this->filterActiveOwners(
                        items: array_merge(
                            $this->getMenuMatches(businessModules: $navigationBusinessModules, keyword: $keyword, locale: $locale),
                            $this->getPageMatches(businessModules: $navigationBusinessModules, keyword: $keyword, locale: $locale),
                        ),
                        activeBusinessModules: $activeBusinessModules,
                    ),
                    businessModule: $businessModule,
                ),
            )),
            keyword: $keyword,
        );

        return $this->renderResult(keyword: $keyword, result: $result, businessModule: $businessModule, isRecent: false);
    }

    public function recentSearch(RecentRequest $request): RedirectResponse
    {
        $searcherId = $this->searcherId();
        $routeUri = ltrim((string) $request->validated('routeUri'), '/');
        $businessModule = (string) $request->validated('business_module');
        $title = (string) $request->validated('routeName');
        $type = (string) $request->validated('type');
        $keyword = $request->searchKeyword();
        $targetUrl = $keyword === '' ? url($routeUri) : $this->appendKeyword(url: url($routeUri), keyword: urlencode($keyword));

        $this->recentSearchService->updateOrInsert(
            params: [
                'user_id' => $searcherId,
                'user_type' => $this->searcherType(),
                'route_uri' => $routeUri,
            ],
            data: [
                'business_module' => $businessModule,
                'title' => $title,
                'keyword' => $keyword === '' ? null : $keyword,
                'response' => json_encode([
                    'page_title' => $title,
                    'page_title_value' => $title,
                    'uri' => $routeUri,
                    'type' => $type,
                    'priority' => $this->getResultPriority(type: $type),
                    'image' => $request->validated('image'),
                    'business_module' => $businessModule,
                ]),
                'route_full_url' => $targetUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $owner = ['user_id' => $searcherId, 'user_type' => $this->searcherType(), 'business_module' => $businessModule];
        $overflowCount = $this->recentSearchService->count(filters: $owner) - self::RECENT_SEARCH_LIMIT;

        if ($overflowCount > 0) {
            $oldestSearches = $this->recentSearchService->getList(
                dataLimit: $overflowCount,
                filters: $owner,
                sortBy: RecentSearchService::SORT_OLDEST,
            );

            foreach ($oldestSearches as $oldestSearch) {
                $this->recentSearchService->delete(id: $oldestSearch->id);
            }
        }

        return redirect($targetUrl);
    }

    protected function canSeePage(array $page): bool
    {
        return true;
    }

    protected function getAdditionalProductMatches(array $source, string $keyword): array
    {
        return [];
    }

    protected function getSourceRows(array $source, ?string $keyword): iterable
    {
        return app($source['service'])->getList(...[
            'searchValue' => $keyword,
            'dataLimit' => $source['data_limit'],
            'relations' => $source['relations'] ?? [],
            'filters' => $source['filters'],
            ...($source['arguments'] ?? []),
        ]);
    }

    protected function mapSourceRows(string $type, array $source, iterable $rows, string $keyword = ''): array
    {
        $items = [];

        foreach ($rows as $row) {
            $title = trim(implode(' ', array_map(fn (string $attribute): string => (string) data_get($row, $attribute), $source['title'])));

            if ($title === '') {
                $title = (string) collect($source['title_fallback'] ?? [])
                    ->map(fn (string $attribute): string => trim((string) data_get($row, $attribute)))
                    ->first(fn (string $value): bool => $value !== '', '');
            }

            $parameters = [];

            foreach ($source['route_parameters'] as $parameter => $attribute) {
                $value = data_get($row, $attribute);
                $parameters[$parameter] = $source['route_parameter_values'][$parameter][$value] ?? $value;
            }

            if (isset($source['keyword_parameter']) && $keyword !== '') {
                $parameters[$source['keyword_parameter']] = $keyword;
            }

            $uri = ltrim(route($source['route'], $parameters, false), '/');

            if (! $this->isAllowedUri(uri: $uri)) {
                continue;
            }

            $items[] = [
                'page_title' => $title,
                'page_title_value' => $title,
                'key' => base64_encode('dbsearch'.$uri),
                'uri' => $uri,
                'type' => $type,
                'image' => $this->getRowImage(source: $source, row: $row),
                'priority' => self::MODEL_RESULT_PRIORITY,
                'business_module' => $source['business_module'],
            ];
        }

        return $items;
    }

    protected function getActiveBusinessModules(): array
    {
        return array_keys($this->businessModuleManager->availableInPanel());
    }

    private function getRecentSearchResponse(string $businessModule): JsonResponse
    {
        $recentSearches = $this->recentSearchService->getList(
            dataLimit: self::RECENT_SEARCH_LIMIT,
            filters: [
                'user_id' => $this->searcherId(),
                'user_type' => $this->searcherType(),
                'business_module' => [...$this->getActiveBusinessModules(), BusinessModule::COMMON],
            ],
            sortBy: RecentSearchService::SORT_NEWEST,
        );

        $items = collect($this->recentSearchService->groupResultsByType($recentSearches))->flatten(depth: 1)->all();
        $items = array_filter($items, fn (array $item): bool => $this->isVisibleRecentItem(item: $item, fallbackBusinessModule: $businessModule));
        $items = array_map(fn (array $item): array => $this->labelRecentItem(item: $item), $items);
        $result = collect($items)->unique('uri')->groupBy('type')->toArray();

        return $this->renderResult(keyword: '', result: $result, businessModule: $businessModule, isRecent: count($result) > 0);
    }

    private function isVisibleRecentItem(array $item, string $fallbackBusinessModule): bool
    {
        $type = (string) ($item['type'] ?? '');
        $uri = (string) ($item['uri'] ?? '');
        $sources = $this->advancedSearchService->getSources(businessModule: (string) ($item['business_module'] ?? $fallbackBusinessModule));

        if (isset($sources[$type])) {
            if ($this->resolveSource(source: $sources[$type]) === null) {
                return false;
            }
        } elseif (! in_array($type, [AdvancedSearchService::TYPE_MENU, AdvancedSearchService::TYPE_PAGE], true)) {
            return false;
        }

        return $this->advancedSearchService->isReachable(uri: $uri) && $this->isAllowedUri(uri: $uri);
    }

    private function renderResult(string $keyword, array $result, string $businessModule, bool $isRecent): JsonResponse
    {
        $groupLabels = $this->getGroupLabels(businessModule: $businessModule);
        $result = array_intersect_key($result, $groupLabels);

        return response()->json([
            'keyword' => $keyword,
            'result' => $result,
            'htmlView' => view(self::RESULT_VIEW, [
                'result' => $this->prepareResultForDisplay(result: $result, groupLabels: $groupLabels, keyword: $keyword),
                'keyword' => $keyword,
                'recent' => $isRecent,
                'recentSearchUrl' => route($this->recentSearchRouteName()),
            ])->render(),
        ]);
    }

    private function getGroupLabels(string $businessModule): array
    {
        $labels = [
            AdvancedSearchService::TYPE_MENU => translate('Menu'),
            AdvancedSearchService::TYPE_PAGE => translate('Page'),
        ];

        foreach ($this->getActiveBusinessModules() === [] ? [$businessModule] : array_unique([$businessModule, ...$this->getActiveBusinessModules()]) as $module) {
            foreach ($this->advancedSearchService->getSources(businessModule: $module) as $type => $source) {
                $labels[$type] ??= translate($source['label']);
            }
        }

        return $labels;
    }

    private function prepareResultForDisplay(array $result, array $groupLabels, string $keyword): array
    {
        $displayResult = [];

        foreach ($result as $groupKey => $items) {
            $displayItems = [];

            foreach ($items as $key => $item) {
                $displayItems[] = $this->prepareItemForDisplay(item: $item, keyword: $keyword, uniqueId: 'item_'.$groupKey.'_'.$key);
            }

            $displayResult[] = ['label' => $groupLabels[$groupKey], 'items' => $displayItems];
        }

        return $displayResult;
    }

    private function prepareItemForDisplay(array $item, string $keyword, string $uniqueId): array
    {
        $title = ucfirst(str_replace('_', ' ', str_replace("\\'", "'", (string) ($item['page_title_value'] ?? self::UNTITLED_RESULT))));
        $uri = (string) ($item['uri'] ?? '');

        return [
            ...$item,
            'route_name' => (string) ($item['page_title_value'] ?? ''),
            'route_uri' => $uri,
            'search_keyword' => $keyword === '' ? (string) ($item['search_keyword'] ?? '') : $keyword,
            'business_module' => (string) ($item['business_module'] ?? BusinessModule::COMMON),
            'type' => (string) ($item['type'] ?? ''),
            'image' => $item['image'] ?? null,
            'unique_id' => $uniqueId,
            'highlighted_title' => $this->highlightMatches(text: $title, keyword: $keyword),
            'highlighted_uri' => $this->highlightMatches(text: $uri, keyword: $keyword),
        ];
    }

    private function highlightMatches(string $text, string $keyword): string
    {
        if ($keyword === '') {
            return e($text);
        }

        $segments = preg_split('/('.preg_quote($keyword, '/').')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            return e($text);
        }

        $highlighted = '';

        foreach ($segments as $index => $segment) {
            $highlighted .= $index % 2 === 1 ? '<mark>'.e($segment).'</mark>' : e($segment);
        }

        return $highlighted;
    }

    private function getModelMatches(string $businessModule, string $keyword, bool $isBusinessModuleActive): array
    {
        $items = [];

        foreach ($this->advancedSearchService->getSources(businessModule: $businessModule) as $type => $source) {
            $isModuleSource = $source['business_module'] !== BusinessModule::COMMON;
            $resolvedSource = $type === self::PRODUCT_SOURCE_TYPE || ($isModuleSource && ! $isBusinessModuleActive) ? null : $this->resolveSource(source: $source);

            if ($resolvedSource === null) {
                continue;
            }

            $items = array_merge($items, $this->mapSourceRows(
                type: (string) $type,
                source: $resolvedSource,
                rows: $this->getSourceRows(source: $resolvedSource, keyword: $keyword),
                keyword: $keyword,
            ));
        }

        return $items;
    }

    private function getProductMatches(array $businessModules, string $keyword): array
    {
        $items = [];

        foreach ($businessModules as $businessModule) {
            $source = $this->advancedSearchService->getSources(businessModule: $businessModule)[self::PRODUCT_SOURCE_TYPE] ?? null;
            $resolvedSource = $source === null ? null : $this->resolveSource(source: $source);

            if ($resolvedSource === null) {
                continue;
            }

            $moduleItems = array_merge(
                $this->mapSourceRows(type: self::PRODUCT_SOURCE_TYPE, source: $resolvedSource, rows: $this->getSourceRows(source: $resolvedSource, keyword: $keyword), keyword: $keyword),
                $this->getAdditionalProductMatches(source: $resolvedSource, keyword: $keyword),
            );

            $items = array_merge($items, array_map(fn (array $item): array => [...$item, 'owner_business_module' => $businessModule], $moduleItems));
        }

        return $items;
    }

    private function getRowImage(array $source, object $row): string
    {
        if ($source['image'] === null) {
            return getStorageImages(path: '', type: self::PLACEHOLDER_IMAGE_TYPE);
        }

        return getStorageImages(path: data_get($row, $source['image']), type: $source['image_type']);
    }

    private function getMenuMatches(array $businessModules, string $keyword, string $locale): array
    {
        $items = [];
        $isMultiModuleSearch = count($businessModules) > 1;

        foreach ($businessModules as $businessModule) {
            $menus = $this->advancedSearchService->getMenuMatches(
                businessModule: $businessModule,
                keyword: $keyword,
                locale: $locale,
                excludeOtherModulePages: $isMultiModuleSearch,
            );

            foreach ($menus as $menu) {
                if (! $this->canSeeMenu(menu: $menu) || ! $this->isAllowedUri(uri: $menu['uri'])) {
                    continue;
                }

                $items[] = [
                    'page_title' => trim(($menu['parent_title'] ?? '').' '.$menu['title']),
                    'page_title_value' => $this->getMenuDisplayTitle(menu: $menu),
                    'key' => base64_encode('dbsearch'.$menu['uri']),
                    'uri' => $menu['uri'],
                    'type' => AdvancedSearchService::TYPE_MENU,
                    'priority' => self::MENU_RESULT_PRIORITY,
                    'keywords' => implode(', ', $menu['keywords']),
                    'business_module' => $menu['business_module'],
                    'owner_business_module' => $menu['owner_business_module'],
                ];
            }
        }

        return $items;
    }

    private function getMenuDisplayTitle(array $menu): string
    {
        $title = translate($menu['title']);

        return $menu['parent_title'] === null ? $title : translate($menu['parent_title']).self::MENU_TITLE_SEPARATOR.$title;
    }

    private function getPageMatches(array $businessModules, string $keyword, string $locale): array
    {
        $items = [];

        foreach ($businessModules as $businessModule) {
            foreach ($this->advancedSearchService->getPageMatches(businessModule: $businessModule, keyword: $keyword, locale: $locale) as $page) {
                if (! $this->isAllowedUri(uri: $page['uri']) || ! $this->canSeePage(page: $page)) {
                    continue;
                }

                $items[] = [
                    'page_title' => $page['page_title'],
                    'page_title_value' => $page['page_title'],
                    'key' => $page['key'],
                    'uri' => $page['uri'],
                    'uri_count' => $page['uri_count'],
                    'method' => $page['method'],
                    'keywords' => mb_strtolower($keyword),
                    'productNames' => [],
                    'items_count' => self::PAGE_RESULT_ITEMS_COUNT,
                    'priority' => self::PAGE_RESULT_PRIORITY,
                    'type' => AdvancedSearchService::TYPE_PAGE,
                    'business_module' => $page['business_module'],
                    'owner_business_module' => $page['owner_business_module'],
                ];
            }
        }

        return $items;
    }

    private function labelRecentItem(array $item): array
    {
        $owner = (string) ($item['business_module'] ?? BusinessModule::COMMON);
        $label = $owner === BusinessModule::COMMON ? null : $this->businessModuleManager->get($owner)?->label;
        $title = (string) ($item['page_title_value'] ?? '');
        $isLabelledType = in_array($item['type'] ?? null, [AdvancedSearchService::TYPE_MENU, AdvancedSearchService::TYPE_PAGE, self::PRODUCT_SOURCE_TYPE], true);

        if ($label === null || ! $isLabelledType || str_starts_with($title, $label.self::MENU_TITLE_SEPARATOR)) {
            return $item;
        }

        return [...$item, 'page_title_value' => $label.self::MENU_TITLE_SEPARATOR.$title];
    }

    private function filterActiveOwners(array $items, array $activeBusinessModules): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => $item['owner_business_module'] === null || in_array($item['owner_business_module'], $activeBusinessModules, true),
        ));
    }

    private function orderCurrentModuleFirst(array $items, string $businessModule): array
    {
        usort($items, fn (array $first, array $second): int => ($second['owner_business_module'] === $businessModule) <=> ($first['owner_business_module'] === $businessModule));

        return $items;
    }

    private function labelOwnerBusinessModule(array $items): array
    {
        return array_map(function (array $item): array {
            $owner = $item['owner_business_module'] ?? null;

            if ($owner === null) {
                return $item;
            }

            $item['page_title_value'] = $this->businessModuleManager->get($owner)?->label.self::MENU_TITLE_SEPARATOR.$item['page_title_value'];
            $item['business_module'] = $owner;

            return $item;
        }, $items);
    }

    private function getResultPriority(string $type): int
    {
        return match ($type) {
            AdvancedSearchService::TYPE_MENU => self::MENU_RESULT_PRIORITY,
            AdvancedSearchService::TYPE_PAGE => self::PAGE_RESULT_PRIORITY,
            default => self::MODEL_RESULT_PRIORITY,
        };
    }

    private function appendKeyword(string $url, string $keyword): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'keyword='.$keyword;
    }
}
