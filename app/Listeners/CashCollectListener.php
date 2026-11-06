<?php

namespace App\Listeners;

use App\Events\CashCollectEvent;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;


class CashCollectListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    use PushNotificationTrait;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CashCollectEvent $event): void
    {
        $this->sendNotification($event);
    }

    public function sendNotification($event): void
    {
        $key = $event->key;
        $type = $event->type;
        $lang = $event->lang;
        $amount = $event->amount;
        $fcmToken = $event->fcmToken;
        $this->cashCollectNotification(key: $key, type: $type, lang: $lang, amount: $amount, fcmToken: $fcmToken);
    }
}
