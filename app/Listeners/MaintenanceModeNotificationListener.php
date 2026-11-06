<?php

namespace App\Listeners;

use App\Events\MaintenanceModeNotificationEvent;
use App\Traits\EmailTemplateTrait;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class MaintenanceModeNotificationListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    use EmailTemplateTrait, PushNotificationTrait;

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
    public function handle(MaintenanceModeNotificationEvent $event): void
    {
        $this->sendNotification($event);
    }

    private function sendNotification(MaintenanceModeNotificationEvent $event): void
    {
        $data = $event->data;
        $this->sendNotificationToHttp([
            'message' => [
                'topic' => $data['topic'],
                'data' => [
                    'title' => (string)$data['title'],
                    'body' => (string)$data['description'],
                    'image' => $data['image'] ?? '',
                    'type' => (string)$data['type'],
                    'is_read' => '0'
                ],
//                'notification' => [
//                    'title' => (string)$data['title'],
//                    'body' => (string)$data['description'],
//                ]
            ]
        ]);
    }
}
