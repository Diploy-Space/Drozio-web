<?php

namespace App\Enums;

/**
 * Canonical user types for push-notification messages.
 *
 * These are the persisted `notification_messages.user_type` values. The admin UI
 * historically used the labels "vendor"/"deliveryman" in the URL while the DB
 * stored "seller"/"delivery_man"; resolveFromRequest() centralises that mapping
 * so the divergence lives in exactly one place.
 */
enum NotificationUserType
{
    const CUSTOMER = 'customer';
    const SELLER = 'seller';
    const ADMIN = 'admin';
    const DELIVERY_MAN = 'delivery_man';

    /**
     * All canonical user-type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::CUSTOMER, self::SELLER, self::ADMIN, self::DELIVERY_MAN];
    }

    /**
     * Value => human label (untranslated key fed to translate() in the view).
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::CUSTOMER => 'Customer',
            self::SELLER => 'Vendor',
            self::ADMIN => 'Admin',
            self::DELIVERY_MAN => 'Delivery Man',
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? $value;
    }

    /**
     * Resolve a request/URL value to a canonical user-type value, tolerating the
     * legacy UI aliases. Returns null when the value is not a known user type.
     */
    public static function resolveFromRequest(?string $value): ?string
    {
        $aliases = [
            'vendor' => self::SELLER,
            'deliveryman' => self::DELIVERY_MAN,
            'delivery-man' => self::DELIVERY_MAN,
        ];

        $resolved = $aliases[$value] ?? $value;

        return in_array($resolved, self::values(), true) ? $resolved : null;
    }
}
