<?php

namespace Tests\Feature\Contract\Api;

use App\Models\Currency;
use App\Services\Cache\AppCache;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Proves the invalidation wiring end-to-end against the seeded DB:
 *  - a model write fires its group observer and clears ONLY that group;
 *  - the appcache:clear command clears a named group or all groups;
 * neither touches unrelated groups.
 */
class AppCacheInvalidationContractTest extends ContractTestCase
{
    #[Test]
    public function currency_write_clears_only_the_locale_group(): void
    {
        $this->warm('locale:probe', AppCache::GROUP_LOCALE);
        $this->warm('catalog:probe', AppCache::GROUP_CATALOG);

        // A real admin currency edit — the LocaleCacheObserver fires on saved.
        $currency = Currency::query()->first();
        $currency->exchange_rate = $currency->exchange_rate;
        $currency->save();

        $this->assertFalse(AppCache::has('locale:probe'), 'Currency write must clear the locale group.');
        $this->assertTrue(AppCache::has('catalog:probe'), 'Currency write must NOT clear other groups.');
    }

    #[Test]
    public function legacy_cache_remove_by_type_also_clears_the_mapped_group(): void
    {
        $this->warm('catalog:probe', AppCache::GROUP_CATALOG);
        $this->warm('locale:probe', AppCache::GROUP_LOCALE);

        // Non-Eloquent invalidation path (repositories/query-builder updates).
        cacheRemoveByType(type: 'categories');

        $this->assertFalse(AppCache::has('catalog:probe'), 'cacheRemoveByType(categories) must clear the catalog group.');
        $this->assertTrue(AppCache::has('locale:probe'), 'It must not clear unrelated groups.');
    }

    #[Test]
    public function command_clears_a_named_group_only(): void
    {
        $this->warm('shipping:probe', AppCache::GROUP_SHIPPING);
        $this->warm('settings:probe', AppCache::GROUP_SETTINGS);

        $this->artisan('appcache:clear', ['group' => 'shipping'])
            ->assertSuccessful();

        $this->assertFalse(AppCache::has('shipping:probe'));
        $this->assertTrue(AppCache::has('settings:probe'));
    }

    #[Test]
    public function command_rejects_an_unknown_group(): void
    {
        $this->artisan('appcache:clear', ['group' => 'nope'])->assertFailed();
    }

    #[Test]
    public function command_without_argument_clears_every_group(): void
    {
        foreach (AppCache::GROUPS as $group) {
            $this->warm($group . ':probe', $group);
        }

        $this->artisan('appcache:clear')->assertSuccessful();

        foreach (AppCache::GROUPS as $group) {
            $this->assertFalse(AppCache::has($group . ':probe'), "Group [{$group}] should be cleared.");
        }
    }

    private function warm(string $key, string $group): void
    {
        AppCache::rememberInGroup($key, $group, fn() => 'warm');
        $this->assertTrue(AppCache::has($key), "Failed to warm {$key}.");
    }
}
