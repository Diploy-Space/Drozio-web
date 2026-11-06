<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Enums\WebConfigKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\AddRequest;
use App\Http\Requests\Admin\Notification\UpdateRequest;
use App\Services\New\NotificationService;
use App\Traits\PushNotificationTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use PushNotificationTrait;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $notifications = $this->notificationService->getList(
            searchValue: $request->get('searchValue'),
            dataLimit: getWebConfig(WebConfigKey::PAGINATION_LIMIT),
            filters: ['sent_to' => NotificationService::AUDIENCE_CUSTOMER],
        );

        return view('admin-views.notification.index', compact('notifications'));
    }

    public function add(AddRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Push notification is disabled for demo'));

            return back();
        }

        $notification = $this->notificationService->create(
            data: $request->validated(),
            image: $request->file('image'),
        );

        $this->push($notification)
            ? ToastMagic::success(translate('Notification sent successfully'))
            : ToastMagic::warning(translate('Push notification failed'), $this->pushFailureDescription());

        return back();
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $notification = $this->notificationService->find(id: $id);

        if ($notification === null) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.notification.index');
        }

        return view('admin-views.notification.update-view', compact('notification'));
    }

    public function update(UpdateRequest $request, string|int $id): RedirectResponse
    {
        $notification = $this->notificationService->update(
            id: $id,
            data: $request->validated(),
            image: $request->file('image'),
        );

        if ($notification === null) {
            ToastMagic::error(translate('Not found'));
        } elseif (config('app.mode') === 'demo' || ! $notification['status'] || $this->push($notification)) {
            ToastMagic::success(translate('Notification updated successfully'));
        } else {
            ToastMagic::warning(translate('Push notification failed'), $this->pushFailureDescription());
        }

        return redirect()->route('admin.retail.notification.index');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $this->notificationService->updateStatus(id: $request['id'], status: (bool) $request['status']);

        return response()->json([
            'message' => translate(':item updated successfully', ['item' => translate('Status')]),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $this->notificationService->delete(id: $request['id']);

        return response()->json();
    }

    public function resendNotification(Request $request): JsonResponse
    {
        if (config('app.mode') === 'demo') {
            return response()->json([
                'success' => false,
                'title' => translate('Push notification failed'),
                'message' => translate('Push notification is disabled for demo'),
            ]);
        }

        $notification = $this->notificationService->find(id: $request['id']);

        if ($notification === null) {
            return response()->json([
                'success' => false,
                'title' => translate('Push notification failed'),
                'message' => translate('Notification not found'),
            ]);
        }

        if (! $this->push($notification)) {
            return response()->json([
                'success' => false,
                'title' => translate('Push notification failed'),
                'message' => $this->pushFailureDescription(),
            ]);
        }

        $this->notificationService->resend(id: $notification->id);

        return response()->json([
            'success' => true,
            'title' => translate('Push notification sent'),
            'message' => translate('The notification was sent to your customers again'),
        ]);
    }

    private function pushFailureDescription(): string
    {
        return $this->hasPushCredentials()
            ? translate('Firebase did not accept the notification. Check the Firebase credentials and try again')
            : translate('Firebase is not configured yet. Add the service account credentials in Firebase configuration and try again');
    }

    private function push(?Model $notification): bool
    {
        if ($notification === null) {
            return false;
        }

        try {
            $payload = $notification->toArray();
            $payload['type'] = 'notification';
            $payload['image'] = getStorageImages(path: $notification->image_full_url, type: 'backend-basic');

            return $this->isPushDelivered(response: $this->sendPushNotificationToTopic($payload, $this->notificationService->pushTopic()));
        } catch (\Exception) {
            return false;
        }
    }
}
