<?php

namespace App\Listeners;

use App\Events\RestockProductNotificationEvent;
use App\Traits\EmailTemplateTrait;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class RestockProductNotificationListener implements ShouldQueue
{
    use EmailTemplateTrait, PushNotificationTrait;

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(RestockProductNotificationEvent $event): void
    {
        $this->sendNotification($event);
    }

    private function sendNotification(RestockProductNotificationEvent $event): void
    {
        $data = $event->data;
        $this->sendNotificationToHttp([
            'message' => [
                'topic' => $data['topic'],
                'data' => [
                    'title' => (string) $data['title'],
                    'product_id' => (string) ($data['product_id'] ?? ''),
                    'slug' => (string) ($data['slug'] ?? ''),
                    'body' => (string) $data['description'],
                    'image' => $data['image'] ?? '',
                    'type' => (string) ($data['type'] ?? ''),
                    'status' => (string) ($data['status'] ?? ''),
                    'route' => (string) ($data['route'] ?? ''),
                    'is_read' => '0',
                    ...$this->businessModulePayload(businessModule: (string) ($data['business_module'] ?? '')),
                ],
                'notification' => [
                    'title' => (string) $data['title'],
                    'body' => (string) $data['description'],
                ],
            ],
        ]);
    }
}
