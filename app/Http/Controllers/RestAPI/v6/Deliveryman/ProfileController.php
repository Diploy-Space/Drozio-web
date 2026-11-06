<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Profile\BankInfoRequest;
use App\Http\Requests\Deliveryman\Profile\DeviceTokenUpdateRequest;
use App\Http\Requests\Deliveryman\Profile\OnlineStatusRequest;
use App\Http\Requests\Deliveryman\Profile\UpdateRequest;
use App\Http\Requests\Shared\Profile\LanguageRequest;
use App\Http\Resources\V6\Deliveryman\DeliverymanProfileResource;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\OrderService;
use App\Services\New\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProfileController extends BaseApiController
{
    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly OrderService $orderService,
        private readonly ReviewService $reviewService,
    ) {}

    #[ApiDoc(
        summary: 'The signed-in delivery man profile',
        description: 'Profile, bank details, balances, delivery counts and rating in one call.',
        tag: 'Delivery-man',
        status: [401, 403],
        guard: 'deliveryman',
    )]
    public function getProfile(Request $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => (new DeliverymanProfileResource($deliveryman))->resolve()
                + $this->earningSummary(deliverymanId: (int) $deliveryman->id)
                + $this->orderService->getDeliveryStatusSummaryInAnyBusinessModule(deliverymanId: (int) $deliveryman->id)
                + $this->reviewService->getRatingSummary(filters: ['delivery_man_id' => (int) $deliveryman->id]),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update the delivery man profile',
        description: 'Send as multipart with _method=PUT when uploading an image; PHP does not populate files on a real PUT body. Fields left out are kept, so a partial update never blanks the address.',
        tag: 'Delivery-man',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function updateProfile(UpdateRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $updated = $this->deliveryManService->update(
            id: $deliveryman->id,
            data: $request->validated(),
            image: $request->file('image'),
        );

        return $this->responseFormatter(
            config: config('response.profile_updated'),
            content: ['data' => new DeliverymanProfileResource($updated),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Update the delivery man bank details',
        description: 'Only the fields you send are written, so sending one does not clear the rest.',
        tag: 'Delivery-man',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function updateBankInfo(BankInfoRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $updated = $this->deliveryManService->update(id: $deliveryman->id, data: $request->validated());

        return $this->responseFormatter(
            config: config('response.bank_information_updated'),
            content: ['data' => new DeliverymanProfileResource($updated),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Go online or offline',
        description: 'Going offline is refused while an order is out for delivery and not paused.',
        tag: 'Delivery-man',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function updateOnlineStatus(OnlineStatusRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');
        $isOnline = $request->boolean('is_online');

        if (! $isOnline && $this->orderService->hasOngoingDeliveryInAnyBusinessModule(deliverymanId: (int) $deliveryman->id)) {
            return $this->authError(
                config: config('response.delivery_in_progress'),
                errorCode: AuthErrorCode::DELIVERY_IN_PROGRESS,
            );
        }

        $this->deliveryManService->updateOnlineStatus(id: $deliveryman->id, isOnline: $isOnline);

        return $this->responseFormatter(
            config: config($isOnline ? 'response.online_status_on' : 'response.online_status_off'),
            content: ['is_online' => $isOnline,
            ],
        );
    }

    #[ApiDoc(
        summary: 'Store the device push token',
        tag: 'Delivery-man',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function updateDeviceToken(DeviceTokenUpdateRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $this->deliveryManService->update(id: $deliveryman->id, data: $request->validated());

        return $this->responseFormatter(config: config('response.device_token_updated'));
    }

    #[ApiDoc(
        summary: 'Change the delivery man app language',
        description: 'Only a language enabled in the admin panel is accepted; the stored value drives push-notification wording.',
        tag: 'Delivery-man',
        status: [401, 403, 422],
        guard: 'deliveryman',
    )]
    public function updateLanguage(LanguageRequest $request): JsonResponse
    {
        $deliveryman = $request->attributes->get('delivery_man');

        $this->deliveryManService->update(
            id: $deliveryman->id,
            data: ['app_language' => $request['current_language']],
        );

        return $this->responseFormatter(
            config: config('response.language_updated_successfully'),
            content: ['app_language' => $request['current_language'],
            ],
        );
    }

    /**
     * @return array{current_balance: float, cash_in_hand: float, pending_withdraw: float, total_withdraw: float, withdrawable_balance: float, total_earn: float}
     */
    private function earningSummary(int $deliverymanId): array
    {
        $wallet = $this->deliverymanWalletService->findBy(params: ['delivery_man_id' => $deliverymanId]);

        $currentBalance = (float) ($wallet->current_balance ?? 0);
        $cashInHand = (float) ($wallet->cash_in_hand ?? 0);
        $pendingWithdraw = (float) ($wallet->pending_withdraw ?? 0);
        $totalWithdraw = (float) ($wallet->total_withdraw ?? 0);

        return [
            'current_balance' => $currentBalance,
            'cash_in_hand' => $cashInHand,
            'pending_withdraw' => $pendingWithdraw,
            'total_withdraw' => $totalWithdraw,
            'withdrawable_balance' => max(0, $currentBalance - ($cashInHand + $pendingWithdraw)),
            'total_earn' => $currentBalance + $totalWithdraw,
        ];
    }
}
