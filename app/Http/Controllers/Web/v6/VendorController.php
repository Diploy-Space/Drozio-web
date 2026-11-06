<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Vendor\ListRequest;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\RobotsMetaContentService;
use App\Services\New\ShopService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class VendorController extends Controller
{
    private const BUSINESS_MODE_SETTING = 'business_mode';

    private const BUSINESS_MODE_SINGLE = 'single';

    private const PAGE_SIZE = 12;

    private const IN_HOUSE_ABILITY_TYPE = 'inhouse';

    /** @var array<int, string> */
    private const IN_HOUSE_PRODUCT_FILTERS = [
        'brand_id',
        'category_id',
        'sub_category_id',
        'sub_sub_category_id',
        'product_type',
        'publishing_house_id',
        'author_id',
    ];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly ProductRetailService $productService,
        private readonly ReviewService $reviewService,
        private readonly OrderService $orderService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
    ) {}

    public function index(ListRequest $request): View|RedirectResponse
    {
        $robotsMetaContentData = $this->robotsMetaContentService->findByPageNameOrDefault(
            pageName: RobotsMetaContentService::PAGE_NAME_VENDORS,
        );

        if (getWebConfig(name: self::BUSINESS_MODE_SETTING) === self::BUSINESS_MODE_SINGLE) {
            ToastMagic::warning(translate('Access denied').' !!');

            return back();
        }

        $vendorsList = $this->shopService->getVendorListing(shopName: $request['shop_name']);
        $vendorsList = $this->shopService->prependInHouseShop($vendorsList, $this->getInHouseShop($request));
        $vendorsList = $this->shopService->applyVendorOrdering($vendorsList, $request['order_by']);

        return view(themeViewName(key: 'all_stores_page'), [
            'vendorsList' => $vendorsList->paginate(self::PAGE_SIZE)->appends($request->all()),
            'order_by' => $request['order_by'],
            'robotsMetaContentData' => $robotsMetaContentData,
            'activeBrands' => $this->getActiveBrands(),
            'categories' => $this->getCategoryTree(),
        ]);
    }

    private function getCategoryTree(): Collection
    {
        return $this->categoryService->getList(
            dataLimit: 'all',
            sortBy: CategoryService::SORT_LIST_PRIORITY,
            withProductCounts: true,
        );
    }

    private function getActiveBrands(): Collection
    {
        return $this->brandService->getList(
            dataLimit: 'all',
            withCount: ['brandProducts'],
            filters: ['status' => 1],
            sortBy: BrandService::SORT_PRIORITY,
        );
    }

    private function getInHouseShop(ListRequest $request): ?Model
    {
        $productFilters = $this->inHouseProductFilters($request);
        $products = $this->productService->getList(
            dataLimit: 'all',
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'added_by_user' => ProductRetailService::ADDED_BY_ADMIN,
                ...$productFilters,
            ],
        );

        if ($products->isEmpty() && $productFilters !== []) {
            return null;
        }

        $shopName = $request['shop_name'];

        if ($shopName && ! str_contains(strtolower(getInHouseShopConfig(key: 'name')), strtolower($shopName))) {
            return null;
        }

        $reviews = $this->reviewService->getList(
            dataLimit: 'all',
            filters: ['status' => 1, 'product_id' => $products->pluck('id')->all()],
        );
        $reviewCount = $reviews->count();

        $shop = getInHouseShopConfig();
        $shop->products_count = $products->count();
        $shop->review_count = $reviewCount;
        $shop->average_rating = $reviews->avg('rating') ?? 0;
        $shop->positive_review = $reviewCount === 0
            ? 0
            : ($reviews->where('rating', '>=', 4)->count() * 100) / $reviewCount;
        $shop->orders_count = $this->orderService->count(filters: ['seller_is' => ProductRetailService::ADDED_BY_ADMIN]);
        $shop->is_vacation_mode_now = checkVendorAbility(type: self::IN_HOUSE_ABILITY_TYPE, status: 'vacation_status');

        return $shop;
    }

    /** @return array<string, mixed> */
    private function inHouseProductFilters(ListRequest $request): array
    {
        $filters = [];

        foreach (self::IN_HOUSE_PRODUCT_FILTERS as $key) {
            if (! empty($request[$key])) {
                $filters[$key] = $request[$key];
            }
        }

        return $filters;
    }
}
