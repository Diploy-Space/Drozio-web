<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Traits\CacheManagerTrait;
use App\Utils\CategoryManager;
use App\Utils\ProductManager;
use Illuminate\Console\Command;

class WarmHomeCache extends Command
{
    use CacheManagerTrait;

    protected $signature = 'cache:warm-home {--fresh : Invalidate home caches before rebuilding}';

    protected $description = 'Pre-build the home page caches so no visitor pays the cold-cache cost';

    private Product $product;

    public function handle(): int
    {
        $this->product = new Product();

        if ($this->option('fresh')) {
            $this->invalidateHomeCache();
            $this->line('Home caches invalidated.');
        }

        $rows = [];
        foreach ($this->homeCacheBuilders() as $label => $builder) {
            $start = microtime(true);
            $builder();
            $rows[] = [$label, round((microtime(true) - $start) * 1000) . ' ms'];
        }

        $this->table(['Cache', 'Build time'], $rows);
        $this->info('Home cache warmed (' . count($rows) . ' builders).');

        return self::SUCCESS;
    }

    /**
     * @return array<string, callable>
     */
    private function homeCacheBuilders(): array
    {
        return [
            'business_settings' => fn() => $this->cacheBusinessSettingsTable(),
            'currency'          => fn() => $this->cacheCurrencyTable(),
            'brands'            => fn() => $this->cachePriorityWiseBrandList(),
            'home_categories'   => fn() => $this->cacheHomeCategoriesList(),
            'nav_menu'          => fn() => CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting(dataForm: 'home_page'),
            'header_menu'       => fn() => CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting(dataLimit: 11),
            'top_rated'         => fn() => $this->cacheTopRatedProductList(),
            'latest'            => fn() => $this->cacheHomePageLatestProductList(),
            'best_sell'         => fn() => $this->cacheBestSellProductList(),
            'featured'          => fn() => $this->cacheHomePageFeaturedProductList(dataLimit: 12),
            'new_arrival'       => fn() => $this->cacheHomePageNewArrivalProductList(dataLimit: 8),
            'deal_of_the_day'   => fn() => $this->cacheHomePageDealOfTheDay(),
            'recommended'       => fn() => $this->cacheHomePageRandomSingleProductItem(),
            'top_vendors'       => fn() => $this->cacheHomePageTopVendorsList(),
            'clearance_sale'    => fn() => $this->cacheHomePageClearanceSaleProducts(),
            'robots_meta'       => fn() => $this->cacheRobotsMetaContent(page: 'default'),
            'flash_deal'        => fn() => ProductManager::getPriorityWiseFlashDealsProductsQuery(userId: 0),
            'main_banner'       => fn() => $this->cacheBannerForTypeMainBanner(),
            'section_banner'    => fn() => $this->cacheBannerTable(bannerType: 'Main Section Banner'),
            'footer_banner'     => fn() => $this->cacheBannerTable(bannerType: 'Footer Banner', dataLimit: 10),
        ];
    }

    private function invalidateHomeCache(): void
    {
        cacheRemoveByType(type: 'products');
        cacheRemoveByType(type: 'banners');
        cacheRemoveByType(type: 'currencies');
    }
}
