<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Location\RecordRequest;
use App\Services\New\DeliveryHistoryService;
use App\Services\New\OrderService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class LocationController extends BaseApiController
{
    public function __construct(
        private readonly DeliveryHistoryService $deliveryHistoryService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'Record a location ping against an assigned order',
        description: 'The order must be assigned to the signed-in delivery man; each call appends a new row.',
        tag: 'Delivery-man',
        status: [201, 401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function record(RecordRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $order = $this->orderService->findBy(params: [
            'id' => $request['order_id'],
            'delivery_man_id' => $deliveryman->id,
        ]);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_assigned'));
        }

        $this->deliveryHistoryService->create(data: $request->validated() + [
            'deliveryman_id' => $deliveryman->id,
        ]);

        return $this->responseFormatter(config: config('response.location_recorded'));
    }
}
