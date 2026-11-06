<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Order\DeliveryProofAddRequest;
use App\Http\Requests\Deliveryman\Order\VerificationCodeRequest;
use App\Services\New\OrderDeliveryVerificationService;
use App\Services\New\OrderService;
use App\Traits\PushNotificationTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DeliveryVerificationController extends BaseApiController
{
    use PushNotificationTrait;

    private const PUSH_TYPE_ORDER = 'order';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDeliveryVerificationService $orderDeliveryVerificationService,
    ) {}

    #[ApiDoc(
        summary: 'Send the customer a delivery verification code',
        description: 'Generates a new 6-digit code for an order assigned to the delivery man and pushes it to the customer. A guest order or a customer without a device token cannot receive it.',
        tag: 'Delivery-man Delivery Verification',
        status: [401, 403, 404],
        guard: 'deliveryman',
    )]
    public function sendVerificationCode(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->findForDeliveryman(
            id: $id,
            deliverymanId: (int) $request->attributes->get('delivery_man')->id,
            relations: ['customer'],
        );

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $verificationCode = $this->orderService->regenerateDeliveryVerificationCode(order: $order);
        $customerDeviceToken = $order['is_guest'] ? null : $order->customer?->cm_firebase_token;

        if ($verificationCode === null || empty($customerDeviceToken)) {
            return $this->responseFormatter(config: config('response.delivery_verification_code_send_failed'));
        }

        $title = translate('Order verification code');

        $this->sendPushNotificationToDevice(fcmToken: $customerDeviceToken, data: [
            'title' => $title,
            'description' => $title.' '.$verificationCode,
            'order_id' => $order->id,
            'image' => '',
            'type' => self::PUSH_TYPE_ORDER,
        ]);

        return $this->responseFormatter(config: config('response.delivery_verification_code_sent'));
    }

    #[ApiDoc(
        summary: 'Verify the delivery code the customer reads out',
        description: 'Marks the order as verified when the code matches the last one sent.',
        tag: 'Delivery-man Delivery Verification',
        status: [401, 403, 404, 422, 429],
        guard: 'deliveryman',
    )]
    public function verifyCode(VerificationCodeRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->findForDeliveryman(
            id: $id,
            deliverymanId: (int) $request->attributes->get('delivery_man')->id,
        );

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $isVerified = $this->orderService->verifyDeliveryCode(
            order: $order,
            verificationCode: (string) $request['verification_code'],
        );

        return $this->responseFormatter(config: config(
            $isVerified ? 'response.delivery_verification_code_verified' : 'response.delivery_verification_code_invalid'
        ));
    }

    #[ApiDoc(
        summary: 'Upload photos of the delivery handover',
        description: 'Send the photos as image[]. Each one is stored as webp against an order assigned to the delivery man.',
        tag: 'Delivery-man Delivery Verification',
        status: [401, 404, 422],
        guard: 'deliveryman',
    )]
    public function storeDeliveryProofs(DeliveryProofAddRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->findForDeliveryman(
            id: $id,
            deliverymanId: (int) $request->attributes->get('delivery_man')->id,
        );

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        foreach ($request->file('image') ?? [] as $image) {
            $this->orderDeliveryVerificationService->create(data: ['order_id' => $order->id], image: $image);
        }

        return $this->responseFormatter(config: config('response.delivery_proofs_uploaded'));
    }
}
