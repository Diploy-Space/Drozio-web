<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Product\AddRequest;
use App\Http\Requests\Shared\Product\DeleteImageRequest;
use App\Http\Requests\Shared\Product\UpdateRequest;
use App\Http\Requests\Vendor\Product\ListRequest;
use App\Http\Requests\Vendor\Product\StatusUpdateRequest;
use App\Http\Resources\V6\Vendor\ProductEditResource;
use App\Http\Resources\V6\Vendor\ProductListResource;
use App\Http\Resources\V6\Vendor\ProductResource;
use App\Services\New\CartService;
use App\Services\New\CategoryService;
use App\Services\New\ColorService;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\ProductRetailService;
use App\Services\New\ShopService;
use App\Traits\ProductTrait;
use App\Utils\CartManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductController extends BaseApiController
{
    use ProductTrait;

    private const SELLER_STATES = [
        ProductRetailService::REQUEST_STATE_ALL,
        ProductRetailService::REQUEST_STATE_NEW,
        ProductRetailService::REQUEST_STATE_APPROVED,
        ProductRetailService::REQUEST_STATE_DENIED,
    ];

    private const SORT_KEYS = [
        ProductRetailService::SORT_LATEST,
        ProductRetailService::SORT_OLDEST,
        ProductRetailService::SORT_BEST_SELLING,
        ProductRetailService::SORT_MOST_FAVORITE,
    ];

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly ColorService $colorService,
        private readonly ShopService $shopService,
        private readonly CartService $cartService,
        private readonly CategoryService $categoryService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly DealOfTheDayService $dealOfTheDayService,
    ) {}

    #[ApiDoc(
        summary: 'Retail products of the authenticated seller for one request state',
        tag: 'Products (Seller)',
        query: [
            'state' => 'nullable|string|in:all,new,approved,denied|default:all',
            'search' => 'nullable|string',
            'filter_sort_by' => 'nullable|string|in:latest,oldest,best-selling,most-favorite|default:latest',
            'product_status' => 'nullable|array|in:0,1',
            'filter_product_types' => 'nullable|array|in:physical,digital',
            'filter_brand_ids' => 'nullable|array',
            'filter_category_ids' => 'nullable|array',
            'filter_sub_category_ids' => 'nullable|array',
            'filter_sub_sub_category_ids' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|default:25',
            'offset' => 'nullable|integer|min:1|default:1',
        ],
        hints: [
            'state' => 'all = every request state · new = awaiting admin approval · approved = approved by admin · denied = rejected by admin; any other value is treated as all.',
            'search' => 'Partial match on product name (full-text when the search driver is fulltext), product code, or any translated name; a numeric value also matches the product id exactly.',
            'filter_sort_by' => 'latest = newest first · oldest = oldest first · best-selling = most delivered quantity first · most-favorite = most customer reviews first; any other value is treated as latest.',
            'product_status' => '1 = active, 0 = inactive; send one value, a list, or a JSON array. Applied only when state is all or approved, and then keeps approved products only, so pending and denied products never appear under active or inactive.',
            'filter_product_types' => 'physical or digital; send one value, a list, or a JSON array.',
            'filter_brand_ids' => 'Brand ids from GET /api/v6/seller/brands; keeps products of any listed brand.',
            'filter_category_ids' => 'Category ids from GET /api/v6/seller/categories; category filters of all three levels are combined with OR.',
            'filter_sub_category_ids' => 'Sub-category ids; combined with the other category filters using OR.',
            'filter_sub_sub_category_ids' => 'Sub-sub-category ids; combined with the other category filters using OR.',
            'limit' => 'Rows per page (≥1), default 25; an invalid value falls back to 25.',
            'offset' => 'Page number, starting at 1.',
        ],
        status: [422],
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $state = $this->requestedState($request);
        $searchValue = $request->query('search');
        $sortBy = $this->requestedSortBy($request);
        $listArgs = $this->listArgs(request: $request, defaultLimit: DEFAULT_DATA_LIMIT);

        $products = $this->productService->getList(
            searchValue: $searchValue,
            dataLimit: $listArgs['limit'],
            page: $listArgs['offset'],
            filters: $this->listFilters($request, $state),
            sortBy: $sortBy,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductListResource::collection($products->getCollection())->resolve(),
                'params' => $this->paramsFormatter([
                    'state' => $state,
                    'search' => $searchValue,
                    'filter_sort_by' => $sortBy,
                ]),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    private function requestedState(Request $request): string
    {
        $state = (string) $request->query('state', ProductRetailService::REQUEST_STATE_ALL);

        return in_array($state, self::SELLER_STATES, true) ? $state : ProductRetailService::REQUEST_STATE_ALL;
    }

    private function requestedSortBy(Request $request): string
    {
        $sortBy = (string) $request->query('filter_sort_by', '');

        return in_array($sortBy, self::SORT_KEYS, true) ? $sortBy : ProductRetailService::SORT_LATEST;
    }

    private function listFilters(ListRequest $request, string $state): array
    {
        return $this->productService->listFilters(
            state: $state,
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            sellerId: $this->sellerId($request),
            catalogFilters: [
                'brand_id' => $request->selectedIds('filter_brand_ids'),
                'product_type' => $request->selectedValues('filter_product_types'),
                ...$this->getCategoryIdsForFilter($request),
            ],
            statuses: $request->selectedValues('product_status'),
        );
    }

    private function getCategoryIdsForFilter(ListRequest $request): array
    {
        $matchingIds = $this->categoryService->getMatchingCategoryIds(
            categoryIds: $request->selectedIds('filter_category_ids'),
            subCategoryIds: $request->selectedIds('filter_sub_category_ids'),
            subSubCategoryIds: $request->selectedIds('filter_sub_sub_category_ids'),
        );

        return array_filter([
            'filter_category_ids' => $matchingIds['category_ids'],
            'filter_sub_category_ids' => $matchingIds['sub_category_ids'],
            'filter_sub_sub_category_ids' => $matchingIds['sub_sub_category_ids'],
            'filter_direct_category_ids' => $matchingIds['direct_category_ids'],
            'filter_direct_sub_category_ids' => $matchingIds['direct_sub_category_ids'],
        ]);
    }

    #[ApiDoc(
        summary: 'Create a retail product for the authenticated seller',
        tag: 'Products (Seller)',
        status: [422],
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);

        $product = $this->productService->create(
            data: $request->all(),
            files: $request->allFiles(),
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            userId: $sellerId,
            shopId: $this->shopService->findBy(params: ['seller_id' => $sellerId])?->id ?? 0,
            colorNames: $this->colorService->getNameMap(),
            authorIds: $this->resolveDigitalAuthorIds($request),
            publishingHouseIds: $this->resolveDigitalPublishingHouseIds($request),
            taxIds: $request['tax_ids'] ?? [],
        );

        return $this->responseFormatter(
            config: config('response.default_store_200'),
            content: ['data' => new ProductResource($product)],
        );
    }

    #[ApiDoc(
        summary: 'Update a retail product owned by the authenticated seller',
        tag: 'Products (Seller)',
        status: [404, 422],
    )]
    public function update(UpdateRequest $request, mixed $id): JsonResponse
    {
        $product = $this->sellerProduct($request, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $this->productService->update(
            id: $product->id,
            data: $request->all(),
            files: $request->allFiles(),
            updateBy: ProductRetailService::ADDED_BY_SELLER,
            colorNames: $this->colorService->getNameMap(),
            authorIds: $this->resolveDigitalAuthorIds($request),
            publishingHouseIds: $this->resolveDigitalPublishingHouseIds($request),
            taxIds: $request['tax_ids'] ?? [],
        );

        $updatedProduct = $this->productService->find(id: $product->id, relations: ['retailDetail']);

        $this->updateRestockRequestListAndNotify(product: $product, updatedProduct: $updatedProduct);
        $this->deactivateClearanceSaleBelowFlatDiscount(product: $updatedProduct);

        CartManager::updateProductShippingCost(cartList: $this->cartService->getCartsForProduct(productId: $product->id));

        return $this->responseFormatter(
            config: config('response.data_updated'),
            content: ['data' => new ProductResource($updatedProduct)],
        );
    }

    #[ApiDoc(
        summary: 'Delete one image from a retail product owned by the authenticated seller',
        tag: 'Products (Seller)',
        status: [404, 422],
    )]
    public function deleteImage(DeleteImageRequest $request, mixed $id): JsonResponse
    {
        $product = $this->sellerProduct($request, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        if (count(json_decode($product->getRawOriginal('images'), true) ?: []) < 2) {
            return $this->responseFormatter(
                config: config('response.unprocessable_entity_422'),
                errors: [['error_code' => 'images', 'message' => translate('You cannot delete all images')]],
            );
        }

        $isDeleted = $this->productService->deleteImage(id: $product->id, imageName: $request['name']);

        if (! $isDeleted) {
            return $this->responseFormatter(
                config: config('response.data_not_found'),
                errors: [['error_code' => 'name', 'message' => translate('Image not found')]],
            );
        }

        return $this->responseFormatter(
            config: config('response.default_delete_200'),
            content: ['data' => new ProductResource($this->sellerProduct($request, $id))],
        );
    }

    #[ApiDoc(
        summary: 'Generate variation combinations for the in-progress product form',
        tag: 'Products (Seller)',
    )]
    public function getVariationCombinations(Request $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $this->productService->getSkuCombinations(
                    data: $request->all(),
                    product: $request['id'] ? $this->sellerProduct($request, $request['id']) : null,
                    colorNames: $this->colorService->getNameMap(),
                ),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Details of a retail product owned by the authenticated seller',
        tag: 'Products (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function getDetails(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findBy(
            filters: $this->sellerProductFilters($request, $id),
            relations: [
                'translations',
                'reviews',
                'retailDetail',
                'seoInfo',
                'digitalProductAuthors',
                'digitalProductPublishingHouse',
                'clearanceSale' => fn (HasOne $clearanceSale) => $clearanceSale->active(),
                'taxVats' => fn (MorphMany $taxVats) => $taxVats->with(['tax'])->whereHas('tax', fn ($tax) => $tax->where('is_active', 1)),
            ],
        );

        if ($product === null) {
            return $this->notFoundResponse();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ProductEditResource($this->withEditPayloadAttributes($product))],
        );
    }

    #[ApiDoc(
        summary: 'Edit form data of a retail product owned by the authenticated seller',
        tag: 'Products (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function getEditData(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findBy(
            filters: $this->sellerProductFilters($request, $id),
            relations: [
                'translations',
                'reviews',
                'retailDetail',
                'tags',
                'digitalVariation',
                'seoInfo',
                'clearanceSale' => fn (HasOne $clearanceSale) => $clearanceSale->active(),
            ],
        );

        if ($product === null) {
            return $this->notFoundResponse();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ProductEditResource($this->withEditPayloadAttributes($product))],
        );
    }

    #[ApiDoc(
        summary: 'Gallery and color images of a retail product owned by the authenticated seller',
        tag: 'Products (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function getImages(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findSellerProductBy(sellerId: (int) $this->sellerId($request), filters: ['id' => $id]);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => [
                    'images' => json_decode((string) $product->getRawOriginal('images'), true),
                    'color_image' => json_decode((string) $product->retailDetail?->color_image, true),
                    'images_full_url' => $product->images_full_url,
                    'color_images_full_url' => $product->color_images_full_url,
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Activate or deactivate a retail product owned by the authenticated seller',
        description: 'A product that is pending or denied cannot be activated.',
        tag: 'Products (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->findSellerProductBy(sellerId: (int) $this->sellerId($request), filters: ['id' => $id]);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $status = $request->boolean('status');

        if (! $this->productService->activate(id: $product->id, status: $status)) {
            return $this->responseFormatter(
                config: config('response.product_status_update_not_allowed'),
                errors: [['error_code' => 'status', 'message' => translate('Product must be approved before it can be activated')]],
            );
        }

        return $this->responseFormatter(
            config: config('response.product_status_updated_successfully'),
            content: ['data' => ['id' => $product->id, 'status' => (int) $status]],
        );
    }

    #[ApiDoc(
        summary: 'Delete a retail product owned by the authenticated seller',
        description: 'Removes the product images, preview file, retail detail and translations, and its flash deal and deal of the day entries.',
        tag: 'Products (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->findSellerProductBy(sellerId: (int) $this->sellerId($request), filters: ['id' => $id]);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        DB::transaction(function () use ($product): void {
            $this->productService->delete(id: $product->id);
            $this->flashDealProductService->deleteBy(params: ['product_id' => $product->id]);
            $this->dealOfTheDayService->deleteBy(params: ['product_id' => $product->id]);
        });

        return $this->responseFormatter(config: config('response.product_deleted_successfully'));
    }

    private function sellerProductFilters(Request $request, int $id): array
    {
        return [
            'id' => $id,
            'user_id' => $this->sellerId($request),
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
        ];
    }

    private function withEditPayloadAttributes(Model $product): Model
    {
        return $product
            ->setAttribute('reviews_count', $product->reviews->count())
            ->setAttribute('colors_formatted', $this->productService->getFormattedColors(
                product: $product,
                colorNames: $this->colorService->getNameMap(),
            ));
    }

    private function sellerId(Request $request): ?int
    {
        return $request->attributes->get('seller')?->id;
    }

    private function sellerProduct(Request $request, mixed $id): ?Model
    {
        return $this->productService->findBy(
            filters: [
                'id' => $id,
                'user_id' => $this->sellerId($request),
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
            ],
            relations: ['translations', 'reviews', 'retailDetail'],
        );
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_not_found'),
            errors: [['error_code' => 'product', 'message' => translate('Product not found')]],
        );
    }
}
