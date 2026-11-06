<?php

namespace Tests\Feature\Api\Support;

use RuntimeException;

/**
 * Reads and writes the recorded snapshot files under tests/Feature/Api/__snapshots__.
 *
 * A missing snapshot is recorded on the spot — that is how a migration task
 * captures the current contract before touching production code. An existing
 * snapshot is only overwritten when SNAPSHOT_UPDATE=1 is set explicitly.
 */
final class SnapshotRecorder
{
    public function __construct(private readonly string $group) {}

    public static function updatesAllowed(): bool
    {
        return in_array((string) getenv('SNAPSHOT_UPDATE'), ['1', 'true'], true);
    }

    public function path(string $name): string
    {
        return dirname(__DIR__).'/__snapshots__/'.$this->group.'/'.$name.'.json';
    }

    public function relativePath(string $name): string
    {
        return 'tests/Feature/Api/__snapshots__/'.$this->group.'/'.$name.'.json';
    }

    public function exists(string $name): bool
    {
        return is_file($this->path($name));
    }

    public function read(string $name): string
    {
        $contents = file_get_contents($this->path($name));

        if ($contents === false) {
            throw new RuntimeException('Unable to read snapshot '.$this->relativePath($name));
        }

        return $contents;
    }

    public function write(string $name, string $contents): void
    {
        $path = $this->path($name);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create snapshot directory '.$directory);
        }

        file_put_contents($path, $contents);
    }

    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
