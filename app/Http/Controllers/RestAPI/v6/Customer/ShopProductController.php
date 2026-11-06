<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Product\ListRequest;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\AuthorService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\GuestUserService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ShopProductController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const DEFAULT_PRODUCT_LIMIT = 10;

    private const DEFAULT_SECTION_LIMIT = 15;

    private const SECTION_QUERY = [
        'limit' => 'nullable|integer|min:1',
        'offset' => 'nullable|integer',
    ];

    private const RECOMMENDED_QUERY = [
        'category' => 'nullable|string',
        'limit' => 'nullable|integer|min:1',
        'offset' => 'nullable|integer',
    ];

    private const PRODUCT_QUERY = [
        'limit' => 'nullable|integer|min:1',
        'offset' => 'nullable|integer',
        'search' => 'nullable|string',
        'product_type' => 'nullable|string|in:physical,digital',
        'offer_type' => 'nullable|string|in:clearance_sale',
        'brand_ids' => 'nullable|string',
        'category' => 'nullable|string',
        'publishing_houses' => 'nullable|string',
        'product_authors' => 'nullable|string',
        'product_id' => 'nullable|integer',
    ];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly ProductRetailService $productRetailService,
        private readonly CategoryService $categoryService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Shop products',
        description: 'Active products of a vendor shop or the in-house shop, ordered by the vendor product list priority setup (clearance sale priority when offer_type is clearance_sale). brand_ids, category, publishing_houses and product_authors accept an array or a JSON array string; 0 in publishing_houses or product_authors selects digital products without one. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Shop',
        query: self::PRODUCT_QUERY,
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getList(ListRequest $request, string $slug): JsonResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug]);

        if ($shop === null) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = [
            'search' => $request['search'],
            'product_type' => $request['product_type'],
            'offer_type' => $request['offer_type'],
            'brand_ids' => $request->getInputIds(key: ListRequest::INPUT_BRAND_IDS),
            'category' => $request->getInputIds(key: ListRequest::INPUT_CATEGORY_IDS),
            'publishing_houses' => $request->getInputIds(key: ListRequest::INPUT_PUBLISHING_HOUSE_IDS),
            'product_authors' => $request->getInputIds(key: ListRequest::INPUT_AUTHOR_IDS),
            'product_id' => $request['product_id'],
            'limit' => $request->getLimit(default: self::DEFAULT_PRODUCT_LIMIT),
            'offset' => $request->getPage(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SHOP,
            filters: $this->getShopSectionFilters(shop: $shop, args: $args),
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->productListResponse(request: $request, products: $products, args: $args);
    }

    #[ApiDoc(
        summary: 'Shop best selling products',
        description: 'Active products of the shop that have been ordered, most ordered first. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Shop',
        query: self::SECTION_QUERY,
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getBestSelling(ListRequest $request, string $slug): JsonResponse
    {
        return $this->shopSectionList(request: $request, slug: $slug, section: ProductRetailService::SECTION_SHOP_BEST_SELLING);
    }

    #[ApiDoc(
        summary: 'Shop featured products',
        description: 'Featured active products of the shop, ordered by the featured product priority setup when custom sorting is on. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Shop',
        query: self::SECTION_QUERY,
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getFeatured(ListRequest $request, string $slug): JsonResponse
    {
        return $this->shopSectionList(request: $request, slug: $slug, section: ProductRetailService::SECTION_SHOP_FEATURED);
    }

    #[ApiDoc(
        summary: 'Shop recommended products',
        description: 'Active products of the shop ordered by delivered orders, then by tag visits. category accepts an array or a JSON array string of category, sub category or sub sub category ids. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Shop',
        query: self::RECOMMENDED_QUERY,
        hints: [
            'category' => 'Category ids at any level, e.g. [12,45]; a sub category narrows its parent category, a sub sub category narrows its sub category',
            'limit' => 'Products per page, 15 when omitted',
            'offset' => 'Page number, starting at 1',
        ],
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getRecommended(ListRequest $request, string $slug): JsonResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug]);

        if ($shop === null) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = [
            'category' => $request->getInputIds(key: ListRequest::INPUT_CATEGORY_IDS),
            'limit' => $request->getLimit(default: self::DEFAULT_SECTION_LIMIT),
            'offset' => $request->getPage(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SHOP_RECOMMENDED,
            filters: $this->getShopOwnershipFilters(shop: $shop) + $this->getCategoryLevelFilters(categoryIds: $args['category']),
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->productListResponse(request: $request, products: $products, args: $args);
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function shopSectionList(ListRequest $request, string $slug, string $section): JsonResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug]);

        if ($shop === null) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = [
            'limit' => $request->getLimit(default: self::DEFAULT_SECTION_LIMIT),
            'offset' => $request->getPage(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: $section,
            filters: $this->getShopOwnershipFilters(shop: $shop),
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->productListResponse(request: $request, products: $products, args: $args);
    }

    private function productListResponse(ListRequest $request, mixed $products, array $args): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);
        $customerId = $owner !== null && ! $owner['is_guest'] ? (int) $owner['id'] : null;

        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
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

    /**
     * @return array<string, mixed>
     */
    private function getShopOwnershipFilters(Model $shop): array
    {
        if ($shop->author_type === ShopService::AUTHOR_TYPE_ADMIN) {
            return [ProductRetailService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_ADMIN];
        }

        return [
            ProductRetailService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_SELLER,
            ProductRetailService::FILTER_SELLER_ID => (int) $shop->seller_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCategoryLevelFilters(array $categoryIds): array
    {
        $categoryIdsByLevel = $this->categoryService->getIdsByPosition(categoryIds: $categoryIds);

        return [
            ProductRetailService::FILTER_CATEGORY_IDS => $categoryIdsByLevel['category_ids'],
            ProductRetailService::FILTER_SUB_CATEGORY_IDS => $categoryIdsByLevel['sub_category_ids'],
            ProductRetailService::FILTER_SUB_SUB_CATEGORY_IDS => $categoryIdsByLevel['sub_sub_category_ids'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getShopSectionFilters(Model $shop, array $args): array
    {
        $filters = $this->getShopOwnershipFilters(shop: $shop) + [
            ProductRetailService::FILTER_SEARCH => $args['search'],
            ProductRetailService::FILTER_PRODUCT_TYPE => $args['product_type'],
            ProductRetailService::FILTER_OFFER_TYPE => $args['offer_type'],
            ProductRetailService::FILTER_BRAND_IDS => $args['brand_ids'],
        ] + $this->getCategoryLevelFilters(categoryIds: $args['category']);

        if ($args['offer_type'] === ProductRetailService::OFFER_CLEARANCE_SALE) {
            $filters[ProductRetailService::FILTER_CLEARANCE_PRODUCT_IDS] = $this->stockClearanceProductService->getProductIds(running: true);
        }

        if (! empty($args['product_id'])) {
            $filters[ProductRetailService::FILTER_EXCLUDE_IDS] = [$args['product_id']];
        }

        if ($args['publishing_houses'] !== []) {
            $filters[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS] = $args['publishing_houses'];
            $filters[ProductRetailService::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds(ownerIds: $args['publishing_houses']);
            $filters[ProductRetailService::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds();
        }

        if ($args['product_authors'] !== []) {
            $filters[ProductRetailService::FILTER_AUTHOR_IDS] = $args['product_authors'];
            $filters[ProductRetailService::FILTER_SELECTED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds(ownerIds: $args['product_authors']);
            $filters[ProductRetailService::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds();
        }

        return $filters;
    }
}
