<?php

namespace Tests\Feature\Api\Support;

use Dotenv\Dotenv;
use PDO;
use PDOException;

/**
 * Resolves the snapshot test database and points the booting application at it.
 *
 * The API snapshot harness runs against a full clone of a working 6valley
 * database (schema plus data) because the application reads its configuration
 * from the database at boot. The clone is produced by
 * tests/bootstrap/import_snapshot_db.php and is never written to permanently:
 * every snapshot test runs inside a transaction that is rolled back.
 *
 * It is a different database from the Contract suite's `6valley_test`, which
 * tests/bootstrap/import_test_db.php fills from the install dump — snapshots
 * need the richer data of a working database.
 */
final class TestDatabase
{
    private static ?array $environment = null;

    public static function basePath(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * The database the clone is taken from — the developer's working database.
     */
    public static function sourceDatabase(): string
    {
        return self::env('SNAPSHOT_SOURCE_DB') ?: (self::env('DB_DATABASE') ?: 'forge');
    }

    /**
     * The database the snapshot tests run against. Must end with `_test` so the
     * import script can never overwrite a working database by accident.
     */
    public static function testDatabase(): string
    {
        return self::env('SNAPSHOT_TEST_DB') ?: self::sourceDatabase().'_snapshot_test';
    }

    public static function connection(): array
    {
        return [
            'host' => self::env('DB_HOST') ?: '127.0.0.1',
            'port' => self::env('DB_PORT') ?: '3306',
            'socket' => self::env('DB_SOCKET') ?: '',
            'username' => self::env('DB_USERNAME') ?: 'root',
            'password' => self::env('DB_PASSWORD') ?: '',
        ];
    }

    /**
     * Points the booting application at the snapshot clone, reusing the
     * `mysql_testing` connection phpunit.xml already selects.
     */
    public static function useTestDatabase(): void
    {
        $overrides = [
            'DB_CONNECTION' => 'mysql_testing',
            'DB_TEST_DATABASE' => self::testDatabase(),
        ];

        foreach ($overrides as $key => $value) {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    /**
     * Returns why the harness cannot run, or null when the clone is usable.
     */
    public static function unavailableReason(): ?string
    {
        $database = self::testDatabase();

        try {
            $connection = self::pdo($database);
        } catch (PDOException $exception) {
            return sprintf(
                'Snapshot database "%s" is not reachable (%s). Run: php tests/bootstrap/import_snapshot_db.php',
                $database,
                $exception->getMessage()
            );
        }

        $tables = (int) $connection->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '.$connection->quote($database))->fetchColumn();

        if ($tables === 0) {
            return sprintf(
                'Snapshot database "%s" is empty. Run: php tests/bootstrap/import_snapshot_db.php',
                $database
            );
        }

        return null;
    }

    public static function pdo(?string $database = null): PDO
    {
        $connection = self::connection();

        $dsn = $connection['socket'] !== ''
            ? 'mysql:unix_socket='.$connection['socket']
            : 'mysql:host='.$connection['host'].';port='.$connection['port'];

        if ($database !== null) {
            $dsn .= ';dbname='.$database;
        }

        return new PDO($dsn, $connection['username'], $connection['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * Reads the process environment and then .env. $_SERVER is deliberately
     * ignored: phpunit.xml pins the test connection there, and
     * useTestDatabase() rewrites the same keys once the clone is resolved.
     *
     * Public because the booting application is configured before Laravel loads
     * .env — see Support\InteractsWithApiTestEnvironment.
     */
    public static function env(string $key): ?string
    {
        if (self::$environment === null) {
            self::$environment = Dotenv::createArrayBacked(self::basePath())->safeLoad();
        }

        $value = getenv($key);

        if ($value === false || $value === '') {
            $value = self::$environment[$key] ?? null;
        }

        return $value === null ? null : (string) $value;
    }
}
