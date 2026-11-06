<?php

namespace App\Listeners;

use App\Events\AdminPasswordResetEvent;
use App\Mail\AdminPasswordResetMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class AdminPasswordResetListener implements ShouldQueue
{
    public string $queue = 'high';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(AdminPasswordResetEvent $event): void
    {
        Mail::to($event->email)->send(new AdminPasswordResetMail(url: $event->resetUrl));
    }
}
