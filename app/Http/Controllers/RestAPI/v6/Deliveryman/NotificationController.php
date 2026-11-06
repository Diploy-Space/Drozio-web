<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Deliveryman\NotificationResource;
use App\Services\New\DeliverymanNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class NotificationController extends BaseApiController
{
    public function __construct(private readonly DeliverymanNotificationService $deliverymanNotificationService) {}

    #[ApiDoc(
        summary: 'Notifications for the signed-in delivery man',
        description: 'There is no seen state on these — the table has no read column, so the app tracks that itself.',
        tag: 'Delivery-man',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403],
        guard: 'deliveryman',
    )]
    public function getList(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $notifications = $this->deliverymanNotificationService->getList(
            dataLimit: $limit,
            page: $offset,
            relations: ['order:id,order_status,business_module'],
            filters: ['delivery_man_id' => $deliveryman->id],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => NotificationResource::collection($notifications),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($notifications),
            ],
        );
    }
}
