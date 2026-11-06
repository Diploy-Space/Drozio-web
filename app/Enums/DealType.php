<?php

namespace App\Enums;

enum DealType
{
    const FLASH = 'flash_deal';

    const FEATURED = 'feature_deal';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::FLASH, self::FEATURED];
    }
}
