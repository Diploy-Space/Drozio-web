<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseAdvancedSearchController;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\AdvancedSearchService;
use App\Services\New\RecentSearchService;
use App\Services\New\ShopService;
use App\Utils\Helpers;

class AdvancedSearchController extends BaseAdvancedSearchController
{
    private const SEARCHER_TYPE = 'admin';

    private const SHOP_PRODUCT_SHOP_LIMIT = 3;

    private const SHOP_OWNER_IN_HOUSE = 'in_house';

    private const SHOP_OWNER_VENDOR = 'vendor';

    private ?array $matchedShops = null;

    public function __construct(
        RecentSearchService $recentSearchService,
        AdvancedSearchService $advancedSearchService,
        BusinessModuleManager $businessModuleManager,
        private readonly ShopService $shopService,
    ) {
        parent::__construct(
            recentSearchService: $recentSearchService,
            advancedSearchService: $advancedSearchService,
            businessModuleManager: $businessModuleManager,
        );
    }

    protected function panel(): string
    {
        return AdvancedSearchService::PANEL_ADMIN;
    }

    protected function searcherId(): ?int
    {
        return auth('admin')->id();
    }

    protected function searcherType(): string
    {
        return self::SEARCHER_TYPE;
    }

    protected function recentSearchRouteName(): string
    {
        return 'admin.advanced-search-recent';
    }

    protected function resolveSource(array $source): ?array
    {
        return Helpers::module_permission_check(...(array) $source['permission']) ? $source : null;
    }

    protected function canSeeMenu(array $menu): bool
    {
        return Helpers::module_permission_check(...$menu['permission']);
    }

    protected function isAllowedUri(string $uri): bool
    {
        if (! str_starts_with($uri, $this->advancedSearchService->getUriPrefix())) {
            return false;
        }

        foreach ($this->advancedSearchService->getRequiredPermissions(uri: $uri) ?? [] as $permissions) {
            if (! Helpers::module_permission_check(...$permissions)) {
                return false;
            }
        }

        return true;
    }

    protected function getAdditionalProductMatches(array $source, string $keyword): array
    {
        if (! isset($source['shop_owner_filters'])) {
            return [];
        }

        $this->matchedShops ??= $this->shopService->getList(searchValue: $keyword, dataLimit: self::SHOP_PRODUCT_SHOP_LIMIT)->all();
        $items = [];

        foreach ($this->matchedShops as $shop) {
            $remainingLimit = (int) $source['data_limit'] - count($items);
            $ownerFilters = $this->getShopOwnerFilters(source: $source, shop: $shop);

            if ($remainingLimit <= 0) {
                break;
            }

            if ($ownerFilters === null) {
                continue;
            }

            $shopSource = [...$source, 'data_limit' => $remainingLimit, 'filters' => [...$source['filters'], ...$ownerFilters]];
            $items = array_merge($items, $this->mapSourceRows(type: self::PRODUCT_SOURCE_TYPE, source: $shopSource, rows: $this->getSourceRows(source: $shopSource, keyword: null)));
        }

        return $items;
    }

    private function getShopOwnerFilters(array $source, object $shop): ?array
    {
        if ($shop->author_type === ShopService::AUTHOR_TYPE_ADMIN) {
            return $source['shop_owner_filters'][self::SHOP_OWNER_IN_HOUSE] ?? null;
        }

        if (! isset($source['shop_owner_filters'][self::SHOP_OWNER_VENDOR], $source['shop_vendor_filter']) || empty($shop->seller_id)) {
            return null;
        }

        return [...$source['shop_owner_filters'][self::SHOP_OWNER_VENDOR], $source['shop_vendor_filter'] => (int) $shop->seller_id];
    }
}
