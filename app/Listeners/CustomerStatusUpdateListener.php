<?php

namespace App\Listeners;

use App\Events\CustomerStatusUpdateEvent;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomerStatusUpdateListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    use EmailTemplateTrait;
    public function __construct()
    {
        //
    }

    public function handle(CustomerStatusUpdateEvent $event): void
    {
        $this->sendMail($event);
    }

    private function sendMail(CustomerStatusUpdateEvent $event):void{
        $email = $event->email;
        $data = $event->data;
        $this->sendingMail(sendMailTo: $email,userType: $data['userType'],templateName: $data['templateName'],data: $data);
    }
}
