<?php

namespace App\Enums;

enum OtpPurpose
{
    const LOGIN = 'login';

    const VERIFICATION = 'verification';

    const REGISTRATION = 'registration';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::LOGIN, self::VERIFICATION, self::REGISTRATION];
    }
}
