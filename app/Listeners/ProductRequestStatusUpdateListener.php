<?php

namespace App\Listeners;

use App\Events\ProductRequestStatusUpdateEvent;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductRequestStatusUpdateListener implements ShouldQueue
{
    use PushNotificationTrait;

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(ProductRequestStatusUpdateEvent $event): void
    {
        $this->productRequestStatusUpdateNotification(
            key: $event->key,
            type: $event->type,
            lang: $event->lang,
            fcmToken: $event->fcmToken,
            businessModule: $event->businessModule,
            productId: $event->productId,
        );
    }
}
