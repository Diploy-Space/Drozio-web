<?php

namespace Tests\Feature\Contract;

use App\Services\Cache\AppCache;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Contract\Concerns\AssertsJsonContract;
use Tests\TestCase;

/**
 * Base class for mobile-API contract tests.
 *
 * Runs against the `mysql_testing` connection seeded from
 * installation/backup/database.sql (imported once by tests/bootstrap.php).
 * Each test runs inside a transaction so per-test fixtures roll back and the
 * seeded baseline is preserved between tests.
 *
 * Contract tests capture the CURRENT response shape exactly — including any
 * existing quirks or bugs. They must not be "corrected" to a desired shape.
 */
abstract class ContractTestCase extends TestCase
{
    use AssertsJsonContract;
    use DatabaseTransactions;

    protected string $apiPrefix = 'api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        // Per-request helper memoization is static; clear it so no memoized DB
        // result (currency rows, settings, shipping methods…) leaks across tests.
        Helpers::resetMemo();
        // AppCache lives in the array store which persists across tests in one
        // process; clear its groups so a rolled-back fixture can't leak via cache.
        AppCache::forgetAllGroups();
        $this->bootActiveTheme();
    }

    protected function tearDown(): void
    {
        Helpers::resetMemo();
        AppCache::forgetAllGroups();
        parent::tearDown();
    }

    /**
     * ThemeServiceProvider only defines VIEW_FILE_NAMES and registers the theme
     * view path when NOT running in console; under PHPUnit that guard skips it,
     * so web (Blade) contract tests need the same setup replicated here.
     */
    private function bootActiveTheme(): void
    {
        $theme = env('WEB_THEME') ?: 'default';
        $path = base_path('resources/themes/' . $theme);
        if (! defined('VIEW_FILE_NAMES') && is_file($path . '/file_names.php')) {
            define('VIEW_FILE_NAMES', include $path . '/file_names.php');
        }
        $this->app['view']->addLocation($path);
    }

    protected function apiGet(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->getJson($this->url($uri), $this->defaultHeaders($headers));
    }

    protected function apiPost(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($this->url($uri), $data, $this->defaultHeaders($headers));
    }

    protected function url(string $uri): string
    {
        $uri = ltrim($uri, '/');
        return str_starts_with($uri, 'api/') ? $uri : "{$this->apiPrefix}/{$uri}";
    }

    private function defaultHeaders(array $headers): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'X-localization' => 'en',
        ], $headers);
    }
}
