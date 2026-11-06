<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Brand\ListRequest;
use App\Http\Requests\Customer\Brand\ProductListRequest;
use App\Http\Resources\V6\Customer\BrandResource;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\GuestUserService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use App\Traits\ShopProductScopeTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class BrandController extends BaseApiController
{
    use CustomerOwnerTrait;
    use ShopProductScopeTrait;

    private const BASE_LOCALE = 'en';

    private const KEYWORD_MATCH_LIMIT = 10;

    public function __construct(
        private readonly BrandService $brandService,
        private readonly ProductRetailService $productRetailService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Brand list',
        description: 'Active brands. Send shop_slug to receive only the brands the shop has active products under.',
        tag: 'Brand',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string', 'sort_by' => 'nullable|string', 'shop_slug' => 'nullable|string'],
        status: [404, 422],
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $shopScope = $this->resolveShopProductIds(shopSlug: $request->shopSlug());

        if (! $shopScope['found']) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = [
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by'),
            'shop_slug' => $request->shopSlug(),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $filters = ['status' => 1];

        if ($shopScope['product_ids'] !== null) {
            $filters[BrandService::FILTER_PRODUCT_IDS] = $shopScope['product_ids'];
        }

        $brands = $this->brandService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['translations' => fn ($query) => $query->where('locale', app()->getLocale())],
            filters: $filters,
            sortBy: $args['sort_by'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => BrandResource::collection($brands),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($brands),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Products of a brand',
        description: 'Active products of an active brand. Omit limit to receive every product without pagination. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Brand',
        query: ['limit' => 'nullable|integer|min:0', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getProducts(ProductListRequest $request, int|string $brandId): JsonResponse
    {
        if ($this->brandService->findBy(params: ['id' => (int) $brandId, 'status' => 1]) === null) {
            return $this->responseFormatter(config: config('response.brand_not_found'));
        }

        $args = [
            'search' => $request['search'],
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $filters = [ProductRetailService::FILTER_BRAND_ID => (int) $brandId];

        if (! empty($args['search'])) {
            $filters[ProductRetailService::FILTER_SEARCH] = $args['search'];
            $filters[ProductRetailService::FILTER_SEARCH_PRODUCT_IDS] = $this->getKeywordProductIds(keyword: (string) $args['search']);
        }

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_BRAND,
            filters: $filters,
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        $this->stampOwnerFlags(request: $request, products: $products);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    protected function shopService(): ShopService
    {
        return $this->shopService;
    }

    protected function productRetailService(): ProductRetailService
    {
        return $this->productRetailService;
    }

    /**
     * @return array<int, int>
     */
    private function getKeywordProductIds(string $keyword): array
    {
        $matches = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SEARCH,
            filters: [
                ProductRetailService::FILTER_SEARCH => $keyword,
                ProductRetailService::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS => $this->authorService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS => $this->publishingHouseService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_SEARCH_TRANSLATED_ONLY => getDefaultLanguage() !== self::BASE_LOCALE,
            ],
            dataLimit: self::KEYWORD_MATCH_LIMIT,
            page: 1,
        );

        return collect($matches->items())->pluck('id')->all();
    }

    private function stampOwnerFlags(Request $request, iterable $products): void
    {
        $owner = $this->resolveCustomerOwner($request);
        $customerId = $owner !== null && ! $owner['is_guest'] ? (int) $owner['id'] : null;

        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
        );
    }
}
