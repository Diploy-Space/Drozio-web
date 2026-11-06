<?php

namespace App\Listeners;

use App\Events\WithdrawStatusUpdateEvent;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;


class WithdrawStatusUpdateListener implements ShouldQueue
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
    public function handle(WithdrawStatusUpdateEvent $event): void
    {
        $this->sendNotification($event);
    }

    public function sendNotification($event): void
    {
        $key = $event->key;
        $type = $event->type;
        $lang = $event->lang;
        $status = $event->status;
        $fcmToken = $event->fcmToken;
        $this->withdrawStatusUpdateNotification(key: $key, type: $type, lang: $lang, status: $status, fcmToken: $fcmToken);
    }
}
