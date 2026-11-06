<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpSmsEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly string $phone, public readonly string $otp) {}
}
