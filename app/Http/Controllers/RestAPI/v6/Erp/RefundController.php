<?php

namespace App\Http\Controllers\RestAPI\v6\Erp;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Erp\Refund\ListRequest;
use App\Http\Resources\V6\Erp\RefundResource;
use App\Services\New\RefundRequestService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RefundController extends BaseApiController
{
    private const DEFAULT_LIMIT = 10;

    private const RELATIONS = [
        'order:id,order_status,order_amount,seller_id,customer_id',
        'order.seller.shop',
        'order.customer:id,f_name,l_name,email,phone',
    ];

    public function __construct(
        private readonly RefundRequestService $refundRequestService,
    ) {}

    #[ApiDoc(
        summary: 'Refund requests with their order, store and customer, oldest first',
        tag: 'ERP Integration',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 422],
        guard: 'erp',
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset']);

        $refunds = $this->refundRequestService->getList(
            dataLimit: (int) ($args['limit'] ?? self::DEFAULT_LIMIT),
            page: (int) ($args['offset'] ?? 1),
            relations: self::RELATIONS,
            sortBy: RefundRequestService::SORT_OLDEST_ID,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => RefundResource::collection($refunds->getCollection())->resolve(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($refunds),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Total number of refund requests',
        tag: 'ERP Integration',
        status: [401, 422],
        guard: 'erp',
    )]
    public function count(ListRequest $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => ['total_refund_count' => $this->refundRequestService->count()]],
        );
    }

    #[ApiDoc(
        summary: 'One refund request with its order, store and customer',
        tag: 'ERP Integration',
        status: [401, 404],
        guard: 'erp',
    )]
    public function find(int $id): JsonResponse
    {
        $refund = $this->refundRequestService->find(id: $id, relations: self::RELATIONS);

        if ($refund === null) {
            return $this->responseFormatter(
                config: config('response.data_not_found'),
                errors: [['error_code' => 'refund', 'message' => translate('Refund not found')]],
            );
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => RefundResource::make($refund)->resolve()],
        );
    }
}
