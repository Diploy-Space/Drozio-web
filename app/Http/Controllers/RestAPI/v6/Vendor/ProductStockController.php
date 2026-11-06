<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Product\BarcodeGenerateRequest;
use App\Http\Requests\Vendor\Product\QuantityUpdateRequest;
use App\Http\Resources\V6\Vendor\ProductListResource;
use App\Services\New\ProductRetailService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductStockController extends BaseApiController
{
    private const STOCK_OUT_RELATIONS = ['seoInfo', 'digitalProductAuthors', 'digitalProductPublishingHouse', 'clearanceSale'];

    private const STATUS_ONE_PRODUCT = 'one_product';

    private const STATUS_MULTIPLE_PRODUCT = 'multiple_product';

    private const BARCODE_VIEWS = [
        ProductRetailService::BARCODE_LAYOUT_DEFAULT => 'vendor-views.product.barcode-pdf',
        ProductRetailService::BARCODE_LAYOUT_COMPACT => 'vendor-views.product.barcode-pdf-compact',
    ];

    private const COMPACT_LABEL_PAPER_POINTS = [0, 0, 111.6, 61.2];

    private const BARCODE_FILE = 'product/barcode.pdf';

    public function __construct(
        private readonly ProductRetailService $productService,
    ) {}

    #[ApiDoc(
        summary: 'Approved physical products of the authenticated seller below the stock limit',
        description: 'The limit is the seller stock limit, or the business stock limit when the seller has none. A limit of zero lists products with no stock.',
        tag: 'Product Stock (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401],
        guard: 'seller',
    )]
    public function getStockOutList(Request $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset']);
        $seller = $request->attributes->get('seller');

        $products = $this->productService->getStockLimitList(
            stockLimit: $this->stockLimit(sellerStockLimit: $seller['stock_limit']),
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            relations: [...self::STOCK_OUT_RELATIONS, 'clearanceSale' => fn ($query) => $query->active()],
            withCount: ['reviews'],
            filters: [
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
                'seller_id' => (int) $seller->id,
                'request_status' => ProductRetailService::REQUEST_STATUS_APPROVED,
                'product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL,
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductListResource::collection($products->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Whether one or several approved physical products of the authenticated seller are below the stock limit',
        tag: 'Product Stock (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getStockLimitStatus(Request $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $products = $this->productService->getSellerLowStockProducts(
            sellerId: (int) $seller->id,
            stockLimit: $this->stockLimit(sellerStockLimit: $seller['stock_limit'] ?? 0),
        );

        $data = $products->count() === 1
            ? ['status' => self::STATUS_ONE_PRODUCT, 'product_count' => 1, 'product' => ProductListResource::make($products->first())->resolve()]
            : ['status' => self::STATUS_MULTIPLE_PRODUCT, 'product_count' => $products->count()];

        return $this->responseFormatter(config: config('response.data_fetched'), content: ['data' => $data]);
    }

    #[ApiDoc(
        summary: 'Set the stock and variation-wise stock of a product owned by the authenticated seller',
        description: 'variation accepts a list or a JSON string of type, price, sku and qty. Customers waiting on a restock are notified when stock returns.',
        tag: 'Product Stock (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function updateQuantity(QuantityUpdateRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->findSellerProductBy(
            sellerId: (int) $request->attributes->get('seller')->id,
            filters: ['id' => $id],
            relations: ['retailDetail'],
        );

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $updatedProduct = $this->productService->updateStockAndVariation(
            id: $product->id,
            currentStock: (int) $request->validated('current_stock'),
            variation: $request->variations(),
        );

        if ($updatedProduct === null) {
            return $this->notFoundResponse();
        }

        $this->productService->updateRestockRequestListAndNotify(product: $product, updatedProduct: $updatedProduct);

        return $this->responseFormatter(
            config: config('response.product_quantity_updated'),
            content: ['data' => ProductListResource::make($updatedProduct)->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Render a barcode PDF sheet for a product owned by the authenticated seller',
        description: 'Returns the URL of the generated PDF.',
        tag: 'Product Stock (Seller)',
        query: [
            'quantity' => 'required|integer|min:1|max:270',
            'layout' => 'nullable|string|in:default,compact|default:default',
        ],
        hints: [
            'quantity' => 'Number of labels to print.',
            'layout' => 'default: A4 sheet of labels with shop name, product name, price, bars and code. compact: one 1.55in x 0.85in sticker per page with bars and code only, same as the Compact label option in the vendor panel.',
        ],
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function generateBarcode(BarcodeGenerateRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->findSellerProductBy(
            sellerId: (int) $request->attributes->get('seller')->id,
            filters: ['id' => $id],
            relations: ['seller.shop'],
            withCount: ['reviews'],
        );

        if ($product === null) {
            return $this->notFoundResponse();
        }

        if (! isset($product->code)) {
            return $this->responseFormatter(
                config: config('response.product_barcode_code_missing'),
                errors: [['error_code' => 'code', 'message' => translate('Please update product code!')]],
            );
        }

        $sheet = $this->productService->getBarcodeSheet(
            quantity: (int) $request->validated('quantity'),
            layout: $request->validated('layout'),
        );

        $pdf = app()->make(PDF::class);
        $pdf->loadView(self::BARCODE_VIEWS[$sheet['layout']], [
            'product' => $product,
            'quantity' => (int) $request->validated('quantity'),
            'barcodes' => $sheet['pages'],
        ]);

        if ($sheet['layout'] === ProductRetailService::BARCODE_LAYOUT_COMPACT) {
            $pdf->setPaper(self::COMPACT_LABEL_PAPER_POINTS);
        }

        $pdf->save(storage_path('app/public/'.self::BARCODE_FILE));

        return $this->responseFormatter(
            config: config('response.product_barcode_generated'),
            content: ['data' => asset('storage/app/public/'.self::BARCODE_FILE)],
        );
    }

    private function stockLimit(mixed $sellerStockLimit): int
    {
        return (int) ($sellerStockLimit <= 0 ? getWebConfig(name: 'stock_limit') : $sellerStockLimit);
    }

    private function notFoundResponse(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_not_found'),
            errors: [['error_code' => 'product', 'message' => translate('Product not found')]],
        );
    }
}
