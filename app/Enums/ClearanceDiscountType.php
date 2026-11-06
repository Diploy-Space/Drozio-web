<?php

namespace App\Enums;

enum ClearanceDiscountType
{
    const FLAT = 'flat';

    const PERCENTAGE = 'percentage';

    const PRODUCT_WISE = 'product_wise';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::FLAT, self::PERCENTAGE, self::PRODUCT_WISE];
    }
}
