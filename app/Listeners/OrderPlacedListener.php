<?php

namespace App\Listeners;

use App\Events\OrderPlacedEvent;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Traits\EmailTemplateTrait;
use App\Traits\PushNotificationTrait;
use App\Utils\OrderInvoicePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class OrderPlacedListener implements ShouldQueue
{
    use EmailTemplateTrait;
    use PushNotificationTrait;

    public string $queue = 'high';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly ReferralCustomerService $referralCustomerService,
    ) {}

    public function handle(OrderPlacedEvent $event): void
    {
        if ($event->email) {
            $this->sendMail($event);
        }

        if ($event->notification) {
            $this->sendNotification($event);
        }
    }

    private function sendMail(OrderPlacedEvent $event): void
    {
        $data = $this->withInvoiceAttachment(data: $event->data);

        try {
            $this->sendingMail(sendMailTo: $event->email, userType: $data['userType'], templateName: $data['templateName'], data: $data);
        } catch (Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withInvoiceAttachment(array $data): array
    {
        if (! isset($data['invoiceOrderId'])) {
            return $data;
        }

        $order = $this->orderService->find(id: $data['invoiceOrderId'], relations: ['seller', 'shipping']);

        if ($order === null) {
            return $data;
        }

        try {
            $data['attachmentPath'] = OrderInvoicePdf::store(order: $order);
        } catch (Throwable) {
        }

        return $data;
    }

    private function sendNotification(OrderPlacedEvent $event): void
    {
        $order = $event->notification->order;

        $this->sendOrderNotification(key: $event->notification->key, type: $event->notification->type, order: $order);

        if ($order['is_guest'] || ! isset($order?->customer?->id)) {
            return;
        }

        $referralCustomer = $this->referralCustomerService->findBy(params: ['user_id' => $order->customer->id]);

        if ($referralCustomer !== null && $referralCustomer->ordered_notify != 1) {
            $this->referralCustomerService->updateWhere(params: ['user_id' => $order->customer->id], data: ['ordered_notify' => 1]);
        }
    }
}
