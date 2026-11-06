<?php

namespace App\Listeners;

use App\Events\OtpSmsEvent;
use App\Utils\SMSModule;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpSmsListener implements ShouldQueue
{
    public string $queue = 'high';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(OtpSmsEvent $event): void
    {
        SMSModule::sendCentralizedSMS($event->phone, $event->otp);
    }
}
