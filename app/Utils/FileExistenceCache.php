<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Multi-tier file-existence resolver over any storage disk.
 *
 * Read fast-path for the local "public" disk is a prebuilt manifest
 * (bootstrap/cache/storage_files.php) mapping every relative path under
 * storage/app/public to `true`, so existence is an isset() on an in-memory
 * array instead of a per-file stat() syscall — the point being to survive
 * 100k+ files without touching the filesystem on read paths. Remote disks
 * (S3, etc.) use Laravel Cache for positive hits; everything falls back to a
 * real Storage::exists() so un-indexed / newly uploaded files still resolve.
 *
 * The manifest is loaded once per request (opcache keeps the literal in shared
 * memory) and rebuilt only off the request path via `storage:index` or the
 * cron-less after-response auto-refresh below.
 */
class FileExistenceCache
{
    /** Auto-refresh window (seconds); 0 disables and relies solely on `storage:index`. */
    private const AUTO_REFRESH_TTL = 1800;

    private static array $memo = [];

    private static ?array $index = null;

    private static bool $indexLoaded = false;

    private static ?int $manifestMtime = null;

    private static bool $refreshChecked = false;

    public static function exists(string $disk, string $path): bool
    {
        $memoKey = $disk . ':' . $path;
        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        // Local "public" disk: an indexed hit resolves with no filesystem syscall. A miss
        // falls through to a real stat so newly uploaded / un-indexed files still resolve —
        // identical result to Storage::exists().
        if ($disk === 'public' && self::indexAvailable() && self::indexHas($path)) {
            return self::$memo[$memoKey] = true;
        }

        $remote = self::isRemoteDisk($disk);

        // Positive existence is cached across requests (files rarely disappear); a missing
        // file is never cached, so a later upload is reflected on the next check. Local disks
        // skip the cross-request cache — a local stat is cheaper than a cache round-trip.
        if ($remote && Cache::get(self::cacheKey($disk, $path)) === true) {
            return self::$memo[$memoKey] = true;
        }

        try {
            $exists = Storage::disk($disk)->exists($path);
        } catch (Exception $exception) {
            $exists = false;
        }

        if ($remote && $exists) {
            Cache::put(self::cacheKey($disk, $path), true, STORAGE_FILE_EXISTS_CACHE_TTL);
        }

        return self::$memo[$memoKey] = $exists;
    }

    /** Keep the current request consistent after an upload; the manifest is the cross-request truth. */
    public static function remember(string $disk, string $path): void
    {
        self::$memo[$disk . ':' . $path] = true;

        if ($disk !== 'public') {
            return;
        }
        self::loadIndex();
        if (self::$index !== null) {
            self::$index[$path] = true;
        }
    }

    public static function forget(string $disk, string $path): void
    {
        unset(self::$memo[$disk . ':' . $path]);
        Cache::forget(self::cacheKey($disk, $path));

        if ($disk !== 'public') {
            return;
        }
        self::loadIndex();
        if (self::$index !== null) {
            unset(self::$index[$path]);
        }
    }

    public static function manifestPath(): string
    {
        return base_path('bootstrap/cache/storage_files.php');
    }

    /**
     * Rebuild the manifest from disk — the ONLY method that scans
     * storage/app/public. Never called on a request path, only from the
     * `storage:index` command / scheduler / deploy hook / after-response refresh.
     */
    public static function rebuild(): int
    {
        $root = storage_path('app/public');
        $entries = [];

        if (is_dir($root)) {
            foreach (File::allFiles($root) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                $entries[$relative] = true;
            }
        }

        $export = var_export($entries, true);
        $contents = "<?php\n\nreturn " . $export . ";\n";

        // Atomic publish: write a temp file then rename over the manifest so a
        // concurrent request never require()s a half-written file. rename() is
        // atomic only within the same filesystem, hence the temp sits alongside.
        $manifest = self::manifestPath();
        $temp = $manifest . '.' . getmypid() . '.tmp';
        File::put($temp, $contents);
        rename($temp, $manifest);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($manifest, true);
        }

        self::$index = $entries;
        self::$indexLoaded = true;

        return count($entries);
    }

    private static function isRemoteDisk(string $disk): bool
    {
        return config("filesystems.disks.$disk.driver", 'local') !== 'local';
    }

    private static function cacheKey(string $disk, string $path): string
    {
        return 'file_exists:' . $disk . ':' . md5($path);
    }

    /** The index only participates once built; otherwise callers fall through to a stat. */
    private static function indexAvailable(): bool
    {
        self::loadIndex();
        self::maybeScheduleRefresh();

        return self::$index !== null;
    }

    private static function indexHas(string $relativePath): bool
    {
        self::loadIndex();

        return self::$index !== null && isset(self::$index[$relativePath]);
    }

    private static function loadIndex(): void
    {
        if (self::$indexLoaded) {
            return;
        }
        self::$indexLoaded = true;

        $manifest = self::manifestPath();
        if (is_file($manifest)) {
            $data = require $manifest;
            self::$index = is_array($data) ? $data : null;
            self::$manifestMtime = @filemtime($manifest) ?: null;
        } else {
            self::$index = null;
            self::$manifestMtime = null;
        }
    }

    /**
     * If the manifest is missing or older than the TTL, rebuild it once — but
     * only AFTER the response is flushed (app terminating), so the current
     * request never pays the scan cost. This is the cron-less refresh: organic
     * web traffic keeps an externally (FTP/SSH) mutated tree in sync.
     */
    private static function maybeScheduleRefresh(): void
    {
        if (self::$refreshChecked) {
            return;
        }
        self::$refreshChecked = true;

        if (self::AUTO_REFRESH_TTL <= 0) {
            return;
        }

        $fresh = self::$manifestMtime !== null && (time() - self::$manifestMtime) < self::AUTO_REFRESH_TTL;
        if ($fresh) {
            return;
        }

        $app = function_exists('app') ? app() : null;
        if ($app && method_exists($app, 'terminating')) {
            $app->terminating(fn () => self::refreshWithLock());
        }
    }

    /** Non-blocking lock so exactly one worker rebuilds per stale window; others skip. */
    private static function refreshWithLock(): void
    {
        $lockPath = self::manifestPath() . '.lock';
        $handle = @fopen($lockPath, 'c');
        if (!$handle) {
            return;
        }

        try {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $manifest = self::manifestPath();
                $mtime = @filemtime($manifest);
                if ($mtime === false || (time() - $mtime) >= self::AUTO_REFRESH_TTL) {
                    self::rebuild();
                }
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }
}
