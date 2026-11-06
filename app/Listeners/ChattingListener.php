<?php

namespace App\Listeners;

use App\Events\ChattingEvent;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;


class ChattingListener implements ShouldQueue
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
    public function handle(ChattingEvent $event): void
    {
        $this->sendNotification($event);
    }

    private function sendNotification(ChattingEvent $event): void
    {
        $key = $event->key;
        $type = $event->type;
        $userData = $event->userData;
        $messageForm = $event->messageForm;
        $this->chattingNotification(key: $key, type: $type, userData: $userData, messageForm: $messageForm);
    }
}
