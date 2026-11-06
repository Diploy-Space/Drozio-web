<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\ClearanceSaleProductResource;
use App\Http\Resources\V6\Customer\ClearanceSaleResource;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ClearanceSaleController extends BaseApiController
{
    public function __construct(
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly StockClearanceProductService $stockClearanceProductService,
    ) {}

    #[ApiDoc(
        summary: 'The running clearance sale setup',
        tag: 'Clearance Sale',
        status: [404],
    )]
    public function getSetup(): JsonResponse
    {
        $setup = $this->stockClearanceSetupService->findBy(
            params: ['setup_by' => StockClearanceSetupService::SETUP_BY_ADMIN],
            relations: [],
        );

        if ($setup === null || ! $setup->is_active) {
            return $this->responseFormatter(config: config('response.clearance_sale_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ClearanceSaleResource($setup)],
        );
    }

    #[ApiDoc(
        summary: 'Products currently on clearance',
        tag: 'Clearance Sale',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
    )]
    public function getProducts(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $products = $this->stockClearanceProductService->getRunningProducts(
            dataLimit: $limit,
            page: $offset,
            relations: ['product.seller.shop'],
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
}
