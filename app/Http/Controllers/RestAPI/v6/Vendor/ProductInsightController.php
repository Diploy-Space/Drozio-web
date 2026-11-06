<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Vendor\ProductListResource;
use App\Services\New\ProductRetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductInsightController extends BaseApiController
{
    public function __construct(
        private readonly ProductRetailService $productService,
    ) {}

    #[ApiDoc(
        summary: 'Approved products of the authenticated seller ranked by delivered quantity sold',
        description: 'Only products with a delivered, non-refunded order line are listed.',
        tag: 'Product Insights (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401],
        guard: 'seller',
    )]
    public function getTopSelling(Request $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset']);

        $products = $this->productService->getSellerTopSellingList(
            sellerId: (int) $request->attributes->get('seller')->id,
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            relations: ['clearanceSale' => fn ($relation) => $relation->active()],
        );

        $rows = $products->getCollection()->map(fn ($product) => [
            'product_id' => $product->id,
            'count' => (string) ($product->order_details_count ?? 0),
            'product' => ProductListResource::make($product)->resolve(),
        ])->values();

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $rows,
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Approved products of the authenticated seller ranked by customer review count',
        description: 'Only products with at least one customer review are listed.',
        tag: 'Product Insights (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401],
        guard: 'seller',
    )]
    public function getMostPopular(Request $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset']);

        $products = $this->productService->getSellerMostPopularList(
            sellerId: (int) $request->attributes->get('seller')->id,
            dataLimit: max(1, (int) ($args['limit'] ?? getWebConfig(name: 'pagination_limit'))),
            page: max(1, (int) ($args['offset'] ?? 1)),
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
}
