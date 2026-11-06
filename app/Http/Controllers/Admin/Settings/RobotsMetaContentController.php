<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\BusinessModule;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Setting\Website\RobotsMetaUpdateRequest;
use App\Services\New\BusinessPageService;
use App\Services\New\BusinessSettingService;
use App\Services\New\RobotsMetaContentService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\RobotsMetaPage;

class RobotsMetaContentController extends BaseController
{
    private const LIST_ROUTE = 'admin.settings.seo-settings.robots-meta-content.index';

    private const PAGE_LIST_LIMIT = 20;

    private const AUCTION_MODULE = 'Auction';

    private const NO_INDEX = 'noindex';

    public function __construct(
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly BusinessPageService $businessPageService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $pageList = $this->pageCatalogue();

        return view('admin-views.seo-settings.robots-meta-content', [
            'pageList' => $pageList,
            'pageListData' => $this->robotsMetaContentService->getList(
                dataLimit: self::PAGE_LIST_LIMIT,
                filters: [RobotsMetaContentService::FILTER_EXCLUDED_PAGE_NAMES => $this->unlistedPageNames(pageList: $pageList)],
            ),
            'defaultPageData' => $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::DEFAULT_PAGE_NAME]),
        ]);
    }

    public function addPage(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->route(self::LIST_ROUTE);
        }

        $page = $this->pageCatalogue()[$request['page_name']] ?? [];

        if (! empty($page)) {
            $this->storePage(pageName: $request['page_name'], page: $page);
            ToastMagic::success(translate('Added successfully'));
        }

        return redirect()->route(self::LIST_ROUTE);
    }

    public function getPageDelete(Request $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->back();
        }

        $this->robotsMetaContentService->delete(id: $request['id']);
        ToastMagic::success(translate('Deleted successfully'));

        return redirect()->route(self::LIST_ROUTE);
    }

    public function getPageAddContentView(Request $request): View
    {
        return view('admin-views.seo-settings.robots-meta-content-view', [
            'pageData' => $this->robotsMetaContentService->findBy(params: ['page_name' => $request['page_name']]),
            'pageName' => $request['page_name'],
        ]);
    }

    public function getPageContentUpdate(RobotsMetaUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') == 'demo') {
            ToastMagic::error(translate('You cannot update this on demo mode'));

            return redirect()->route(self::LIST_ROUTE);
        }

        $stored = $this->robotsMetaContentService->findBy(params: ['page_name' => $request['page_name']]);
        $data = $this->metaContentFromRequest(request: $request);
        $image = $request->file('meta_image');

        $stored === null
            ? $this->robotsMetaContentService->create(data: $data, image: $image)
            : $this->robotsMetaContentService->update(id: $stored->id, data: $data, image: $image);

        ToastMagic::success(translate('Updated successfully'));

        return redirect()->route(self::LIST_ROUTE);
    }

    /** @param  array{title: string, route: string}  $page */
    private function storePage(string $pageName, array $page): void
    {
        $stored = $this->robotsMetaContentService->findBy(params: ['page_name' => $pageName]);

        if ($stored === null) {
            $this->robotsMetaContentService->create(data: [
                'page_title' => $page['title'],
                'page_name' => $pageName,
                'page_url' => $page['route'],
                'canonicals_url' => $page['route'],
            ]);

            return;
        }

        $this->robotsMetaContentService->update(id: $stored->id, data: [
            'page_title' => $page['title'],
            'page_name' => $pageName,
            'page_url' => $page['route'],
        ]);
    }

    /** @return array<string, mixed> */
    private function metaContentFromRequest(RobotsMetaUpdateRequest $request): array
    {
        return [
            'page_title' => $this->pageCatalogue()[$request['page_name']]['title'] ?? '',
            'page_name' => $request['page_name'],
            'meta_title' => $request['meta_title'],
            'meta_description' => $request['meta_description'],
            'canonicals_url' => $request['canonicals_url'],
            'index' => $request['meta_index'] != self::NO_INDEX ? '' : self::NO_INDEX,
            'no_follow' => $request['meta_no_follow'] ? 'nofollow' : '',
            'no_image_index' => $request['meta_no_image_index'] ? 'noimageindex' : '',
            'no_archive' => $request['meta_no_archive'] ? 'noarchive' : '',
            'no_snippet' => $request['meta_no_snippet'] ?? 0,
            'max_snippet' => $request['meta_max_snippet'] ?? 0,
            'max_snippet_value' => $request['meta_max_snippet_value'] ?? 0,
            'max_video_preview' => $request['meta_max_video_preview'] ?? 0,
            'max_video_preview_value' => $request['meta_max_video_preview_value'] ?? 0,
            'max_image_preview' => $request['meta_max_image_preview'] ?? 0,
            'max_image_preview_value' => $request['meta_max_image_preview_value'] ?? 0,
        ];
    }

    /** @return array<string, array{title: string, route: string}> */
    private function pageCatalogue(): array
    {
        $pages = [
            'brands' => ['title' => 'all_Brands', 'route' => route('brands')],
            'categories' => ['title' => 'all_Categories', 'route' => route('categories')],
            'vendors' => ['title' => 'vendor_List', 'route' => route('vendors')],
            'contacts' => ['title' => 'contact_us', 'route' => route('contacts')],
            'helpTopic' => ['title' => 'FAQ', 'route' => route('help-topics')],
            'track-order' => ['title' => 'track_order', 'route' => route('track-order.index')],
            'featured-products' => ['title' => 'Featured_Products', 'route' => route('featured-products')],
            'latest-products' => ['title' => 'Latest_Products', 'route' => route('latest-products')],
            'best-selling-products' => ['title' => 'Best_Selling_Products', 'route' => route('best-selling-products')],
            'top-rated-products' => ['title' => 'Top_Rated_Products', 'route' => route('top-rated-products')],
            'most-favorite-products' => ['title' => 'Most_Favorite_Products', 'route' => route('most-favorite-products')],
            'discounted-products' => ['title' => 'Discounted_Products', 'route' => route('discounted-products')],
        ];

        foreach ($this->businessPageService->getList(dataLimit: 'all') as $businessPage) {
            if (blank($businessPage['slug'])) {
                continue;
            }

            $pages[$businessPage['slug']] = [
                'title' => $businessPage['title'],
                'route' => route('business-page.view', ['slug' => $businessPage['slug']]),
            ];
        }

        $pages += $this->auctionPageCatalogue();
        $pages += $this->wholesalePageCatalogue();
        ksort($pages);

        return $pages;
    }

    /** @return array<string, array{title: string, route: string}> */
    private function auctionPageCatalogue(): array
    {
        $isAuctionActive = getCheckAddonPublishedStatus(moduleName: self::AUCTION_MODULE)
            && $this->businessSettingService->getValueByType(type: 'auction_feature_status');

        if (! $isAuctionActive) {
            return [];
        }

        return [
            'auction_brands_products' => ['title' => 'Auction_Brands_Products', 'route' => route('auction.brand-products')],
            'auction_ending_soon' => ['title' => 'Auction_Ending_Soon_Products', 'route' => route('auction.ending-soon-products')],
            'auction_trending_products' => ['title' => 'Auction_Trending_Products', 'route' => route('auction.trending-products')],
            'auction_upcoming_products' => ['title' => 'Auction_Upcoming_Products', 'route' => route('auction.upcoming-products')],
        ];
    }

    /** @return array<string, array{title: string, route: string}> */
    private function wholesalePageCatalogue(): array
    {
        if (! businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE)) {
            return [];
        }

        return [
            RobotsMetaPage::HOME => ['title' => 'Wholesale Home', 'route' => route('wholesale.products.index')],
            RobotsMetaPage::MANUFACTURERS => ['title' => 'Wholesale Manufacturers', 'route' => route('wholesale.manufacturers.index')],
            RobotsMetaPage::BRAND_LIST => ['title' => 'Wholesale Brand List', 'route' => route('wholesale.products.brands')],
            RobotsMetaPage::MANUFACTURER_LIST => ['title' => 'Wholesale Manufacturer List', 'route' => route('wholesale.manufacturers.list')],
            RobotsMetaPage::PRODUCTS => ['title' => 'Wholesale Products', 'route' => route('wholesale.products.list')],
            RobotsMetaPage::BEST_PRICES => ['title' => 'Best Wholesale Prices Products', 'route' => route('wholesale.products.best-wholesale-prices')],
            RobotsMetaPage::CATEGORY_PRODUCTS => ['title' => 'Wholesale Categories Wise Products', 'route' => $this->routeBaseUrl(routeName: 'wholesale.products.category-products')],
            RobotsMetaPage::BRAND_PRODUCTS => ['title' => 'Wholesale Brands Wise Products', 'route' => $this->routeBaseUrl(routeName: 'wholesale.products.brand-products')],
            RobotsMetaPage::MOST_DEMANDED => ['title' => 'Wholesale Most Demanded Items', 'route' => route('wholesale.products.most-demanded')],
            RobotsMetaPage::FEATURED_PRODUCTS => ['title' => 'Wholesale Featured Products', 'route' => route('wholesale.products.featured-products')],
            RobotsMetaPage::BEST_SELLING_PRODUCTS => ['title' => 'Wholesale Best Sellings', 'route' => route('wholesale.products.best-selling-products')],
            RobotsMetaPage::LATEST_PRODUCTS => ['title' => 'Wholesale Latest Products', 'route' => route('wholesale.products.latest-products')],
            RobotsMetaPage::OFFERS => ['title' => 'Wholesale Offers', 'route' => route('wholesale.offers.index')],
        ];
    }

    private function routeBaseUrl(string $routeName): string
    {
        return url(Str::before(Route::getRoutes()->getByName($routeName)->uri(), '/{'));
    }

    /**
     * @param  array<string, array{title: string, route: string}>  $pageList
     * @return array<int, string>
     */
    private function unlistedPageNames(array $pageList): array
    {
        $storedPageNames = $this->robotsMetaContentService->getList(dataLimit: 'all')->pluck('page_name')->toArray();

        return array_merge(
            [RobotsMetaContentService::DEFAULT_PAGE_NAME],
            array_diff($storedPageNames, array_keys($pageList)),
        );
    }
}
