<?php

namespace App\Services\New\Deal;

use App\Enums\DealType;

class FeaturedDealService extends FlashDealService
{
    public const PRIORITY_TYPE = 'feature_deal_priority';

    protected function dealType(): string
    {
        return DealType::FEATURED;
    }

    public function getPriorityType(): string
    {
        return self::PRIORITY_TYPE;
    }
}
