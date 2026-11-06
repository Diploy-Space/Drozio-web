<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BusinessModule;
use App\Http\Controllers\BaseAdvancedSearchController;
use App\Services\New\AdvancedSearchService;

class AdvancedSearchController extends BaseAdvancedSearchController
{
    private const SEARCHER_TYPE = 'seller';

    private const ENABLED_SETTING_VALUE = 1;

    protected function panel(): string
    {
        return AdvancedSearchService::PANEL_VENDOR;
    }

    protected function searcherId(): ?int
    {
        return auth('seller')->id();
    }

    protected function searcherType(): string
    {
        return self::SEARCHER_TYPE;
    }

    protected function recentSearchRouteName(): string
    {
        return 'vendor.advanced-search-recent';
    }

    protected function resolveSource(array $source): ?array
    {
        $sellerId = $this->searcherId();

        if ($sellerId === null || ! isset($source['seller_filter'])) {
            return null;
        }

        if (! $this->isVisible(visibility: (array) ($source['visibility'] ?? []), businessModule: $source['business_module'])) {
            return null;
        }

        return [...$source, 'filters' => [...$source['filters'], $source['seller_filter'] => $sellerId]];
    }

    protected function canSeeMenu(array $menu): bool
    {
        return $this->isVisible(visibility: $menu['visibility'] ?? [], businessModule: $menu['owner_business_module']);
    }

    protected function canSeePage(array $page): bool
    {
        $businessModule = $page['owner_business_module'] ?? $this->businessModuleManager->activeKey();

        foreach ($this->advancedSearchService->getMenus(businessModule: $businessModule) as $menu) {
            if ($menu['uri'] === $page['uri'] && ! $this->canSeeMenu(menu: $menu)) {
                return false;
            }
        }

        return true;
    }

    protected function isAllowedUri(string $uri): bool
    {
        return str_starts_with($uri, $this->advancedSearchService->getUriPrefix());
    }

    private function isVisible(array $visibility, ?string $businessModule): bool
    {
        $isOwnedByModule = $businessModule !== null && $businessModule !== '' && $businessModule !== BusinessModule::COMMON;

        if (isset($visibility['addon']) && ! getCheckAddonPublishedStatus(moduleName: $visibility['addon'])) {
            return false;
        }

        if (isset($visibility['section']) && ! $this->hasSection(section: $visibility['section'], area: $visibility['area'] ?? ($isOwnedByModule ? $businessModule : null))) {
            return false;
        }

        if (isset($visibility['module_item'])) {
            $isItemVisible = $isOwnedByModule
                ? $this->businessModuleManager->itemVisibleFor(itemKey: $visibility['module_item'], businessModule: $businessModule)
                : $this->businessModuleManager->itemVisible(itemKey: $visibility['module_item']);

            if (! $isItemVisible) {
                return false;
            }
        }

        if (isset($visibility['helper']) && (! function_exists($visibility['helper']) || call_user_func($visibility['helper']) === null)) {
            return false;
        }

        foreach ((array) ($visibility['settings'] ?? []) as $setting) {
            if ((int) getWebConfig(name: $setting) !== self::ENABLED_SETTING_VALUE) {
                return false;
            }
        }

        $seller = auth('seller')->user();

        foreach ((array) ($visibility['seller_flags'] ?? []) as $flag) {
            if ((int) ($seller?->{$flag} ?? 0) !== self::ENABLED_SETTING_VALUE) {
                return false;
            }
        }

        return true;
    }

    private function hasSection(string $section, ?string $area): bool
    {
        if ($area === null) {
            return true;
        }

        $businessArea = $this->businessModuleManager->get($area) ?? ($this->businessModuleManager->commonAreas()[$area] ?? null);

        return $businessArea !== null && $businessArea->hasSection($section);
    }
}
