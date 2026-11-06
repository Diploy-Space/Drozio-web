<?php

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * File-driver-safe cache facade for the app.
 *
 * The project runs on the `file` cache driver, which has NO tag support and NO
 * safe way to wipe a subset of keys. This service is the single seam through
 * which the app should cache, so grouped invalidation stays correct:
 *
 *  - Every key is namespaced under the "6v:" prefix, built in one place (key()).
 *  - forgetGroup() works without tags by maintaining a per-group registry: a
 *    plain cache entry ("6v:registry:{group}") holding the list of keys in the
 *    group. remember() appends to it; forgetGroup() replays it, forgets each
 *    member key, then drops the registry.
 *
 * Cache::flush() is deliberately never called here — on the file driver it
 * deletes the entire cache directory (sessions, framework caches, everything).
 */
class AppCache
{
    public const PREFIX = '6v:';

    private const REGISTRY_PREFIX = self::PREFIX.'registry:';

    /** Cache groups. Each backs one invalidation domain (one set of observers). */
    public const GROUP_SETTINGS = 'settings';

    public const GROUP_CATALOG = 'catalog';

    public const GROUP_CONTENT = 'content';

    public const GROUP_SHIPPING = 'shipping';

    public const GROUP_LOCALE = 'locale';

    public const GROUP_AUCTION = 'auction';

    /** Every registered group, in the order the clear command reports them. */
    public const GROUPS = [
        self::GROUP_SETTINGS,
        self::GROUP_CATALOG,
        self::GROUP_CONTENT,
        self::GROUP_SHIPPING,
        self::GROUP_LOCALE,
        self::GROUP_AUCTION,
    ];

    /**
     * Per-group TTLs in seconds. Correctness comes from observer invalidation,
     * not expiry — the TTL is only a backstop for missed events.
     *   settings, locale : 24h   catalog, shipping, auction : 6h   content : 1h
     * Shipping has no TTL rule of its own; it is config-like and rarely-changing,
     * so it is tiered with catalog (6h).
     */
    public const TTL = [
        self::GROUP_SETTINGS => 86400,
        self::GROUP_LOCALE => 86400,
        self::GROUP_CATALOG => 21600,
        self::GROUP_SHIPPING => 21600,
        self::GROUP_CONTENT => 3600,
        self::GROUP_AUCTION => 21600,
    ];

    /** TTL (seconds) for a group; throws on an unknown group so typos fail loud. */
    public static function ttl(string $group): int
    {
        if (! isset(self::TTL[$group])) {
            throw new \InvalidArgumentException("Unknown cache group [{$group}].");
        }

        return self::TTL[$group];
    }

    /**
     * Build the one canonical namespaced key. All reads/writes route through here.
     */
    public static function key(string $key): string
    {
        return self::PREFIX.$key;
    }

    /**
     * Cache-or-compute for $ttl seconds. When $group is given, the key is
     * registered so forgetGroup($group) can later invalidate it.
     */
    public static function remember(string $key, int $ttl, Closure $callback, ?string $group = null): mixed
    {
        if ($group !== null) {
            self::registerKey($group, $key);
        }

        return Cache::remember(self::key($key), $ttl, $callback);
    }

    /**
     * Preferred call site: cache under a group using that group's TTL, and
     * register the key for group invalidation. Keeps TTL and group in lockstep
     * so a call site can't drift from the policy.
     */
    public static function rememberInGroup(string $key, string $group, Closure $callback): mixed
    {
        return self::remember($key, self::ttl($group), $callback, $group);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get(self::key($key), $default);
    }

    public static function put(string $key, mixed $value, int $ttl, ?string $group = null): bool
    {
        if ($group !== null) {
            self::registerKey($group, $key);
        }

        return Cache::put(self::key($key), $value, $ttl);
    }

    public static function has(string $key): bool
    {
        return Cache::has(self::key($key));
    }

    public static function forget(string $key): bool
    {
        return Cache::forget(self::key($key));
    }

    /**
     * Forget every key registered under $group, then the registry itself.
     * Safe to call when the group was never populated (missing/empty registry).
     */
    public static function forgetGroup(string $group): void
    {
        $registryKey = self::registryKey($group);
        $keys = Cache::get($registryKey, []);
        foreach ($keys as $key) {
            Cache::forget(self::key($key));
        }
        Cache::forget($registryKey);
    }

    /**
     * Forget every registered group's keys (and registries). Targets only keys
     * this service registered — never touches unrelated cache entries, and never
     * calls Cache::flush().
     */
    public static function forgetAllGroups(): void
    {
        foreach (self::GROUPS as $group) {
            self::forgetGroup($group);
        }
    }

    /**
     * Append $key to $group's registry (idempotent — no duplicate entries).
     */
    private static function registerKey(string $group, string $key): void
    {
        $registryKey = self::registryKey($group);
        $keys = Cache::get($registryKey, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever($registryKey, $keys);
        }
    }

    private static function registryKey(string $group): string
    {
        return self::REGISTRY_PREFIX.$group;
    }
}
