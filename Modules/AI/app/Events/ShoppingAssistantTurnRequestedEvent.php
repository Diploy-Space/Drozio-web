<?php

namespace Modules\AI\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\AI\app\Contracts\ShoppingStorefrontInterface;

class ShoppingAssistantTurnRequestedEvent
{
    use Dispatchable;

    /** @var array{reply: string, intent: string, products: array, actions: array}|null */
    public ?array $result = null;

    public function __construct(
        public readonly int $sessionId,
        public readonly string $content,
        public readonly ?string $imageUrl,
        public readonly ShoppingStorefrontInterface $storefront,
    ) {}
}
