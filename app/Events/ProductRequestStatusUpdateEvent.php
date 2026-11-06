<?php

namespace App\Events;

use App\Enums\BusinessModule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRequestStatusUpdateEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $key,
        public string $type,
        public string $lang,
        public string $fcmToken,
        public string $businessModule = BusinessModule::RETAIL,
        public ?int $productId = null,
    ) {}
}
