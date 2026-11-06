<?php

namespace Tests\Feature\Wholesale\Concerns;

use Modules\Wholesale\app\Providers\WholesaleServiceProvider;

trait InteractsWithWholesaleAddon
{
    protected function skipUnlessWholesaleAddonPublished(): void
    {
        if (! addon_published_status(moduleName: 'Wholesale')) {
            $this->markTestSkipped('Wholesale addon is not published.');
        }
    }

    protected static function wholesaleDataSets(callable $dataSets): array
    {
        return self::isWholesaleAddonInstalled() ? $dataSets() : ['Wholesale addon is not installed' => []];
    }

    protected static function withWholesaleDataSets(array $dataSets, array $wholesaleDataSets): array
    {
        return self::isWholesaleAddonInstalled() ? [...$dataSets, ...$wholesaleDataSets] : $dataSets;
    }

    private static function isWholesaleAddonInstalled(): bool
    {
        return class_exists(WholesaleServiceProvider::class);
    }
}
