<?php

namespace App\Listeners;
use App\Events\DigitalProductOtpVerificationEvent;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class DigitalProductOtpVerificationListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'high';
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
    public function handle(DigitalProductOtpVerificationEvent $event): void
    {
        $this->sendMail($event);
    }

    private function sendMail(DigitalProductOtpVerificationEvent $event):void{
        $email = $event->email;
        $data = $event->data;
        $this->sendingMail(sendMailTo: $email,userType: $data['userType'],templateName: $data['templateName'],data: $data);
    }
}
