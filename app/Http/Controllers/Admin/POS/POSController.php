<?php

namespace App\Http\Controllers\Admin\POS;

use App\Http\Requests\Admin\Pos\Coupon\ApplyRequest;
use App\Http\Requests\Admin\Pos\Customer\ChangeRequest;
use App\Http\Requests\Admin\Pos\Discount\UpdateRequest;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\ColorService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\OrderService;
use App\Services\New\Pos\PosCartService;
use App\Services\New\Pos\PosHoldOrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\ProductService;
use App\Traits\CommonTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class POSController extends PosBaseController
{
    use CommonTrait;

    public const SEARCH_RESULT_LIMIT = 20;

    private function posProductFilters(array $filter, array $whereIn): array
    {
        return array_filter([
            ...$whereIn,
            'added_by' => $filter['added_by'] ?? null,
            'seller_id' => $filter['seller_id'] ?? null,
            'product_type' => $filter['product_type'] ?? ($whereIn['product_type'] ?? null),
            'filter_category_ids' => $filter['filter_category_ids'] ?? [],
            'filter_sub_category_ids' => $filter['filter_sub_category_ids'] ?? [],
            'filter_sub_sub_category_ids' => $filter['filter_sub_sub_category_ids'] ?? [],
            ProductRetailService::FILTER_ACTIVE => true,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function posSearchFilters(Request $request): array
    {
        return [
            'status' => 1,
            'filter_sort_by' => $request['filter_sort_by'] ?? ProductRetailService::SORT_LATEST,
            'filter_category_ids' => $request['filter_category_ids'] ?? [],
            'filter_sub_category_ids' => $request['filter_sub_category_ids'] ?? [],
            'filter_sub_sub_category_ids' => $request['filter_sub_sub_category_ids'] ?? [],
        ];
    }

    private function posWhereInFilters(Request $request): array
    {
        return array_filter([
            'brand_id' => array_values($request['filter_brand_ids'] ?? []),
            'shop_id' => array_values($request['filter_shop_ids'] ?? []),
            'product_type' => array_values($request['filter_product_types'] ?? []),
            'status' => array_values($request['product_status'] ?? []),
        ]);
    }

    public function __construct(
        private readonly AuthorService $authorService,
        private readonly BrandService $brandService,
        private readonly CategoryService $categoryService,
        private readonly ColorService $colorService,
        ProductRetailService $retailProductService,
        CustomerService $customerService,
        PosHoldOrderService $posHoldOrderService,
        private readonly OrderService $orderService,
        private readonly CouponService $couponService,
        private readonly ProductService $productService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly PublishingHouseService $publishingHouseService,
    ) {
        parent::__construct(retailProductService: $retailProductService, customerService: $customerService, posHoldOrderService: $posHoldOrderService);
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $searchValue = $request['searchValue'] ?? null;
        $requestedProductType = $request->input('product_type', 'physical');
        $productType = $requestedProductType;

        if (! empty($searchValue)) {
            $filterWhereIn = $this->posWhereInFilters($request);
            $filter = $this->posSearchFilters($request);
            $filter['added_by'] = 'in_house';
            $allSearchResults = $this->retailProductService->getList(searchValue: $searchValue, dataLimit: getWebConfig('pagination_limit'), filters: $this->posProductFilters($filter, $filterWhereIn), sortBy: $filter['filter_sort_by'] ?? null);
            if ($allSearchResults->count() > 0) {
                $firstProductType = $allSearchResults->first()?->product_type;
                if (! $request->has('product_type')) {
                    $productType = $firstProductType;
                } else {
                    $hasResultsInRequestedType = $allSearchResults->contains('product_type', $requestedProductType);
                    if (! $hasResultsInRequestedType) {
                        $productType = $firstProductType;
                    }
                }
            }
        }
        $products = $this->getPosProductList(request: $request, productType: $productType ?? 'physical');
        $this->posHoldOrderService->registerCart(cartId: $this->posHoldOrderService->generateWalkInCartId());
        $this->releaseCartOfDeletedCustomer();
        $customers = $this->customerService->getList(dataLimit: 'all', filters: [CustomerService::FILTER_EXCLUDE_IDS => [0]]);
        $summaryData = array_merge($this->posHoldOrderService->getSummaryData(), $this->getCurrentCustomerSummary());
        $cartItems = $this->getCartSummary(cartId: $this->posHoldOrderService->getCurrentCartId());
        $order = $this->orderService->findBy(params: ['id' => $this->posHoldOrderService->getLastOrderId()]);
        $totalHoldOrder = $summaryData['totalHoldOrders'];

        $countries = getWebConfig(name: 'delivery_country_restriction')
            ? $this->get_delivery_country_array()
            : COUNTRIES;

        $zipCodes = getWebConfig(name: 'delivery_zip_code_area_restriction')
            ? $this->deliveryZipCodeService->getList(dataLimit: 'all')
            : 0;

        $productCounts = $this->posHoldOrderService->getProductCounts();

        $productBrands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);

        $productCategories = $this->categoryService->getList(
            dataLimit: 'all',
            relations: ['childes.childes'],
            sortBy: CategoryService::SORT_NEWEST_ID,
            position: CategoryService::POSITION_CATEGORY,
        );

        return view('admin-views.pos.index', compact(
            'products',
            'customers',
            'searchValue',
            'summaryData',
            'cartItems',
            'order',
            'totalHoldOrder',
            'countries',
            'zipCodes',
            'productCounts',
            'productType',
            'productBrands',
            'productCategories',
        ));
    }

    private function releaseCartOfDeletedCustomer(): void
    {
        $customerId = $this->posHoldOrderService->getCartCustomerId(cartId: $this->posHoldOrderService->getCurrentCartId());

        if ($customerId !== null && ! $this->customerService->exists(params: ['id' => $customerId])) {
            $this->posHoldOrderService->carryCurrentCartToWalkIn();
        }
    }

    public function changeCustomer(ChangeRequest $request): JsonResponse
    {
        $previousCustomerId = $this->posHoldOrderService->getCurrentCustomerId();
        $customerChanges = $this->posHoldOrderService->switchCustomerCart(
            cartId: $this->posHoldOrderService->getCustomerCartId(customerId: (int) $request->validated('user_id')),
        );

        $cartId = $this->posHoldOrderService->getCurrentCartId();
        $summaryData = array_merge($this->posHoldOrderService->getSummaryData(), $this->getCurrentCustomerSummary());
        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'view' => view('admin-views.pos.partials._admin-pos-customer-info', compact('summaryData', 'cartItems'))->render(),
            'cart_view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            'totalHoldOrders' => $this->posHoldOrderService->getTotalHoldOrders(),
            'holdNotices' => $this->customerChangeHoldNotices(customerChanges: $customerChanges, previousCustomerId: $previousCustomerId),
            'adjustmentNotices' => in_array(PosCartService::ADJUSTMENT_COUPON_REMOVED_CUSTOMER_CHANGED, $customerChanges, true)
                ? [translate('Coupon removed because the customer changed. Apply it again for this customer')]
                : [],
        ]);
    }

    private function customerChangeHoldNotices(array $customerChanges, int $previousCustomerId): array
    {
        if (in_array(PosHoldOrderService::CUSTOMER_CHANGE_WALK_IN_CART_HELD, $customerChanges, true)) {
            return [translate('The walk-in cart was saved to hold orders')];
        }

        if (in_array(PosHoldOrderService::CUSTOMER_CHANGE_CUSTOMER_CART_HELD, $customerChanges, true)) {
            $previousCustomer = $this->customerService->findBy(params: ['id' => $previousCustomerId]);
            $customerName = $previousCustomer ? trim($previousCustomer['f_name'].' '.$previousCustomer['l_name']) : translate('Customer');

            return [translate('Cart of :customer was saved to hold orders', ['customer' => $customerName])];
        }

        return [];
    }

    public function updateDiscount(UpdateRequest $request): JsonResponse
    {
        $cartId = $this->posHoldOrderService->getCurrentCartId();

        if (! $this->posHoldOrderService->hasCartLines(cartId: $cartId)) {
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'cart' => 'empty',
                'message' => translate('Please add a product to the cart before applying a discount'),
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        $extraDiscount = $this->posHoldOrderService->applyExtraDiscount(
            cartId: $cartId,
            type: $request->validated('type'),
            discount: (float) $request->validated('discount'),
        );
        if ($extraDiscount === null) {
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'extraDiscount' => 'amount_low',
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'extraDiscountAmount' => $extraDiscount,
            'extraDiscount' => 'success',
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function removeDiscount(): JsonResponse
    {
        $cartId = $this->posHoldOrderService->getCurrentCartId();
        $this->posHoldOrderService->removeExtraDiscount(cartId: $cartId);
        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'extraDiscountAmount' => 0,
            'extraDiscount' => 'success',
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function getCouponDiscount(ApplyRequest $request): JsonResponse
    {
        $cartId = $this->posHoldOrderService->getCurrentCartId();

        if (! $this->posHoldOrderService->hasCartLines(cartId: $cartId)) {
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'cart' => 'empty',
                'message' => translate('Please add a product to the cart before applying a coupon'),
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        $userId = $this->posHoldOrderService->getCurrentCustomerId();
        $couponCode = $request->validated('coupon_code');

        if ($userId != 0) {
            $usedCoupon = $this->orderService->count(filters: ['customer_type' => 'customer', 'coupon_code' => $couponCode, 'customer_id' => $userId]);
            $coupon = $this->couponService->findBy(params: [
                'code' => $couponCode,
                'added_by' => 'admin',
                CouponService::FILTER_USAGE_BELOW => $usedCoupon,
                CouponService::FILTER_RUNNING => true,
                'status' => 1,
            ]
            );
        } else {
            $coupon = $this->couponService->findBy(params: [
                'code' => $couponCode,
                'added_by' => 'admin',
                CouponService::FILTER_RUNNING => true,
                'status' => 1,
            ]
            );
        }

        if (! $coupon || $coupon['coupon_type'] == 'free_delivery' || $coupon['coupon_type'] == 'first_order') {
            $cartItems = $this->getCartSummary(cartId: $cartId);

            return response()->json([
                'coupon' => 'coupon_invalid',
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        if ($coupon['customer_id'] == '0' || $coupon['customer_id'] == $userId) {
            $couponResult = $this->posHoldOrderService->applyCoupon(cartId: $cartId, coupon: $coupon, couponCode: $couponCode);
            $cartItems = $this->getCartSummary(cartId: $cartId);

            if ($couponResult === PosCartService::COUPON_BELOW_MIN_PURCHASE) {
                return response()->json([
                    'coupon' => 'min_purchase',
                    'message' => translate('Add products worth at least :amount to use this coupon', [
                        'amount' => setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $coupon['min_purchase']), currencyCode: getCurrencyCode()),
                    ]),
                    'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            }

            return response()->json([
                'coupon' => $couponResult === PosCartService::COUPON_EXCEEDS_TOTAL ? 'amount_low' : 'success',
                'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        $cartItems = $this->getCartSummary(cartId: $cartId);

        return response()->json([
            'coupon' => 'coupon_invalid',
            'view' => view('admin-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function getQuickView(Request $request): JsonResponse
    {
        $product = $this->retailProductService->find(
            id: $request['product_id'],
            relations: ['brand', 'category', 'rating', 'tags', 'digitalVariation', 'clearanceSale' => function ($query) {
                return $query->active();
            }],
            withCount: ['reviews'],
        );
        $productAuthorIds = $this->productService->getProductAuthorsInfo(product: $product)['ids'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $productPublishingHouseIds = $this->productService->getProductPublishingHouseInfo(product: $product)['ids'];
        $publishingHouseRepo = $this->publishingHouseService->getList(dataLimit: 'all');
        $colorNames = $this->colorService->getNameMapForCodes(json_decode((string) $product->colors, true) ?: []);
        $selection = $this->posHoldOrderService->getQuickViewSelection(product: $product, colorNames: $colorNames);
        $addToCartLabel = $this->quickViewAddToCartLabel(selection: $selection);
        $isDigital = $product->product_type === PosCartService::PRODUCT_TYPE_DIGITAL;
        $imageSources = $this->quickViewImageSources(product: $product, isDigital: $isDigital);
        $productDescription = $this->quickViewDescription(details: (string) $product->details);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.partials._quick-view', compact('product', 'digitalProductAuthors', 'productAuthorIds', 'productPublishingHouseIds', 'publishingHouseRepo', 'colorNames', 'selection', 'addToCartLabel', 'isDigital', 'imageSources', 'productDescription'))->render(),
        ]);
    }

    private function quickViewDescription(string $details): string
    {
        $isPlainText = strip_tags($details) === $details;

        return $isPlainText ? nl2br(e($details)) : $details;
    }

    private function quickViewAddToCartLabel(array $selection): string
    {
        return match (true) {
            ! $selection['isSelectionAvailable'] => translate('Out of Stock'),
            $selection['isInCart'] => translate('Update Cart'),
            default => translate('Add To Cart'),
        };
    }

    private function quickViewImageSources(object $product, bool $isDigital): array
    {
        $hasColorImages = ! $isDigital && ! empty($product->retailDetail?->color_image) && count($product->color_images_full_url) > 0;

        return $hasColorImages ? $product->color_images_full_url : $product->images_full_url;
    }

    private function getPosProductList(Request $request, string $productType): LengthAwarePaginator
    {
        $filter = $this->posSearchFilters($request);
        $filter['added_by'] = 'in_house';
        $filter['product_type'] = $productType;

        return $this->retailProductService->getList(
            searchValue: $request['searchValue'] ?? null,
            dataLimit: getWebConfig('pagination_limit'),
            relations: ['clearanceSale'],
            filters: $this->posProductFilters($filter, $this->posWhereInFilters($request)),
            sortBy: $filter['filter_sort_by'] ?? null,
        );
    }

    public function getProductListView(Request $request): JsonResponse
    {
        $products = $this->getPosProductList(request: $request, productType: $request->input('product_type') ?? 'physical');

        return response()->json([
            'view' => view('admin-views.pos.partials._product-list', compact('products'))->render(),
            'hasMorePages' => $products->hasMorePages(),
            'productCounts' => $this->posHoldOrderService->getProductCounts(),
        ]);
    }

    public function getSearchedProductsView(Request $request): JsonResponse
    {
        $products = $this->retailProductService->getList(
            searchValue: $request['name'],
            dataLimit: self::SEARCH_RESULT_LIMIT,
            page: 1,
            relations: ['category', 'brand'],
            filters: [
                'added_by' => ProductRetailService::ADDED_BY_IN_HOUSE,
                ProductRetailService::FILTER_ACTIVE => true,
            ],
        );
        $data = [
            'count' => $products->count(),
            'result' => view('admin-views.pos.partials._search-product', compact('products'))->render(),
        ];
        if ($products->count() > 0) {
            $data += ['id' => $products[0]->id];
        }

        return response()->json($data);
    }
}
