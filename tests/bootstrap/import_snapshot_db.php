<?php

/**
 * Builds the database the API snapshot harness runs against.
 *
 * Usage:
 *   php tests/bootstrap/import_snapshot_db.php            # import once, skip when already present
 *   php tests/bootstrap/import_snapshot_db.php --fresh    # drop and re-import
 *
 * Source database  : SNAPSHOT_SOURCE_DB, falling back to DB_DATABASE from .env
 * Snapshot database: SNAPSHOT_TEST_DB, falling back to <source>_test
 * mysql binaries   : MYSQL_BIN_DIR, falling back to MAMP and then to PATH
 *
 * The application reads settings, themes, payment configs and add-on states
 * from the database at boot, so the harness needs a populated clone rather
 * than a migrated empty schema.
 */

use Tests\Feature\Api\Support\TestDatabase;

require __DIR__.'/../../vendor/autoload.php';

$fresh = in_array('--fresh', array_slice($argv, 1), true);

$source = TestDatabase::sourceDatabase();
$target = TestDatabase::testDatabase();
$connection = TestDatabase::connection();

if ($source === $target) {
    fail("Source and snapshot database are the same ({$source}). Set SNAPSHOT_TEST_DB.");
}

if (! str_ends_with($target, '_test')) {
    fail("Snapshot database name must end with _test, got \"{$target}\".");
}

$mysql = binary('mysql');
$mysqldump = binary('mysqldump');

$server = TestDatabase::pdo();

$sourceExists = (bool) $server->query('SHOW DATABASES LIKE '.$server->quote($source))->fetchColumn();
if (! $sourceExists) {
    fail("Source database \"{$source}\" does not exist.");
}

if ($fresh) {
    info("Dropping {$target}");
    $server->exec("DROP DATABASE IF EXISTS `{$target}`");
}

$server->exec("CREATE DATABASE IF NOT EXISTS `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$tableCount = (int) $server->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '.$server->quote($target))->fetchColumn();

if ($tableCount > 0) {
    info("{$target} already holds {$tableCount} tables — nothing to do. Re-run with --fresh to rebuild.");
    exit(0);
}

info("Cloning {$source} into {$target}");

$command = sprintf(
    '%s %s --single-transaction --no-tablespaces --routines --events %s | %s %s %s',
    escapeshellarg($mysqldump),
    credentials($connection),
    escapeshellarg($source),
    escapeshellarg($mysql),
    credentials($connection),
    escapeshellarg($target)
);

passthru($command, $exitCode);

if ($exitCode !== 0) {
    fail("Import failed with exit code {$exitCode}.");
}

$clone = TestDatabase::pdo($target);
$tables = (int) $clone->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '.$clone->quote($target))->fetchColumn();

if ($tables === 0) {
    fail("Import produced no tables in {$target}. Check the mysqldump output above.");
}

info("Imported {$tables} tables into {$target}");

foreach (['products', 'orders', 'users', 'sellers', 'business_settings'] as $table) {
    $rows = (int) $clone->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    info(sprintf('  %-20s %d rows', $table, $rows));
}

function credentials(array $connection): string
{
    $parts = ['--user='.escapeshellarg($connection['username'])];

    if ($connection['password'] !== '') {
        $parts[] = '--password='.escapeshellarg($connection['password']);
    }

    if ($connection['socket'] !== '') {
        $parts[] = '--socket='.escapeshellarg($connection['socket']);
    } else {
        $parts[] = '--host='.escapeshellarg($connection['host']);
        $parts[] = '--port='.escapeshellarg($connection['port']);
    }

    return implode(' ', $parts);
}

function binary(string $name): string
{
    $candidates = [];

    if ($directory = getenv('MYSQL_BIN_DIR')) {
        $candidates[] = rtrim($directory, '/').'/'.$name;
    }

    foreach (glob('/Applications/MAMP/Library/bin/mysql*/bin/'.$name) ?: [] as $mampBinary) {
        $candidates[] = $mampBinary;
    }

    $candidates[] = '/Applications/MAMP/Library/bin/'.$name;

    foreach ($candidates as $candidate) {
        if (is_executable($candidate)) {
            return $candidate;
        }
    }

    $resolved = trim((string) shell_exec('command -v '.escapeshellarg($name)));

    if ($resolved === '') {
        fail("Could not locate the \"{$name}\" binary. Set MYSQL_BIN_DIR to the directory holding it.");
    }

    return $resolved;
}

function info(string $message): void
{
    fwrite(STDOUT, $message.PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, 'ERROR: '.$message.PHP_EOL);
    exit(1);
}
