<?php

namespace App\Http\Controllers\Admin\Product;

use App\Enums\BusinessModule;
use App\Exports\CustomerReviewListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Product\ReviewReplyRequest;
use App\Services\New\CustomerService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ProductService;
use App\Services\New\ReviewReplyService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Traits\BusinessModuleScopeTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wholesale\app\Services\OrderService as WholesaleOrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\ReviewService as WholesaleReviewService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewController extends BaseController
{
    use BusinessModuleScopeTrait;

    private const LIST_RELATIONS = ['product.seller.shop', 'customer', 'reply', 'order.details'];

    private const PRODUCT_PICKER_RELATIONS = ['category', 'brand', 'seller.shop'];

    private const PRODUCT_SEARCH_LIMIT = 20;

    private const CUSTOMER_OPTION_ALL = 'all';

    private const WALKING_CUSTOMER_ID = 0;

    private const IN_HOUSE_VENDOR_ID = '0';

    private const EXPORT_FILE_NAME = 'Customer-Review-List.xlsx';

    private const EXPORT_ALL_PRODUCTS = 'all_products';

    private const EXPORT_ALL_CUSTOMERS = 'all_customers';

    private const REVIEW_SERVICES = [
        BusinessModule::RETAIL => ReviewService::class,
        BusinessModule::WHOLESALE => WholesaleReviewService::class,
    ];

    private const PRODUCT_SERVICES = [
        BusinessModule::RETAIL => ProductRetailService::class,
        BusinessModule::WHOLESALE => ProductWholesaleService::class,
    ];

    private const ORDER_SERVICES = [
        BusinessModule::RETAIL => OrderService::class,
        BusinessModule::WHOLESALE => WholesaleOrderService::class,
    ];

    private const UNFILTERED_LIST_FILTERS = [
        'delivery_man_id' => null,
        ReviewService::FILTER_INCLUDE_INACTIVE => true,
    ];

    /** @var array<string, array<string, mixed>> */
    private array $moduleListFilters = [];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly ReviewReplyService $reviewReplyService,
        private readonly ShopService $shopService,
    ) {}

    protected function businessModuleAllowList(): array
    {
        return array_keys(self::REVIEW_SERVICES);
    }

    public function index(?Request $request, ?string $type = null): View
    {
        $module = $this->resolveModule($request->route('business_module'));
        $productService = $this->productService($module);
        $vendorShops = $this->vendorShopCards(module: $module);

        return view('admin-views.reviews.list', [
            'reviews' => $this->reviewService($module)->getList(
                dataLimit: getWebConfig(name: 'pagination_limit'),
                relations: self::LIST_RELATIONS,
                filters: $this->listFilters(request: $request, module: $module),
                sortBy: ReviewService::SORT_NEWEST_ID,
            )->appends($request->query()),
            'products' => $productService->getList(
                searchValue: $request['searchValue'],
                dataLimit: getWebConfig(name: 'pagination_limit'),
                relations: self::PRODUCT_PICKER_RELATIONS,
                filters: ['has_reviews' => true],
            ),
            'shopList' => $this->shopService->prependInHouseShop(
                vendors: $vendorShops,
                inHouseShop: $this->inHouseShopCard($module),
            ),
            'product' => $productService->find(id: $this->nullIfBlank($request['product_id']), relations: []),
            'vendor' => $this->findVendorShop($this->nullIfBlank($request['vendor_id'])),
            'from' => $request['from'],
            'to' => $request['to'],
            'customer_id' => $request['customer_id'],
            'vendor_id' => $request['vendor_id'],
            'product_id' => $request['product_id'],
            'status' => $request['status'],
            'searchValue' => $request['searchValue'],
            'customers' => $this->customerOptions(searchValue: $request['searchValue'], dataLimit: 'all'),
            'hasActiveFilter' => $this->hasReviewListFilter($request),
            'businessModuleTabs' => $this->businessModuleTabs(),
            'activeBusinessModule' => $module,
        ]);
    }

    public function searchVendor(Request $request): JsonResponse
    {
        $module = $this->resolveModule($request->route('business_module'));
        $vendorShops = $this->vendorShopCards(module: $module, searchValue: $request['searchValue']);

        $shopList = $this->matchesInHouseShopName($request['searchValue'])
            ? $this->shopService->prependInHouseShop(vendors: $vendorShops, inHouseShop: $this->inHouseShopCard($module))
            : $vendorShops;

        return response()->json([
            'result' => view('admin-views.reviews._review-vendors', [
                'shopList' => $shopList,
            ])->render(),
            'count' => count($shopList),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse|JsonResponse
    {
        $module = $this->resolveModule($request->route('business_module'));

        $this->reviewService($module)->updateStatus(id: $request['id'], status: (bool) ($request['status'] ?? 0));

        if ($request->ajax()) {
            return response()->json([
                'status' => 1,
                'message' => translate('Review status updated.'),
            ]);
        }

        ToastMagic::success(translate('Review status updated'));

        return back();
    }

    public function exportList(Request $request): BinaryFileResponse|RedirectResponse
    {
        $module = $this->resolveModule($request->route('business_module'));
        $productService = $this->productService($module);
        $product = $productService->find(id: $this->nullIfBlank($request['product_id']), relations: []);
        $customer = $this->findFilteredCustomer($request['customer_id']);

        return Excel::download(new CustomerReviewListExport([
            'data-from' => 'admin',
            'business_module' => $this->moduleLabel($module),
            'reviews' => $this->reviewService($module)->getList(
                dataLimit: getWebConfig(name: 'pagination_limit'),
                relations: self::LIST_RELATIONS,
                filters: $this->listFilters(request: $request, module: $module),
                sortBy: ReviewService::SORT_NEWEST_ID,
            ),
            'product_name' => $product?->name ?? self::EXPORT_ALL_PRODUCTS,
            'customer_name' => $customer ?? self::EXPORT_ALL_CUSTOMERS,
            'from' => $request['from'],
            'to' => $request['to'],
            'status' => $request['status'],
            'key' => $request['searchValue'],
        ]), self::EXPORT_FILE_NAME);
    }

    public function getCustomerList(Request $request): JsonResponse
    {
        return response()->json($this->customerOptions(searchValue: $request['searchValue']));
    }

    public function search(Request $request): JsonResponse
    {
        $products = $this->productService($this->resolveModule($request->route('business_module')))->getList(
            searchValue: $request['name'],
            dataLimit: self::PRODUCT_SEARCH_LIMIT,
            relations: self::PRODUCT_PICKER_RELATIONS,
            filters: [ProductService::FILTER_ACTIVE => true],
        );

        return response()->json([
            'result' => view('admin-views.partials._search-product', compact('products'))->render(),
        ]);
    }

    public function addReviewReply(ReviewReplyRequest $request): RedirectResponse
    {
        $this->reviewReplyService->updateBy(
            params: [
                'review_id' => $request['review_id'],
                'added_by' => ReviewReplyService::ADDED_BY_ADMIN,
                'added_by_id' => auth('admin')->id(),
            ],
            data: ['reply_text' => $request['reply_text']],
        );

        return back();
    }

    private function reviewService(string $module): ReviewService
    {
        return app(self::REVIEW_SERVICES[$module]);
    }

    private function productService(string $module): ProductService
    {
        return app(self::PRODUCT_SERVICES[$module]);
    }

    private function orderService(string $module): OrderService
    {
        return app(self::ORDER_SERVICES[$module]);
    }

    private function moduleLabel(string $module): string
    {
        return match ($module) {
            BusinessModule::WHOLESALE => translate('Wholesale'),
            BusinessModule::AUCTION => translate('Auction'),
            default => translate('Retail'),
        };
    }

    /**
     * @return array<string, array{label: string, url: string, count: ?int}>
     */
    private function businessModuleTabs(): array
    {
        $tabs = [];

        foreach (array_keys($this->getAvailableModules()) as $moduleKey) {
            $tabs[$moduleKey] = [
                'label' => $this->moduleLabel($moduleKey),
                'url' => route('admin.people.reviews.list', ['business_module' => $moduleKey]),
                'count' => $this->reviewService($moduleKey)->count(filters: self::UNFILTERED_LIST_FILTERS),
            ];
        }

        return $tabs;
    }

    private function listFilters(?Request $request, string $module): array
    {
        return $this->moduleListFilters[$module] ??= $this->buildListFilters($request, $this->productService($module));
    }

    private function buildListFilters(?Request $request, ProductService $productService): array
    {
        $searchValue = $this->nullIfBlank($request['searchValue']);
        $vendorId = $this->nullIfBlank($request['vendor_id']);

        return [
            ...$this->columnFilters($request),
            ...$this->dateFilters($request),
            ...($searchValue === null
                ? $this->vendorFilters(productService: $productService, vendorId: $vendorId)
                : $this->searchFilters(
                    productService: $productService,
                    searchValue: $searchValue,
                    vendorId: $vendorId,
                )),
            ReviewService::FILTER_INCLUDE_INACTIVE => true,
        ];
    }

    private function columnFilters(?Request $request): array
    {
        $filters = ['delivery_man_id' => null];
        $productId = $this->nullIfBlank($request['product_id']);
        $customerId = $this->nullIfBlank($request['customer_id']);
        $status = $this->nullIfBlank($request['status']);

        if ($productId !== null) {
            $filters['product_id'] = $productId;
        }

        if ($customerId !== null && $customerId !== self::CUSTOMER_OPTION_ALL) {
            $filters['customer_id'] = $customerId;
        }

        if ($status !== null) {
            $filters['status'] = $status;
        }

        return $filters;
    }

    private function dateFilters(?Request $request): array
    {
        $filters = [];
        $dateFrom = $this->nullIfBlank($request['from']);
        $dateTo = $this->nullIfBlank($request['to']);

        if ($dateFrom !== null) {
            $filters[ReviewService::FILTER_DATE_FROM] = $dateFrom;
        }

        if ($dateTo !== null) {
            $filters[ReviewService::FILTER_DATE_TO] = $dateTo;
        }

        return $filters;
    }

    private function vendorFilters(ProductService $productService, ?string $vendorId): array
    {
        return $vendorId === null
            ? []
            : [ReviewService::FILTER_MATCHING_PRODUCT_IDS => $productService->getIds(
                filters: $this->vendorProductOwnership($vendorId),
            )];
    }

    private function searchFilters(ProductService $productService, string $searchValue, ?string $vendorId): array
    {
        return [
            ReviewService::FILTER_MATCHING_PRODUCT_IDS => $productService->getIds(
                filters: $vendorId === null ? [] : $this->vendorProductOwnership($vendorId),
                searchValue: $searchValue,
            ),
            ReviewService::FILTER_MATCHING_CUSTOMER_IDS => $this->customerService->getIdsMatchingAnyNameWord(
                searchValue: $searchValue,
            ),
        ];
    }

    private function vendorProductOwnership(string $vendorId): array
    {
        return $vendorId === self::IN_HOUSE_VENDOR_ID
            ? ['added_by' => ProductService::ADDED_BY_IN_HOUSE]
            : ['added_by' => ProductService::ADDED_BY_SELLER, 'seller_id' => $vendorId];
    }

    private function vendorShopCards(string $module, ?string $searchValue = null): Collection
    {
        $vendorShops = $this->shopService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            filters: ['author_type' => ShopService::AUTHOR_TYPE_VENDOR],
        );

        $this->shopService->attachSellerRatings(
            shops: $vendorShops,
            ratingsBySeller: $this->reviewService($module)->getRatingBreakdownBySellers(
                sellerIds: $vendorShops->pluck('seller_id')->map(fn (mixed $id): int => (int) $id)->all(),
            ),
        );

        return $vendorShops;
    }

    private function inHouseShopCard(string $module): Model
    {
        $inHouseProductIds = $this->productService($module)->getActiveInHouseIds();
        $ratings = $this->reviewService($module)->getRatingBreakdownForProducts(productIds: $inHouseProductIds);
        $inHouseShop = getInHouseShopConfig();

        $inHouseShop->products_count = count($inHouseProductIds);
        $inHouseShop->review_count = $ratings['rating_count'];
        $inHouseShop->average_rating = $ratings['average_rating'];
        $inHouseShop->positive_review = $ratings['positive_percentage'];
        $inHouseShop->orders_count = $this->orderService($module)->count(
            filters: ['seller_is' => OrderService::SELLER_IS_ADMIN],
        );
        $inHouseShop->is_vacation_mode_now = checkVendorAbility(type: 'inhouse', status: 'vacation_status') ? 1 : 0;

        return $inHouseShop;
    }

    private function matchesInHouseShopName(?string $searchValue): bool
    {
        return $searchValue === null
            || str_contains(strtolower(getInHouseShopConfig(key: 'name')), strtolower($searchValue));
    }

    private function findVendorShop(?string $vendorId): ?Model
    {
        return $vendorId === null || $vendorId === self::IN_HOUSE_VENDOR_ID
            ? null
            : $this->shopService->findBy(params: [
                'seller_id' => $vendorId,
                'author_type' => ShopService::AUTHOR_TYPE_VENDOR,
            ]);
    }

    private function findFilteredCustomer(mixed $customerId): ?Model
    {
        $customerId = $this->nullIfBlank($customerId);

        return $customerId === null || $customerId === self::CUSTOMER_OPTION_ALL
            ? null
            : $this->customerService->find(id: $customerId, relations: []);
    }

    private function customerOptions(?string $searchValue = null, int|string $dataLimit = DEFAULT_DATA_LIMIT): array
    {
        $customers = $this->customerService->getNameOptions(searchValue: $searchValue, dataLimit: $dataLimit)
            ->filter(fn (Model $customer): bool => $customer->id != self::WALKING_CUSTOMER_ID)
            ->values()
            ->toArray();

        array_unshift($customers, ['id' => self::CUSTOMER_OPTION_ALL, 'text' => translate('All Customer')]);

        return $customers;
    }

    private function hasReviewListFilter(Request $request): bool
    {
        return (bool) array_filter([
            $request['product_id'],
            $request['vendor_id'],
            $request['customer_id'] === self::CUSTOMER_OPTION_ALL ? null : $request['customer_id'],
            $request['status'],
            $request['from'],
            $request['to'],
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function nullIfBlank(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
