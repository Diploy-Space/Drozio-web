<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\ClearanceDiscountType;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\ClearanceSale\ConfigUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductAddRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductDiscountUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\ProductStatusUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\SeoConfigUpdateRequest;
use App\Http\Requests\Vendor\ClearanceSale\StatusUpdateRequest;
use App\Http\Resources\V6\Vendor\ClearanceSaleProductResource;
use App\Http\Resources\V6\Vendor\ClearanceSaleSetupResource;
use App\Http\Resources\V6\Vendor\ProductOptionResource;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\ProductRetailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ClearanceSaleController extends BaseApiController
{
    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly ProductRetailService $productService,
    ) {}

    #[ApiDoc(
        summary: 'The seller own clearance sale setup',
        tag: 'Clearance Sale (Seller)',
        status: [404],
    )]
    public function getSetup(Request $request): JsonResponse
    {
        $setup = $this->setup($request);

        if ($setup === null) {
            return $this->setupMissingResponse();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ClearanceSaleSetupResource($setup)],
        );
    }

    #[ApiDoc(
        summary: 'Create or update the seller clearance sale setup',
        tag: 'Clearance Sale (Seller)',
    )]
    public function updateSetup(ConfigUpdateRequest $request): JsonResponse
    {
        $this->stockClearanceSetupService->updateOrInsert(
            params: $this->setupParams($request),
            data: array_merge($request->validated() + $request->all(), [
                'shop_id' => $this->shopId($request),
            ]),
        );

        $setup = $this->setup($request);

        if ($setup->discount_type === ClearanceDiscountType::FLAT) {
            $this->stockClearanceProductService->applyFlatDiscountToProducts(
                setupId: $setup->id,
                discountAmount: $request['discount_amount'],
            );
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ClearanceSaleSetupResource($this->setup($request)),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update the seller clearance sale meta information',
        tag: 'Clearance Sale (Seller)',
        status: [404],
    )]
    public function updateSeo(SeoConfigUpdateRequest $request): JsonResponse
    {
        $setup = $this->setup($request);

        if ($setup === null) {
            return $this->setupMissingResponse();
        }

        $this->stockClearanceSetupService->update(
            id: $setup->id,
            data: $request->validated() + $request->all(),
            metaImage: $request->file('meta_image'),
        );

        return $this->responseFormatter(config: config('response.meta_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Turn the seller clearance sale on or off',
        tag: 'Clearance Sale (Seller)',
        status: [404],
    )]
    public function updateSetupStatus(StatusUpdateRequest $request): JsonResponse
    {
        $setup = $this->setup($request);

        if ($setup === null) {
            return $this->setupMissingResponse();
        }

        $this->stockClearanceSetupService->updateStatus(id: $setup->id, status: (bool) $request['status']);

        return $this->responseFormatter(config: config('response.data_fetched'));
    }

    #[ApiDoc(
        summary: 'Products the seller has put on clearance',
        tag: 'Clearance Sale (Seller)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
    )]
    public function getProducts(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $products = $this->stockClearanceProductService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['product'],
            filters: [
                'added_by' => StockClearanceSetupService::SETUP_BY_VENDOR,
                'user_id' => $this->vendorId($request),
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ClearanceSaleProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Seller products that can still be added to the clearance sale',
        tag: 'Clearance Sale (Seller)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
    )]
    public function getAvailableProducts(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $vendorId = $this->vendorId($request);

        $products = $this->productService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                ProductRetailService::FILTER_EXCLUDE_IDS => $this->stockClearanceProductService->getProductIds(
                    addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
                    vendorId: $vendorId,
                ),
                'added_by' => 'seller',
                'user_id' => $vendorId,
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductOptionResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add seller products to the clearance sale',
        tag: 'Clearance Sale (Seller)',
        status: [404, 422],
    )]
    public function addProducts(ProductAddRequest $request): JsonResponse
    {
        $setup = $this->setup($request);

        if ($setup === null) {
            return $this->setupMissingResponse();
        }

        $vendorId = $this->vendorId($request);
        $discounts = $request->discountInputs();

        $products = $this->productService->getList(
            dataLimit: 'all',
            filters: [
                'id' => $request->productIds(),
                'added_by' => 'seller',
                'user_id' => $vendorId,
            ],
        );

        if ($products->isEmpty()) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        $result = $this->stockClearanceProductService->attachMany(
            candidates: $products->map(fn (Model $product): array => [
                'product' => $product,
                'discount_type' => $discounts[$product->id]['discount_type'] ?? null,
                'discount_amount' => $discounts[$product->id]['discount_amount'] ?? null,
            ])->all(),
            config: $setup,
            addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
            vendorId: $vendorId,
            shopId: $this->shopId($request),
        );

        if ($result['reason'] !== null) {
            return $this->responseFormatter(config: $this->discountConfig($result['reason']));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['total_added' => $result['attached'],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update the discount of a clearance sale product',
        tag: 'Clearance Sale (Seller)',
        status: [404, 422],
    )]
    public function updateProductDiscount(ProductDiscountUpdateRequest $request): JsonResponse
    {
        $setup = $this->setup($request);

        if ($setup === null) {
            return $this->setupMissingResponse();
        }

        $vendorId = $this->vendorId($request);
        $clearanceProduct = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $request['product_id'], 'user_id' => $vendorId],
            relations: ['product'],
        );

        if ($clearanceProduct === null || $clearanceProduct->product === null) {
            return $this->responseFormatter(config: config('response.stock_clearance_product_not_found'));
        }

        $reason = $this->stockClearanceProductService->validateDiscountUpdate(
            product: $clearanceProduct->product,
            config: $setup,
            discountType: $request['discount_type'],
            discountAmount: $request['discount_amount'],
        );

        if ($reason !== null) {
            return $this->responseFormatter(config: $this->discountConfig($reason));
        }

        $this->stockClearanceProductService->updateDiscount(
            productId: $clearanceProduct->product_id,
            discountType: $request['discount_type'],
            discountAmount: $request['discount_amount'],
            vendorId: $vendorId,
        );

        return $this->responseFormatter(config: config('response.data_fetched'));
    }

    #[ApiDoc(
        summary: 'Turn a clearance sale product on or off',
        tag: 'Clearance Sale (Seller)',
        status: [404, 422],
    )]
    public function updateProductStatus(ProductStatusUpdateRequest $request): JsonResponse
    {
        $vendorId = $this->vendorId($request);
        $status = (bool) $request['status'];

        $clearanceProduct = $this->stockClearanceProductService->findBy(
            params: ['product_id' => $request['product_id'], 'user_id' => $vendorId],
            relations: ['setup', 'product'],
        );

        if ($clearanceProduct === null) {
            return $this->responseFormatter(config: config('response.stock_clearance_product_not_found'));
        }

        if ($status && ! $this->stockClearanceProductService->isDiscountBelowProductPrice($clearanceProduct)) {
            return $this->responseFormatter(config: config('response.your_products_unit_price_is_lower_then_offer_pri'));
        }

        $this->stockClearanceProductService->updateStatus(
            productId: $clearanceProduct->product_id,
            status: $status,
            vendorId: $vendorId,
        );

        return $this->responseFormatter(config: config('response.data_fetched'));
    }

    #[ApiDoc(
        summary: 'Remove one product from the clearance sale',
        tag: 'Clearance Sale (Seller)',
        status: [404],
    )]
    public function deleteProduct(Request $request, string|int $productId): JsonResponse
    {
        $isDeleted = $this->stockClearanceProductService->detach(
            productId: $productId,
            vendorId: $this->vendorId($request),
        );

        if (! $isDeleted) {
            return $this->responseFormatter(config: config('response.stock_clearance_product_not_found'));
        }

        return $this->responseFormatter(config: config('response.data_fetched'));
    }

    #[ApiDoc(
        summary: 'Remove every product from the seller clearance sale',
        tag: 'Clearance Sale (Seller)',
    )]
    public function deleteAllProducts(Request $request): JsonResponse
    {
        $this->stockClearanceProductService->detachAll(
            addedBy: StockClearanceSetupService::SETUP_BY_VENDOR,
            vendorId: $this->vendorId($request),
        );

        return $this->responseFormatter(config: config('response.all_stock_clearance_products_removed_successfull'));
    }

    private function vendorId(Request $request): ?int
    {
        return $request->attributes->get('seller')?->id;
    }

    private function shopId(Request $request): ?int
    {
        return $request->attributes->get('seller')?->shop?->id;
    }

    /** @return array<string, mixed> */
    private function setupParams(Request $request): array
    {
        return [
            'setup_by' => StockClearanceSetupService::SETUP_BY_VENDOR,
            'user_id' => $this->vendorId($request),
        ];
    }

    private function setup(Request $request): ?Model
    {
        return $this->stockClearanceSetupService->findBy(params: $this->setupParams($request));
    }

    private function setupMissingResponse(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.please_setup_the_configuration_first'));
    }

    /** @return array<string, mixed> */
    private function discountConfig(string $reason): array
    {
        return config('response.'.match ($reason) {
            StockClearanceProductService::REASON_DISCOUNT_MISSING => 'discount_amount_required',
            StockClearanceProductService::REASON_DISCOUNT_NOT_POSITIVE => 'discount_amount_not_positive',
            StockClearanceProductService::REASON_PERCENTAGE_OUT_OF_RANGE => 'discount_percentage_out_of_range',
            StockClearanceProductService::REASON_ABOVE_UNIT_PRICE => 'discount_above_unit_price',
            StockClearanceProductService::REASON_FLAT_SETUP_MANAGED => 'discount_managed_by_flat_setup',
            default => 'discount_above_variation_price',
        });
    }
}
