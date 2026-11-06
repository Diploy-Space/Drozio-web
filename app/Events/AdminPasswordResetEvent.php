<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminPasswordResetEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly string $email, public readonly string $resetUrl) {}
}
