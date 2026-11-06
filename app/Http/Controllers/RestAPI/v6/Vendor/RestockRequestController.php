<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\WebConfigKey;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\RestockRequest\ListRequest;
use App\Http\Requests\Vendor\RestockRequest\StockUpdateRequest;
use App\Http\Resources\V6\Vendor\ProductListResource;
use App\Http\Resources\V6\Vendor\RestockRequestBrandResource;
use App\Http\Resources\V6\Vendor\RestockRequestResource;
use App\Services\New\BrandService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductCustomerService;
use App\Services\New\RestockProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RestockRequestController extends BaseApiController
{
    private const BRAND_COUNTS = ['brandAllProducts', 'brandProductOrderDetails'];

    public function __construct(
        private readonly RestockProductService $restockProductService,
        private readonly RestockProductCustomerService $restockProductCustomerService,
        private readonly ProductRetailService $productService,
        private readonly BrandService $brandService,
    ) {}

    #[ApiDoc(
        summary: 'Customer restock requests on the authenticated seller\'s products',
        description: 'brand_ids accepts a list or a JSON string. The date range applies only when both dates are sent.',
        tag: 'Restock Request (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'brand_ids' => 'nullable|array',
            'restock_start_date' => 'nullable|date',
            'restock_end_date' => 'nullable|date',
        ],
        status: [401, 422],
        guard: 'seller',
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset', 'search', 'category_id', 'brand_ids', 'restock_start_date', 'restock_end_date']);
        $sellerId = $this->sellerId(request: $request);
        $search = $request->validated('search');

        $filters = [
            RestockProductService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_SELLER,
            RestockProductService::FILTER_SELLER_ID => $sellerId,
            RestockProductService::FILTER_CATEGORY_ID => $request->validated('category_id'),
            RestockProductService::FILTER_BRAND_IDS => $request->brandIds(),
            RestockProductService::FILTER_DATE_RANGE => $request->dateRange(),
        ];

        if (! empty($search)) {
            $filters[RestockProductService::FILTER_PRODUCT_IDS] = $this->productService->getSellerRestockSearchIds(
                sellerId: $sellerId,
                searchValue: $search,
            );
        }

        $restockRequests = $this->restockProductService->getList(
            dataLimit: (int) ($args['limit'] ?? getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)),
            page: (int) ($args['offset'] ?? 1),
            relations: ['product.retailDetail'],
            withCount: ['restockProductCustomers'],
            filters: $filters,
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        );

        $variantsByProduct = $this->restockProductService->getVariantsByProductIds(
            productIds: $restockRequests->getCollection()->pluck('product_id')->unique()->values()->all(),
        );

        $restockRequests->getCollection()->each(function ($restockRequest) use ($variantsByProduct): void {
            $restockRequest->variant_keys = $variantsByProduct[$restockRequest->product_id] ?? [];
        });

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => RestockRequestResource::collection($restockRequests->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($restockRequests),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Brands of the authenticated seller\'s products that have restock requests, with a request count per brand',
        tag: 'Restock Request (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getBrands(Request $request): JsonResponse
    {
        $restockRequests = $this->restockProductService->getList(
            dataLimit: 'all',
            relations: ['product'],
            filters: [
                RestockProductService::FILTER_ADDED_BY => ProductRetailService::ADDED_BY_SELLER,
                RestockProductService::FILTER_SELLER_ID => $this->sellerId(request: $request),
            ],
            sortBy: RestockProductService::SORT_RECENTLY_UPDATED,
        );

        $brands = $this->brandService->getList(
            dataLimit: 'all',
            withCount: self::BRAND_COUNTS,
            filters: ['id' => $this->productService->getBrandIds(productIds: $restockRequests->pluck('product_id')->unique()->values()->all())],
            sortBy: BrandService::SORT_OLDEST_ID,
        );

        $brands->each(function ($brand) use ($restockRequests): void {
            $brand->product_count = $restockRequests
                ->filter(fn ($restockRequest) => $restockRequest->product?->brand_id === $brand->id)
                ->count();
        });

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => RestockRequestBrandResource::collection($brands)->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Restock the product behind a restock request of the authenticated seller and notify the requesters',
        description: 'variation accepts a list or a JSON string of type, price, sku and qty. Fulfilled restock requests are removed.',
        tag: 'Restock Request (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function updateStock(StockUpdateRequest $request, int $id): JsonResponse
    {
        $sellerId = $this->sellerId(request: $request);

        $restockRequest = $this->restockProductService->findBy(
            params: ['id' => $id, RestockProductService::FILTER_SELLER_ID => $sellerId],
        );

        $product = $restockRequest === null ? null : $this->productService->findSellerProductBy(
            sellerId: $sellerId,
            filters: ['id' => $restockRequest->product_id],
            relations: ['retailDetail'],
        );

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $updatedProduct = DB::transaction(function () use ($product, $request) {
            $updatedProduct = $this->productService->updateStockAndVariation(
                id: $product->id,
                currentStock: (int) $request->validated('current_stock'),
                variation: $request->variations(),
            );

            if ($updatedProduct !== null) {
                $this->productService->updateRestockRequestListAndNotify(product: $product, updatedProduct: $updatedProduct);
            }

            return $updatedProduct;
        });

        if ($updatedProduct === null) {
            return $this->notFoundResponse();
        }

        return $this->responseFormatter(
            config: config('response.seller_restock_request_stock_updated'),
            content: ['data' => ProductListResource::make($updatedProduct)->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Remove a restock request on the authenticated seller\'s product, with every customer waiting on it',
        tag: 'Restock Request (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, int $id): JsonResponse
    {
        $restockRequest = $this->restockProductService->findBy(
            params: ['id' => $id, RestockProductService::FILTER_SELLER_ID => $this->sellerId(request: $request)],
        );

        if ($restockRequest === null) {
            return $this->notFoundResponse();
        }

        DB::transaction(function () use ($restockRequest): void {
            $this->restockProductService->deleteBy(params: ['id' => $restockRequest->id]);
            $this->restockProductCustomerService->deleteBy(params: ['restock_product_id' => $restockRequest->id]);
        });

        return $this->responseFormatter(config: config('response.seller_restock_request_removed'));
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.seller_restock_request_not_found'),
            errors: [['error_code' => 'restock_request', 'message' => translate('Restock request not found')]],
        );
    }
}
