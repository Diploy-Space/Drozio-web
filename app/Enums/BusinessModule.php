<?php

namespace App\Enums;

/**
 * Canonical business-module keys.
 *
 * These mirror the keys under `modules` in config/business_modules.php, which
 * remains the single source of truth for each module's label, icon, and
 * availability gating. This enum only centralises the KEY constants so the
 * codebase never hardcodes 'retail'/'wholesale'/'auction' as magic strings.
 */
enum BusinessModule
{
    const RETAIL = 'retail';
    const WHOLESALE = 'wholesale';
    const AUCTION = 'auction';

    /**
     * Scope key for records that belong to every business module rather than
     * one of them. Not a business area — it has no config entry, no sidebar
     * sections and never appears in the header's "Select Business" switcher;
     * it only widens an in-page scope selector. Excluded from values().
     */
    const COMMON = 'common';

    /**
     * All business-module keys.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::RETAIL, self::WHOLESALE, self::AUCTION];
    }
}
