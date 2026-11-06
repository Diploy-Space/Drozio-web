<?php

namespace App\Listeners;

use App\Events\RequestProductRestockEvent;
use App\Traits\EmailTemplateTrait;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequestProductRestockListener implements ShouldQueue
{
    use EmailTemplateTrait, PushNotificationTrait;

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(RequestProductRestockEvent $event): void
    {
        $this->sendNotification($event);
    }

    private function sendNotification(RequestProductRestockEvent $event): void
    {
        $data = $event->data;

        if (isset($data['firebase_token'])) {
            $postData = [
                'message' => [
                    'token' => $data['firebase_token'],
                    'data' => [
                        'title' => (string) $data['title'],
                        'body' => (string) $data['body'],
                        'image' => (string) ($data['image'] ?? ''),
                        ...$this->businessModulePayload(businessModule: (string) ($data['business_module'] ?? '')),
                    ],
                    'notification' => [
                        'title' => (string) $data['title'],
                        'body' => (string) $data['body'],
                    ],
                ],
            ];
            $this->sendNotificationToHttp($postData);
        }
    }
}
