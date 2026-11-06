<?php

namespace App\Jobs;

use App\Enums\BusinessModule;
use App\Services\New\BrandService;
use App\Services\New\BusinessPageService;
use App\Services\New\CategoryService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use App\Services\New\SiteMapService;
use Generator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Modules\Blog\app\Services\New\BlogService;
use Modules\Wholesale\app\Services\BrandService as WholesaleBrandService;
use Modules\Wholesale\app\Services\CategoryService as WholesaleCategoryService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\ShopService as WholesaleShopService;
use Throwable;

class GenerateSiteMapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUEUE = 'exports';

    public int $tries = 1;

    public int $timeout = 1800;

    private const WHOLESALE_MODULE = 'Wholesale';

    private const BLOG_MODULE = 'Blog';

    /** @var array<int, string> */
    private const STATIC_PAGE_ROUTES = [
        'home',
        'products',
        'categories',
        'brands',
        'vendors',
        'discounted-products',
        'contacts',
        'help-topics',
        'track-order.index',
    ];

    /** @var array<int, string> */
    private const WHOLESALE_STATIC_PAGE_ROUTES = [
        'wholesale.products.index',
        'wholesale.manufacturers.index',
        'wholesale.manufacturers.list',
        'wholesale.products.brands',
        'wholesale.products.list',
        'wholesale.products.best-wholesale-prices',
        'wholesale.products.most-demanded',
        'wholesale.products.featured-products',
        'wholesale.products.best-selling-products',
        'wholesale.products.latest-products',
    ];

    public function __construct(private readonly bool $publish = false)
    {
        $this->onQueue(self::QUEUE);
    }

    public function handle(
        SiteMapService $siteMapService,
        ProductRetailService $productService,
        BrandService $brandService,
        CategoryService $categoryService,
        ShopService $shopService,
        BusinessPageService $businessPageService,
    ): string {
        return $siteMapService->generate(
            urls: $this->urls(
                productService: $productService,
                brandService: $brandService,
                categoryService: $categoryService,
                shopService: $shopService,
                businessPageService: $businessPageService,
            ),
            publish: $this->publish,
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Sitemap generation failed', ['message' => $exception->getMessage()]);
    }

    /** @return Generator<int, array{loc: string, lastmod?: mixed}> */
    private function urls(
        ProductRetailService $productService,
        BrandService $brandService,
        CategoryService $categoryService,
        ShopService $shopService,
        BusinessPageService $businessPageService,
    ): Generator {
        yield from $this->staticUrls();
        yield from $this->slugUrls(rows: $productService->getIndexableSlugs(), routeName: 'product');
        yield from $this->slugUrls(rows: $brandService->getIndexableSlugs(), routeName: 'brand-products');
        yield from $this->slugUrls(rows: $categoryService->getIndexableSlugs(), routeName: 'category-products');
        yield from $this->slugUrls(rows: $shopService->getIndexableSlugs(), routeName: 'vendor-shop');
        yield from $this->slugUrls(rows: $businessPageService->getIndexableSlugs(), routeName: 'business-page.view');
        yield from $this->blogUrls();
        yield from $this->wholesaleUrls();
    }

    /** @return Generator<int, array{loc: string}> */
    private function staticUrls(): Generator
    {
        foreach (self::STATIC_PAGE_ROUTES as $routeName) {
            yield ['loc' => route($routeName)];
        }

        yield ['loc' => route('vendor-shop', ['slug' => getInHouseShopConfig(key: 'slug')])];
    }

    /** @return Generator<int, array{loc: string, lastmod: mixed}> */
    private function slugUrls(LazyCollection $rows, string $routeName): Generator
    {
        foreach ($rows as $row) {
            yield [
                'loc' => route($routeName, ['slug' => $row->slug]),
                'lastmod' => $row->updated_at,
            ];
        }
    }

    /** @return Generator<int, array{loc: string, lastmod: mixed}> */
    private function blogUrls(): Generator
    {
        $blogService = moduleService(moduleName: self::BLOG_MODULE, serviceClass: BlogService::class);

        if ($blogService === null) {
            return;
        }

        yield from $this->slugUrls(rows: $blogService->getIndexableSlugs(), routeName: 'frontend.blog.details');
    }

    /** @return Generator<int, array{loc: string, lastmod?: mixed}> */
    private function wholesaleUrls(): Generator
    {
        if (! businessModuleManager()->isAvailable(BusinessModule::WHOLESALE)) {
            return;
        }

        foreach (self::WHOLESALE_STATIC_PAGE_ROUTES as $routeName) {
            yield ['loc' => route($routeName)];
        }

        $productService = moduleService(moduleName: self::WHOLESALE_MODULE, serviceClass: ProductWholesaleService::class);
        $categoryService = moduleService(moduleName: self::WHOLESALE_MODULE, serviceClass: WholesaleCategoryService::class);
        $brandService = moduleService(moduleName: self::WHOLESALE_MODULE, serviceClass: WholesaleBrandService::class);
        $shopService = moduleService(moduleName: self::WHOLESALE_MODULE, serviceClass: WholesaleShopService::class);

        if ($productService !== null) {
            yield from $this->slugUrls(rows: $productService->getIndexableSlugs(), routeName: 'wholesale.products.details');
        }

        if ($categoryService !== null) {
            yield from $this->slugUrls(rows: $categoryService->getIndexableSlugs(), routeName: 'wholesale.products.category-products');
        }

        if ($brandService !== null) {
            yield from $this->slugUrls(rows: $brandService->getIndexableSlugs(), routeName: 'wholesale.products.brand-products');
        }

        if ($shopService !== null) {
            yield from $this->slugUrls(rows: $shopService->getIndexableSlugs(), routeName: 'wholesale.shop');
        }
    }
}
