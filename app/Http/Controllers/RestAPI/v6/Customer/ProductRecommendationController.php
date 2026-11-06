<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Product\SectionListRequest;
use App\Http\Resources\V6\Customer\MostDemandedProductResource;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\GuestUserService;
use App\Services\New\MostDemandedService;
use App\Services\New\OrderDetailService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductRecommendationController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const SECTION_QUERY = [
        'limit' => 'nullable|integer|min:0',
        'offset' => 'nullable|integer',
        'guest_id' => 'nullable|integer',
    ];

    private const JUST_FOR_YOU_DEFAULT_LIMIT = 8;

    public function __construct(
        private readonly ProductRetailService $productRetailService,
        private readonly OrderDetailService $orderDetailService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
        private readonly MostDemandedService $mostDemandedService,
        private readonly ReviewService $reviewService,
    ) {}

    #[ApiDoc(
        summary: 'Discounted products',
        description: 'Products with a discount or in a running clearance sale, newest first. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Product recommendation',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getDiscounted(SectionListRequest $request): JsonResponse
    {
        $args = [
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_DISCOUNTED,
            filters: [
                ProductRetailService::FILTER_CLEARANCE_PRODUCT_IDS => $this->stockClearanceProductService->getProductIds(running: true),
            ],
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->pagedSectionResponse(request: $request, products: $products, args: $args);
    }

    #[ApiDoc(
        summary: 'Products the customer has ordered before',
        description: 'Up to 12 active products from the authenticated customer\'s past orders, in random order.',
        tag: 'Product recommendation',
        status: [401],
        auth: true,
        guard: 'api',
    )]
    public function getShopAgain(SectionListRequest $request): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SHOP_AGAIN,
            filters: [
                ProductRetailService::FILTER_PRODUCT_IDS => $this->orderDetailService->getOrderedProductIdsForCustomer(customerId: $customerId),
            ],
        );

        $this->stampCustomerFlags(products: $products, customerId: $customerId);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Most searched products',
        description: 'Served from the best selling list, as the legacy endpoint is.',
        tag: 'Product recommendation',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getMostSearched(SectionListRequest $request): JsonResponse
    {
        $args = [
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_MOST_SEARCHED,
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->pagedSectionResponse(request: $request, products: $products, args: $args);
    }

    #[ApiDoc(
        summary: 'Most demanded product banner',
        description: 'The first active banner whose product is active, with the product\'s order, delivery, review and wishlist counts. Data is an empty list when no banner qualifies. Send a bearer token to receive the customer\'s own wishlist count.',
        tag: 'Product recommendation',
        status: [200],
        auth: false,
        guard: 'api',
    )]
    public function getMostDemanded(Request $request): JsonResponse
    {
        $mostDemanded = $this->mostDemandedService->findBy(
            params: ['status' => MostDemandedService::STATUS_ACTIVE, MostDemandedService::FILTER_ACTIVE_PRODUCT => true],
            relations: ['product'],
        );

        if ($mostDemanded === null) {
            return $this->responseFormatter(config: config('response.data_fetched'), content: ['data' => []]);
        }

        $productId = $mostDemanded->product_id;
        $owner = $this->resolveCustomerOwner($request);
        $orderCounts = $this->orderDetailService->getOrderCountsForProduct(productId: $productId);

        $mostDemanded->setAttribute('order_count', $orderCounts['order_count']);
        $mostDemanded->setAttribute('delivery_count', $orderCounts['delivery_count']);
        $mostDemanded->setAttribute('review_count', $this->reviewService->count(filters: ['product_id' => $productId]));
        $mostDemanded->setAttribute('wishlist_count', $owner === null || $owner['is_guest']
            ? 0
            : $this->wishlistService->count(filters: ['product_id' => $productId, 'customer_id' => $owner['id']]));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => MostDemandedProductResource::make($mostDemanded)->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Products for you',
        description: 'Random active products from the categories of the signed-in customer\'s past orders; guests and customers without orders get random active products. Default page size is 8.',
        tag: 'Product recommendation',
        query: self::SECTION_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getJustForYou(SectionListRequest $request): JsonResponse
    {
        $args = [
            'limit' => $request->filled('limit') ? $request->dataLimit() : self::JUST_FOR_YOU_DEFAULT_LIMIT,
            'offset' => $request->page(),
        ];

        $owner = $this->resolveCustomerOwner($request);
        $filters = $owner === null || $owner['is_guest']
            ? []
            : [ProductRetailService::FILTER_PREFERRED_CATEGORY_IDS => $this->orderDetailService->getFirstLineCategoryIdsForCustomer(customerId: $owner['id'])];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_JUST_FOR_YOU,
            filters: $filters,
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->pagedSectionResponse(request: $request, products: $products, args: $args);
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function pagedSectionResponse(Request $request, iterable $products, array $args): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        $this->stampCustomerFlags(
            products: $products,
            customerId: $owner !== null && ! $owner['is_guest'] ? (int) $owner['id'] : null,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    private function stampCustomerFlags(iterable $products, ?int $customerId): void
    {
        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
        );
    }
}
