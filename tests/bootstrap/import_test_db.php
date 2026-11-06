<?php

/**
 * Idempotent test-schema importer for the contract suite.
 * Loads installation/backup/database.sql into the testing database via mysqli
 * (the MySQL dump is MySQL-specific and cannot run on SQLite; the schema lives
 * in the dump, not in database/migrations). Runs once unless the sentinel table
 * is absent, or FRESH_TEST_DB=1 forces a rebuild.
 */
function contract_env(string $key, ?string $default = null): ?string
{
    static $dotenv = null;
    $fromProcess = getenv($key);
    if ($fromProcess !== false && $fromProcess !== '') {
        return $fromProcess;
    }
    if ($dotenv === null) {
        $dotenv = [];
        $envFile = dirname(__DIR__, 2).'/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $dotenv[trim($k)] = trim($v, " \t\"'");
            }
        }
    }

    return $dotenv[$key] ?? $default;
}

function import_contract_test_db(): void
{
    $host = contract_env('DB_HOST', '127.0.0.1');
    $port = (int) contract_env('DB_PORT', '3306');
    $user = contract_env('DB_USERNAME', 'root');
    $pass = contract_env('DB_PASSWORD', '');
    $db = contract_env('DB_TEST_DATABASE', '6valley_test');
    $socket = (string) contract_env('DB_SOCKET', '');
    $dump = dirname(__DIR__, 2).'/installation/backup/database.sql';

    $mysqli = mysqli_init();
    // Prefer the socket when one is configured — a MAMP/socket-only server refuses
    // TCP, which would kill the whole run at bootstrap. Same order as import_snapshot_db.php.
    if ($socket !== '') {
        $mysqli->real_connect('localhost', $user, $pass, '', null, $socket);
    } else {
        $mysqli->real_connect($host, $user, $pass, '', $port);
    }
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($db);

    $fresh = contract_env('FRESH_TEST_DB', '0') === '1';
    $hasSchema = (bool) $mysqli->query(
        "SELECT 1 FROM information_schema.tables WHERE table_schema='{$db}' AND table_name='business_settings' LIMIT 1"
    )->num_rows;

    if ($hasSchema && ! $fresh) {
        $mysqli->close();

        return;
    }

    if (! is_readable($dump)) {
        throw new RuntimeException("Contract test dump not found at {$dump}");
    }

    $sql = "SET FOREIGN_KEY_CHECKS=0;\n".file_get_contents($dump)."\nSET FOREIGN_KEY_CHECKS=1;\n";
    if (! $mysqli->multi_query($sql)) {
        throw new RuntimeException('Dump import failed: '.$mysqli->error);
    }
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->error) {
        throw new RuntimeException('Dump import error: '.$mysqli->error);
    }
    $mysqli->close();

    // The dump is a schema SNAPSHOT; production applies pending migrations on top
    // ("import then migrate"). Run the delta so migration-added columns/tables
    // (e.g. categories.business_module, courier_shipments) exist in the test DB.
    migrate_contract_test_db();
}

/**
 * Applies pending core + module migrations to the testing connection. Only the
 * delta runs (the dump ships a populated `migrations` table), so this is fast
 * and idempotent. Throws on failure so a broken schema fails loudly at bootstrap.
 *
 * DB_DATABASE is exported alongside --database because the flag only repoints
 * the migration repository. A migration body calling Schema:: without naming a
 * connection still resolves the DEFAULT one, so without this the schema lands in
 * the development database while the test database records the migration as run
 * — leaving tables silently missing here.
 */
function migrate_contract_test_db(): void
{
    $basePath = dirname(__DIR__, 2);
    $database = contract_env('DB_TEST_DATABASE', '6valley_test');
    $environment = 'DB_DATABASE='.escapeshellarg($database).' ';

    $commands = [
        'migrate --database=mysql_testing --force',
        'module:migrate --database=mysql_testing --force',
    ];
    foreach ($commands as $command) {
        $full = $environment.escapeshellarg(PHP_BINARY).' artisan '.$command.' 2>&1';
        $output = [];
        $exitCode = 0;
        exec('cd '.escapeshellarg($basePath).' && '.$full, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException(
                "Test DB migration failed (`{$command}`):\n".implode("\n", $output)
            );
        }
    }
}

import_contract_test_db();
