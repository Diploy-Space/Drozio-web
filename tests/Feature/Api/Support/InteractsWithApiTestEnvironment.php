<?php

namespace Tests\Feature\Api\Support;

use App\Models\DeliveryMan;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Laravel\Passport\Passport;

/**
 * Boots the application the way both API harnesses need it: against the
 * imported database clone, with production-shaped error bodies and the module
 * helper redeclaration warning suppressed.
 *
 * Shared by the snapshot harness (Tests\Feature\Api\ApiSnapshotTestCase) and
 * the V4 parity harness (Tests\Feature\Api\V4\ApiParityTestCase).
 */
trait InteractsWithApiTestEnvironment
{
    /**
     * Contents of the language files the `translate()` helper appends to while
     * the application runs, so a test run never dirties the working tree.
     *
     * @var array<string, string>
     */
    private array $languageFiles = [];

    public function createApplication()
    {
        $this->useApiTestEnvironment();

        $app = require TestDatabase::basePath().'/bootstrap/app.php';

        $this->tolerateModuleFileRedeclarations($app);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * The API test environment differs from the developer's .env in four ways:
     * the database is the imported clone, APP_URL drops the local sub-directory
     * so test requests hit `/api/...` as deployed, mail is captured in memory,
     * and debug is off so error bodies carry the production shape instead of a
     * stack trace. Export SNAPSHOT_DEBUG=1 while writing a new test to see the
     * trace.
     */
    private function useApiTestEnvironment(): void
    {
        TestDatabase::useTestDatabase();

        $overrides = [
            'APP_URL' => $this->apiTestBaseUrl(),
            'APP_DEBUG' => getenv('SNAPSHOT_DEBUG') === '1' ? 'true' : 'false',
            'MAIL_MAILER' => 'array',
        ];

        foreach ($overrides as $key => $value) {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    /**
     * Every host-level route file — including all of `api/*` — is wrapped in
     * `Route::domain(config('app.host_domain'))` when APP_HOST_DOMAIN is set, so
     * a test request sent to any other host resolves to 404 instead of the
     * endpoint. The base URL therefore follows the configured host.
     */
    private function apiTestBaseUrl(): string
    {
        $hostDomain = TestDatabase::env('APP_HOST_DOMAIN');

        return 'http://'.($hostDomain !== null && $hostDomain !== '' ? $hostDomain : 'localhost');
    }

    /**
     * Modules declare global helper files in module.json and laravel-modules
     * `include`s them on every registration. Booting the application once per
     * test therefore re-declares their constants, which Laravel's error handler
     * turns into a fatal ErrorException. The redeclaration is harmless — the
     * values are identical — so the warning is swallowed while providers
     * register and boot, and every other diagnostic keeps its normal behaviour.
     */
    private function tolerateModuleFileRedeclarations(Application $app): void
    {
        $laravelHandler = null;

        $ignoreRedeclarations = function () use (&$laravelHandler) {
            $laravelHandler = set_error_handler(function (int $level, string $message, string $file = '', int $line = 0) use (&$laravelHandler): bool {
                if ($level === E_WARNING && str_contains($message, 'already defined')) {
                    return true;
                }

                return $laravelHandler !== null && (bool) call_user_func($laravelHandler, $level, $message, $file, $line);
            }, E_ALL);
        };

        foreach ([RegisterProviders::class, BootProviders::class] as $bootstrapper) {
            $app->beforeBootstrapping($bootstrapper, $ignoreRedeclarations);
            $app->afterBootstrapping($bootstrapper, fn () => restore_error_handler());
        }
    }

    /**
     * Skips instead of failing when the imported clone is missing — an absent
     * database is an environment gap, not a contract regression.
     */
    protected function skipWhenApiDatabaseUnavailable(): void
    {
        $reason = TestDatabase::unavailableReason();

        if ($reason !== null) {
            $this->markTestSkipped($reason);
        }
    }

    /**
     * `apiGuestCheck` accepts a request that carries a `guest_id` parameter, so
     * guest calls merge these into the query string or body.
     *
     * @return array<string, int>
     */
    protected function guestParams(): array
    {
        return ['guest_id' => $this->guestId()];
    }

    protected function guestId(): int
    {
        return ApiTestData::guestId();
    }

    /**
     * Authenticates as a customer for the `auth:api` / `apiGuestCheck` routes.
     * The bearer header is what `apiGuestCheck` inspects; Passport resolves the
     * actual identity.
     *
     * @return array<string, string>
     */
    protected function actingAsCustomer(User $customer): array
    {
        Passport::actingAs($customer, [], 'api');

        return ['Authorization' => 'Bearer snapshot-harness-token'];
    }

    /**
     * Headers for the v3 seller app, which authenticates on `sellers.auth_token`.
     *
     * @return array<string, string>
     */
    protected function sellerHeaders(Seller $seller): array
    {
        return ['Authorization' => 'Bearer '.$seller->auth_token];
    }

    /**
     * Headers for the delivery-man app, which authenticates on
     * `delivery_men.auth_token` the same way the seller app does.
     *
     * @return array<string, string>
     */
    protected function deliveryManHeaders(DeliveryMan $deliveryMan): array
    {
        return ['Authorization' => 'Bearer '.$deliveryMan->auth_token];
    }

    protected function requireFixture(mixed $fixture, string $description): mixed
    {
        if ($fixture === null) {
            $this->markTestSkipped('Test database has no '.$description.'.');
        }

        return $fixture;
    }

    protected function rememberLanguageFiles(): void
    {
        $this->languageFiles = [];

        foreach (glob(base_path('resources/lang/*/{new-messages,lang}.php'), GLOB_BRACE) ?: [] as $path) {
            $this->languageFiles[$path] = (string) file_get_contents($path);
        }
    }

    protected function restoreLanguageFiles(): void
    {
        foreach ($this->languageFiles as $path => $contents) {
            if (is_file($path) && file_get_contents($path) !== $contents) {
                file_put_contents($path, $contents);
            }
        }

        $this->languageFiles = [];
    }
}
