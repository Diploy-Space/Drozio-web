<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Resources\V6\Customer\NotificationResource;
use App\Services\New\NotificationSeenService;
use App\Services\New\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class NotificationController extends BaseApiController
{
    public function __construct(
        protected readonly NotificationService $notificationService,
        protected readonly NotificationSeenService $notificationSeenService,
    ) {}

    #[ApiDoc(
        summary: 'Broadcast notifications for customers',
        tag: 'Notification',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        auth: false,
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $customerId = auth('api')->id();

        $notifications = $this->notificationService->getBroadcastList(
            audience: NotificationService::AUDIENCE_CUSTOMER,
            dataLimit: $limit,
            page: $offset,
            userId: $customerId,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => NotificationResource::collection($notifications),
                'new_notification' => $this->unseenCount($customerId),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($notifications),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Mark one notification seen',
        tag: 'Notification',
        status: [404],
    )]
    public function markSeen(string|int $id): JsonResponse
    {
        $customerId = auth('api')->id();

        if ($this->notificationService->find(id: $id) === null) {
            return $this->responseFormatter(config: config('response.notification_not_found'));
        }

        $this->notificationSeenService->markSeen(notificationId: $id, userId: $customerId);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['notification_count' => $this->unseenCount($customerId)],
        );
    }

    protected function unseenCount(?int $customerId): int
    {
        return $this->notificationService->getUnseenCount(
            audience: NotificationService::AUDIENCE_CUSTOMER,
            userId: $customerId,
        );
    }
}
