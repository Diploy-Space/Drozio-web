<?php

namespace Modules\AI\app\Contracts;

use Laravel\Ai\Contracts\Tool;

interface ShoppingStorefrontInterface
{
    public function businessModule(): string;

    /** @return array<int, Tool> */
    public function tools(): array;

    public function systemPrompt(): string;

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    public function getActiveProductIds(array $productIds): array;
}
