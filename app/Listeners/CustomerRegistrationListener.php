<?php

namespace App\Listeners;

use App\Events\CustomerRegistrationEvent;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\Queue\ShouldQueue;


class CustomerRegistrationListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    use EmailTemplateTrait;
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
    public function handle(CustomerRegistrationEvent $event): void
    {
        $this->sendMail($event);
    }

    private function sendMail(CustomerRegistrationEvent $event):void{
        $email = $event->email;
        $data = $event->data;
        $this->sendingMail(sendMailTo: $email,userType: $data['userType'],templateName: $data['templateName'],data: $data);

    }
}
