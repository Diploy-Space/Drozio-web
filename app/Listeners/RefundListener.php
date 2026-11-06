<?php

namespace App\Listeners;

use App\Events\RefundEvent;
use App\Services\New\OrderService;
use App\Services\New\RefundRequestService;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefundListener implements ShouldQueue
{
    use PushNotificationTrait;

    public const STATUS_REFUND_REQUESTED = 'refund_request';

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(RefundEvent $event): void
    {
        $order = $event->order;
        $data = [
            'refund' => $event->refund,
            'order_details_id' => $event->orderDetails?->id,
        ];

        if ($order['seller_is'] == OrderService::SELLER_IS_SELLER) {
            $this->sendOrderNotification(
                key: $this->getSellerNotificationKey(status: $event->status),
                type: OrderService::NOTIFICATION_TYPE_SELLER,
                order: $order,
                data: $data,
            );
        }

        $customerKey = $this->getCustomerNotificationKey(status: $event->status);

        if ($customerKey !== null) {
            $this->sendOrderNotification(key: $customerKey, type: OrderService::NOTIFICATION_TYPE_CUSTOMER, order: $order, data: $data);
        }
    }

    private function getSellerNotificationKey(string $status): string
    {
        return match ($status) {
            self::STATUS_REFUND_REQUESTED => 'refund_request_message',
            RefundRequestService::STATUS_REJECTED => 'refund_request_canceled_message',
            RefundRequestService::STATUS_REFUNDED => 'order_refunded_message',
            default => 'refund_request_status_changed_by_admin',
        };
    }

    private function getCustomerNotificationKey(string $status): ?string
    {
        return match ($status) {
            self::STATUS_REFUND_REQUESTED => 'refund_request_message',
            RefundRequestService::STATUS_APPROVED => 'refund_request_approved_message',
            RefundRequestService::STATUS_REFUNDED => 'order_refunded_message',
            RefundRequestService::STATUS_REJECTED => 'refund_request_canceled_message',
            default => null,
        };
    }
}
