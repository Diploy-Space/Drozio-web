<?php

namespace App\Enums;

enum UserType
{
    const CUSTOMER = 'customer';

    const SELLER = 'seller';

    const DELIVERY_MAN = 'delivery_man';

    const ADMIN = 'admin';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::CUSTOMER, self::SELLER, self::DELIVERY_MAN, self::ADMIN];
    }
}
