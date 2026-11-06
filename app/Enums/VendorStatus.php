<?php

namespace App\Enums;

enum VendorStatus
{
    const PENDING = 'pending';

    const APPROVED = 'approved';

    const DENIED = 'denied';

    const SUSPENDED = 'suspended';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::PENDING, self::APPROVED, self::DENIED, self::SUSPENDED];
    }
}
