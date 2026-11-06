<?php

namespace Tests\Unit;

use App\Services\Cache\AppCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit coverage for the file-driver-safe cache seam.
 *
 * Runs on the array driver (phpunit.xml CACHE_DRIVER=array); the group-registry
 * logic under test is driver-agnostic, so behaviour matches the file driver.
 */
class AppCacheTest extends TestCase
{
    public function test_remember_computes_once_then_serves_cached_value(): void
    {
        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;
            return 'value-' . $calls;
        };

        $first = AppCache::remember('alpha', 60, $compute);
        $second = AppCache::remember('alpha', 60, $compute);

        $this->assertSame('value-1', $first);
        $this->assertSame('value-1', $second, 'Second remember() must return the cached value.');
        $this->assertSame(1, $calls, 'Callback must run only once.');
    }

    public function test_remember_namespaces_key_under_prefix(): void
    {
        AppCache::remember('beta', 60, fn() => 'x');

        $this->assertTrue(Cache::has('6v:beta'), 'Key must be stored under the 6v: prefix.');
        $this->assertSame('x', AppCache::get('beta'));
    }

    public function test_forget_removes_a_single_key(): void
    {
        AppCache::put('gamma', 'v', 60);
        $this->assertTrue(AppCache::has('gamma'));

        $this->assertTrue(AppCache::forget('gamma'));
        $this->assertFalse(AppCache::has('gamma'));
    }

    public function test_forget_group_invalidates_every_member_and_the_registry(): void
    {
        AppCache::remember('k1', 60, fn() => 'a', group: 'catalog');
        AppCache::remember('k2', 60, fn() => 'b', group: 'catalog');
        AppCache::remember('k3', 60, fn() => 'c', group: 'catalog');

        // A key in a different group must survive.
        AppCache::remember('other', 60, fn() => 'z', group: 'settings');

        $this->assertTrue(Cache::has('6v:registry:catalog'));

        AppCache::forgetGroup('catalog');

        $this->assertFalse(AppCache::has('k1'));
        $this->assertFalse(AppCache::has('k2'));
        $this->assertFalse(AppCache::has('k3'));
        $this->assertFalse(Cache::has('6v:registry:catalog'), 'Registry itself must be forgotten.');

        $this->assertTrue(AppCache::has('other'), 'Keys in other groups must be untouched.');
    }

    public function test_forget_group_registers_each_key_once(): void
    {
        AppCache::remember('dup', 60, fn() => '1', group: 'g');
        AppCache::forget('dup');
        AppCache::remember('dup', 60, fn() => '2', group: 'g');

        $this->assertSame(['dup'], Cache::get('6v:registry:g'), 'Registry must not contain duplicate keys.');
    }

    public function test_forget_group_on_missing_or_empty_registry_is_a_safe_noop(): void
    {
        // Never populated.
        AppCache::forgetGroup('never-used');
        $this->assertFalse(Cache::has('6v:registry:never-used'));

        // Empty registry present but no member keys.
        Cache::forever('6v:registry:empty', []);
        AppCache::forgetGroup('empty');
        $this->assertFalse(Cache::has('6v:registry:empty'));
    }
}
