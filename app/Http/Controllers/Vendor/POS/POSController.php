<?php

namespace App\Http\Controllers\Vendor\POS;

use App\Enums\SessionKey;
use App\Http\Controllers\BaseController;
use App\Services\CartService;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use App\Services\POSService;
use App\Services\ProductService;
use App\Traits\CalculatorTrait;
use App\Traits\CommonTrait;
use App\Traits\CustomerTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class POSController extends BaseController
{
    use CalculatorTrait, CommonTrait, CustomerTrait;

    /**
     * @param  array<string, mixed>  $filter
     * @param  array<string, mixed>  $whereIn
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
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
        private readonly VendorService $vendorService,
        private readonly BrandService $brandService,
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $retailProductService,
        private readonly CustomerService $customerService,
        private readonly ShopService $shopService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
        private readonly POSService $POSService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly ProductService $productService,
        private readonly PublishingHouseService $publishingHouseService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $filterWhereIn = $this->posWhereInFilters($request);
        $vendorId = auth('seller')->id();
        $vendor = $this->vendorService->findBy(params: ['id' => $vendorId]);
        $getPOSStatus = getWebConfig('seller_pos');
        if ($vendor['pos_status'] == 0 || $getPOSStatus == 0) {
            ToastMagic::warning(translate('Access denied!!'));
        }
        $productType = $request->input('product_type', 'physical');
        $shop = $this->shopService->findBy(params: ['id' => $vendorId]);
        $filter = $this->posSearchFilters($request);
        $filter['added_by'] = 'seller';
        $filter['seller_id'] = $vendorId;
        $searchValue = $request['searchValue'] ?? null;
        if (! empty($searchValue)) {
            $allSearchResults = $this->retailProductService->getList(searchValue: $searchValue, dataLimit: getWebConfig('pagination_limit'), filters: $this->posProductFilters($filter, $filterWhereIn), sortBy: $filter['filter_sort_by'] ?? null);
            if ($allSearchResults->count() > 0) {
                $firstProductType = $allSearchResults->first()?->product_type;
                if (! $request->has('product_type')) {
                    $productType = $firstProductType;
                } else {
                    $hasResultsInRequestedType = $allSearchResults->contains('product_type', $productType);
                    if (! $hasResultsInRequestedType) {
                        $productType = $firstProductType;
                    }
                }
            }
        }
        $products = $this->getPosProductList(request: $request, productType: $productType ?? 'physical', vendorId: $vendorId);
        $cartId = 'walk-in-customer-'.rand(10, 1000);
        $this->cartService->getNewCartSession(cartId: $cartId);
        $customers = $this->customerService->getList(dataLimit: 'all', filters: [CustomerService::FILTER_EXCLUDE_IDS => [0]]);
        $getCurrentCustomerData = $this->getCustomerDataFromSessionForPOS();
        $summaryData = array_merge($this->POSService->getSummaryData(), $getCurrentCustomerData);
        $cartItems = $this->getCartData(cartName: $this->cartService->getCurrentCartId());
        $order = $this->orderService->findBy(params: ['id' => session(SessionKey::LAST_ORDER)]);
        $totalHoldOrder = $summaryData['totalHoldOrders'];
        $countries = getWebConfig(name: 'delivery_country_restriction') ? $this->get_delivery_country_array() : COUNTRIES;
        $zipCodes = getWebConfig(name: 'delivery_zip_code_area_restriction') ? $this->deliveryZipCodeService->getList(dataLimit: 'all') : 0;
        $productCounts = $this->cartService->getProductCounts();
        $currentCustomerSessionInfo = $this->cartService->getCurrentCartItems();

        $productBrands = $this->brandService->getList(dataLimit: 'all', sortBy: BrandService::SORT_NEWEST_ID);

        $productCategories = $this->categoryService->getList(
            dataLimit: 'all',
            relations: ['childes.childes'],
            sortBy: CategoryService::SORT_NEWEST_ID,
            position: CategoryService::POSITION_CATEGORY,
        );

        return view('vendor-views.pos.index', compact(
            'products',
            'cartId',
            'customers',
            'shop',
            'searchValue',
            'summaryData',
            'cartItems',
            'order',
            'totalHoldOrder',
            'countries',
            'zipCodes',
            'productType',
            'currentCustomerSessionInfo',
            'productCounts',
            'productBrands',
            'productCategories',
        ));
    }

    private function getPosProductList(Request $request, string $productType, int $vendorId): LengthAwarePaginator
    {
        $filter = $this->posSearchFilters($request);
        $filter['added_by'] = 'seller';
        $filter['seller_id'] = $vendorId;
        $filter['product_type'] = $productType;

        return $this->retailProductService->getList(
            searchValue: $request['searchValue'] ?? null,
            dataLimit: getWebConfig('pagination_limit'),
            relations: ['clearanceSale' => fn ($query) => $query->active()],
            filters: $this->posProductFilters($filter, $this->posWhereInFilters($request)),
            sortBy: $filter['filter_sort_by'],
        );
    }

    public function getProductListView(Request $request): JsonResponse
    {
        $products = $this->getPosProductList(request: $request, productType: $request['product_type'] ?? 'physical', vendorId: auth('seller')->id());

        return response()->json([
            'view' => view('vendor-views.pos.partials._product-list', compact('products'))->render(),
            'hasMorePages' => $products->hasMorePages(),
            'productCounts' => $this->cartService->getProductCounts(),
        ]);
    }

    public function changeCustomer(Request $request): JsonResponse
    {
        $cartId = ($request['user_id'] != 0 ? 'saved-customer-'.$request['user_id'] : 'walk-in-customer-'.rand(10, 1000));
        $this->POSService->UpdateSessionWhenCustomerChange(cartId: $cartId);
        $getCurrentCustomerData = $this->getCustomerDataFromSessionForPOS();
        $summaryData = array_merge($this->POSService->getSummaryData(), $getCurrentCustomerData);
        $cartItems = $this->getCartData(cartName: $cartId);

        return response()->json([
            'view' => view('vendor-views.pos.partials._vendor-pos-customer-info', compact('summaryData', 'cartItems'))->render(),
            'cart_view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function updateDiscount(Request $request): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        if ($request['type'] == 'percent' && ($request['discount'] < 0 || $request['discount'] > 100)) {
            $cartItems = $this->getCartData(cartName: $cartId);
            $text = $request['discount'] > 0 ? 'Extra_discount_can_not_be_less_than_0_percent' :
                'Extra_discount_can_not_be_more_than_100_percent';
            ToastMagic::error(translate($text));

            return response()->json([
                'extraDiscount' => 'amount_low',
                'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }
        $cart = session($cartId, collect());
        if ($cart) {
            $totalProductPrice = 0;
            $productDiscount = 0;
            $couponDiscount = $cart['coupon_discount'] ?? 0;

            foreach ($cart as $item) {
                if (is_array($item)) {
                    $product = $this->retailProductService->find(id: $item['id'], relations: ['clearanceSale' => function ($query) {
                        return $query->active();
                    }]);
                    $totalProductPrice += $item['price'] * $item['quantity'];
                    $productDiscount += $item['discount'] * $item['quantity'];
                }
            }
            if ($request['type'] == 'percent') {
                $extraDiscount = (($totalProductPrice - $productDiscount - $couponDiscount) / 100) * $request['discount'];
            } else {
                $extraDiscount = currencyConverter(amount: $request['discount']);
            }
            $total = $totalProductPrice - $productDiscount - $couponDiscount - $extraDiscount;
            if ($total < 0) {
                $cartItems = $this->getCartData(cartName: $cartId);

                return response()->json([
                    'extraDiscount' => 'amount_low',
                    'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            } else {
                $cart['ext_discount'] = $request['type'] == 'percent' ? $request['discount'] : currencyConverter(amount: $request['discount']);
                $cart['ext_discount_type'] = $request['type'];
                session()->put($cartId, $cart);
                $cartItems = $this->getCartData(cartName: $cartId);

                return response()->json([
                    'extraDiscountAmount' => $extraDiscount ?? 0,
                    'extraDiscount' => 'success',
                    'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            }
        } else {
            $cartItems = $this->getCartData(cartName: $cartId);

            return response()->json([
                'extraDiscount' => 'empty',
                'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }
    }

    public function getCouponDiscount(Request $request): JsonResponse
    {
        $cartId = $this->cartService->getCurrentCartId();
        $userId = $this->cartService->getUserId();
        if ($userId != 0) {
            $usedCoupon = $this->orderService->count(filters: ['customer_type' => 'customer', 'coupon_code' => $request['coupon_code'], 'customer_id' => $userId]);
            $coupon = $this->couponService->findBy(params: [
                'code' => $request['coupon_code'],
                'coupon_bearer' => 'seller',
                CouponService::FILTER_USAGE_BELOW => $usedCoupon,
                CouponService::FILTER_RUNNING => true,
                'status' => 1,
            ]
            );

        } else {
            $coupon = $this->couponService->findBy(params: [
                'code' => $request['coupon_code'],
                'coupon_bearer' => 'seller',
                CouponService::FILTER_RUNNING => true,
                'status' => 1,
            ]
            );
        }
        if (! $coupon || $coupon['coupon_type'] == 'free_delivery' || $coupon['coupon_type'] == 'first_order') {
            $cartItems = $this->getCartData(cartName: $cartId);

            return response()->json([
                'coupon' => 'coupon_invalid',
                'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
            ]);
        }

        $carts = session($cartId);
        $totalProductPrice = 0;
        $productDiscount = 0;

        if (($coupon['seller_id'] == '0' || $coupon['seller_id'] == auth('seller')->id()) && ($coupon['customer_id'] == '0' || $coupon['customer_id'] == $userId)) {
            if ($carts != null) {
                foreach ($carts as $cart) {
                    if (is_array($cart)) {
                        $product = $this->retailProductService->find(id: $cart['id'], relations: ['clearanceSale' => function ($query) {
                            return $query->active();
                        }]);
                        $totalProductPrice += $cart['price'] * $cart['quantity'];
                        $productDiscount += $cart['discount'] * $cart['quantity'];
                    }
                }
                if ($totalProductPrice >= $coupon['min_purchase']) {
                    $calculation = $this->POSService->getCouponCalculation(coupon: $coupon, totalProductPrice: $totalProductPrice, productDiscount: $productDiscount);
                    $couponDiscount = $calculation['discount'];

                    $extraDiscount = 0;
                    if (isset($carts['ext_discount_type']) && isset($carts['ext_discount'])) {
                        if ($carts['ext_discount_type'] == 'percent') {
                            $extraDiscount = (($totalProductPrice - $productDiscount - $couponDiscount) / 100) * $carts['ext_discount'];
                        } else {
                            $extraDiscount = $carts['ext_discount'];
                        }
                    }

                    $total = $totalProductPrice - $productDiscount - $couponDiscount - $extraDiscount;
                    if ($total < 0) {
                        $cartItems = $this->getCartData(cartName: $cartId);

                        return response()->json([
                            'coupon' => 'amount_low',
                            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                        ]);
                    }

                    $this->POSService->putCouponDataOnSession(
                        cartId: $cartId,
                        discount: $couponDiscount,
                        couponTitle: $coupon['title'],
                        couponBearer: $coupon['coupon_bearer'],
                        couponCode: $request['coupon_code']
                    );
                    $cartItems = $this->getCartData(cartName: $cartId);

                    return response()->json([
                        'coupon' => 'success',
                        'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                    ]);
                }
            } else {
                $cartItems = $this->getCartData(cartName: $cartId);

                return response()->json([
                    'coupon' => 'cart_empty',
                    'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
                ]);
            }
        }
        $cartItems = $this->getCartData(cartName: $cartId);

        return response()->json([
            'coupon' => 'coupon_invalid',
            'view' => view('vendor-views.pos.partials._cart', compact('cartId', 'cartItems'))->render(),
        ]);
    }

    public function getQuickView(Request $request): JsonResponse
    {
        $product = $this->retailProductService->find(
            id: $request['product_id'],
            relations: ['brand', 'category', 'rating', 'tags', 'digitalVariation', 'digitalProductAuthors.author', 'clearanceSale' => function ($query) {
                return $query->active();
            }],
            withCount: ['reviews'],
        );
        $cartId = $this->cartService->getCurrentCartId();
        $cartItems = $this->getCartData(cartName: $cartId);
        $productSubtotal = $cartItems['productSubtotal'] ?? 0;
        $productAuthorIds = $this->productService->getProductAuthorsInfo(product: $product)['ids'];
        $digitalProductAuthors = $this->authorService->getList(dataLimit: 'all');
        $productPublishingHouseIds = $this->productService->getProductPublishingHouseInfo(product: $product)['ids'];
        $publishingHouseRepo = $this->publishingHouseService->getList(dataLimit: 'all');

        return response()->json([
            'success' => 1,
            'view' => view('vendor-views.pos.partials._quick-view', compact('product', 'digitalProductAuthors', 'productAuthorIds', 'productPublishingHouseIds', 'publishingHouseRepo', 'productSubtotal'))->render(),
        ]);
    }

    protected function getCustomerDataFromSessionForPOS(): array
    {
        if (Str::contains($this->cartService->getCurrentCartId(), 'walk-in-customer')) {
            $currentCustomerInfo = ['customerName' => 'Walk-In Customer'];
            $currentCustomerData = $this->customerService->findBy(params: ['id' => '0']);
        } else {
            $userId = explode('-', $this->cartService->getCurrentCartId())[2];
            $currentCustomerData = $this->customerService->findBy(params: ['id' => $userId]);
            $currentCustomerInfo = $this->cartService->getCustomerInfo(currentCustomerData: $currentCustomerData, customerId: $userId);
        }

        return [
            'currentCustomer' => $currentCustomerInfo['customerName'],
            'currentCustomerData' => $currentCustomerData,
        ];
    }

    protected function getCustomerCartData(string $cartName): array
    {
        $customerCartData = [];
        if (Str::contains($cartName, 'walk-in-customer')) {
            $currentCustomerInfo = [
                'customerName' => 'Walk-In Customer',
                'customerPhone' => '',
            ];
            $customerId = 0;
        } else {
            $customerId = explode('-', $cartName)[2];
            $currentCustomerData = $this->customerService->findBy(params: ['id' => $customerId]);
            $currentCustomerInfo = $this->cartService->getCustomerInfo(currentCustomerData: $currentCustomerData, customerId: $customerId);
        }
        $customerCartData[$cartName] = [
            'customerName' => $currentCustomerInfo['customerName'],
            'customerPhone' => $currentCustomerInfo['customerPhone'],
            'customerId' => $customerId,
        ];

        return $customerCartData;
    }

    protected function calculateCartItemsData(string $cartName, array $customerCartData): array
    {
        $cartItemValue = [];
        $subTotalCalculation = [
            'countItem' => 0,
            'totalQuantity' => 0,
            'subtotal' => 0,
            'discountOnProduct' => 0,
            'productSubtotal' => 0,
            'totalTax' => 0,
        ];

        $cartListSession = $this->cartService->filterSessionCartList(cart: session($cartName));

        if (count($cartListSession) > 0) {
            $products = $this->retailProductService->getList(
                dataLimit: 'all',
                relations: ['clearanceSale' => fn ($query) => $query->active()],
                filters: ['id' => $cartListSession->pluck('id')->toArray()],
            );
            $totalDiscountedPrice = $this->cartService->getCartTotalDiscountPrice(session($cartName), $products, $customerCartData[$cartName]['customerId']);

            foreach ($cartListSession as $cartItem) {
                if (is_array($cartItem)) {
                    $product = $products->firstWhere('id', $cartItem['id']);
                    if ($product) {
                        $cartSubTotalCalculation = $this->cartService->getCartSubtotalCalculation(
                            product: $product,
                            cartItem: $cartItem,
                            totalDiscountedPrice: $totalDiscountedPrice,
                            cartName: $cartName,
                        );
                        if ($cartItem['customerId'] == $customerCartData[$cartName]['customerId']) {
                            $cartItem['productSubtotal'] = $cartSubTotalCalculation['productSubtotal'];
                            $cartItemValue[] = $cartItem;
                            $subTotalCalculation['customerOnHold'] = $cartItem['customerOnHold'];

                            $subTotalCalculation['countItem'] += $cartSubTotalCalculation['countItem'];
                            $subTotalCalculation['totalQuantity'] += $cartSubTotalCalculation['totalQuantity'];
                            $subTotalCalculation['productSubtotal'] += $cartSubTotalCalculation['productSubtotal'];
                            $subTotalCalculation['subtotal'] += $cartSubTotalCalculation['subtotal'];
                            $subTotalCalculation['discountOnProduct'] += $cartSubTotalCalculation['discountOnProduct'];
                            $subTotalCalculation['totalTax'] += $cartSubTotalCalculation['appliedTaxAmount'];
                        }
                    }
                }
            }
        }
        $totalCalculation = $this->cartService->getTotalCalculation(
            subTotalCalculation: $subTotalCalculation, cartName: $cartName
        );

        return [
            'countItem' => $subTotalCalculation['countItem'],
            'total' => $totalCalculation['total'],
            'subtotal' => $subTotalCalculation['subtotal'],
            'discountOnProduct' => $subTotalCalculation['discountOnProduct'],
            'productSubtotal' => $subTotalCalculation['productSubtotal'],
            'totalTax' => $subTotalCalculation['totalTax'],
            'cartItemValue' => $cartItemValue,
            'customerOnHold' => $subTotalCalculation['customerOnHold'] ?? false,
            'couponDiscount' => $totalCalculation['couponDiscount'],
            'extraDiscount' => $totalCalculation['extraDiscount'],
        ];
    }

    protected function getCartData(string $cartName): array
    {
        $customerCartData = $this->getCustomerCartData(cartName: $cartName);
        $cartItemData = $this->calculateCartItemsData(cartName: $cartName, customerCartData: $customerCartData);

        return array_merge($customerCartData[$cartName], $cartItemData);
    }

    public function getSearchedProductsView(Request $request): JsonResponse
    {

        $products = $this->retailProductService->getList(
            searchValue: $request['name'],
            dataLimit: 'all',
            relations: ['category', 'brand', 'clearanceSale' => fn ($query) => $query->active()],
            filters: [
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'seller_id' => auth('seller')->id(),
                'status' => 1,
                ProductRetailService::FILTER_ACTIVE => true,
            ],
        );

        $data = [
            'count' => $products->count(),
            'result' => view('vendor-views.pos.partials._search-product', compact('products'))->render(),
        ];
        if ($products->count() > 0) {
            $data += ['id' => $products[0]->id];
        }

        return response()->json($data);
    }
}
