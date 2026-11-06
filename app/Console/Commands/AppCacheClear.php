<?php

namespace App\Console\Commands;

use App\Services\Cache\AppCache;
use Illuminate\Console\Command;

/**
 * Clears AppCache groups without touching unrelated cache entries.
 *
 *   php artisan appcache:clear            # clears every registered group
 *   php artisan appcache:clear catalog    # clears only the catalog group
 *
 * Only keys registered in a group's registry are forgotten — this never calls
 * Cache::flush(), so sessions, framework caches and any non-AppCache entries on
 * the shared file store are left intact.
 */
class AppCacheClear extends Command
{
    protected $signature = 'appcache:clear {group? : One of: ' . 'settings|catalog|content|shipping|locale' . '}';

    protected $description = 'Clear one AppCache group, or all registered groups, without flushing the whole cache';

    public function handle(): int
    {
        $group = $this->argument('group');

        if ($group === null) {
            AppCache::forgetAllGroups();
            $this->info('Cleared all AppCache groups: ' . implode(', ', AppCache::GROUPS) . '.');
            return self::SUCCESS;
        }

        if (!in_array($group, AppCache::GROUPS, true)) {
            $this->error("Unknown group [{$group}]. Valid groups: " . implode(', ', AppCache::GROUPS) . '.');
            return self::FAILURE;
        }

        AppCache::forgetGroup($group);
        $this->info("Cleared AppCache group [{$group}].");
        return self::SUCCESS;
    }
}
