<?php

namespace App\Listeners;

use App\Events\AddFundToWalletEvent;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddFundToWalletListener implements ShouldQueue
{
    // Queued: fire-and-forget notification; external SMTP/FCM calls get retries.
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    use EmailTemplateTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param AddFundToWalletEvent $event
     * @return void
     */
    public function handle(AddFundToWalletEvent $event): void
    {
        $this->sendMail($event);
    }

    private function sendMail(AddFundToWalletEvent $event): void
    {
        $email = $event->email;
        $data = $event->data;
        $this->sendingMail(sendMailTo: $email, userType: $data['userType'], templateName: $data['templateName'], data: $data);
    }
}
