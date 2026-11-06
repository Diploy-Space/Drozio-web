<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\Notification\ModalViewRequest;
use App\Services\New\NotificationSeenService;
use App\Services\New\NotificationService;
use App\Services\New\ShopService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly ShopService $shopService,
        private readonly NotificationService $notificationService,
        private readonly NotificationSeenService $notificationSeenService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return null;
    }

    public function getNotificationModalView(ModalViewRequest $request): JsonResponse
    {
        $sellerId = auth('seller')->id();
        $shop = $this->shopService->findBy(params: ['seller_id' => $sellerId]);
        $companyName = getWebConfig(name: 'company_name') ?? '';

        $notificationSeen = $this->notificationSeenService->findBy(params: [
            'seller_id' => $sellerId,
            'notification_id' => $request['id'],
        ]);

        if ($notificationSeen !== null) {
            $this->notificationSeenService->update(id: $notificationSeen['id'], data: ['created_at' => now()]);
        }

        $data = $this->notificationService->find(id: $request['id']);

        $notificationCount = $this->notificationService->count(filters: [
            'sent_to' => NotificationService::AUDIENCE_SELLER,
            NotificationService::FILTER_SINCE => auth('seller')->user()->created_at,
            NotificationService::FILTER_UNSEEN => true,
        ]);

        return response()->json([
            'notification_count' => $notificationCount,
            'view' => view('vendor-views.partials.notification-modal', compact('shop', 'companyName', 'data'))->render(),
        ]);
    }
}
