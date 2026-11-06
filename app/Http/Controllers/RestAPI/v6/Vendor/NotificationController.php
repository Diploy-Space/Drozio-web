<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

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
        summary: 'Broadcast notifications for the authenticated seller',
        tag: 'Notification (Seller)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer'],
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $seller = $request->attributes->get('seller');

        $notifications = $this->notificationService->getBroadcastList(
            audience: NotificationService::AUDIENCE_SELLER,
            dataLimit: $limit,
            page: $offset,
            sellerId: $seller?->id,
            joinedAt: $seller?->created_at,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => NotificationResource::collection($notifications),
                'new_notification' => $this->unseenCount($seller),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($notifications),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Mark one seller notification seen',
        tag: 'Notification (Seller)',
        status: [404],
    )]
    public function markSeen(Request $request, string|int $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        if ($this->notificationService->find(id: $id) === null) {
            return $this->responseFormatter(config: config('response.notification_not_found'));
        }

        $this->notificationSeenService->markSeen(notificationId: $id, sellerId: $seller?->id);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['notification_count' => $this->unseenCount($seller),
            ],
        );
    }

    protected function unseenCount(mixed $seller): int
    {
        return $this->notificationService->getUnseenCount(
            audience: NotificationService::AUDIENCE_SELLER,
            sellerId: $seller?->id,
            joinedAt: $seller?->created_at,
        );
    }
}
