<?php

namespace Tests\Feature\Courier;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\Seller;
use App\Services\Courier\HostCourierOwnerResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;
use Modules\Courier\app\DataTransferObjects\Responses\Location;
use Modules\Courier\app\Http\Controllers\Admin\CourierLocationController as SharedLocationController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierConfigController as RetailConfigController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierDispatchController as RetailDispatchController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierProviderController as RetailProviderController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller\CourierConfigController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller\CourierDispatchController as DispatchController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller\CourierLocationController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Wholesale\Seller\CourierProviderController;
use Modules\Courier\app\Http\Requests\Admin\EstimateCourierChargeRequest;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\CourierService;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\Services\New\Wholesale\CourierConfigService as WholesaleCourierConfigService;
use Modules\Courier\app\ValueObjects\CourierOwner;
use Modules\Courier\CourierProviders\Contracts\ResolvesLocations;
use Modules\Courier\CourierProviders\CourierProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class WholesaleSellerCourierApiTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PROVIDER = 'ledger_partner';

    private const SELLER_ID = 77;

    private const RETAIL_TOKEN = 'retail-account-token';

    private const WHOLESALE_TOKEN = 'wholesale-account-token';

    private const WHOLESALE_PREFIX = 'api/v6/wholesale/seller/courier';

    private const RETAIL_CONFIG_PATH = 'api/v6/seller/courier/config';

    private const GATED_ENDPOINTS = [
        ['GET', 'config'],
        ['PUT', 'config'],
        ['GET', 'providers'],
        ['GET', 'locations/stores'],
        ['GET', 'locations/cities'],
        ['GET', 'locations/zones/{cityId}'],
        ['GET', 'locations/areas/{zoneId}'],
        ['POST', 'estimate'],
        ['POST', 'dispatch'],
        ['POST', 'revise'],
    ];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        if (! addon_published_status('Courier')) {
            $this->markTestSkipped('Courier addon is not published.');
        }

        config(['courier.providers' => [self::PROVIDER => WholesaleLedgerProvider::class]]);
    }

    public function test_every_wholesale_seller_courier_endpoint_is_registered_behind_the_seller_guard(): void
    {
        foreach ([...self::GATED_ENDPOINTS, ['GET', 'track/{consignmentId}']] as [$method, $path]) {
            $route = $this->routeFor(method: $method, uri: self::WHOLESALE_PREFIX.'/'.$path);

            $this->assertNotNull($route, "Route [{$method} {$path}] is not registered.");
            $this->assertContains('v6_seller_auth', $route->gatherMiddleware());
        }
    }

    public function test_only_tracking_sits_outside_the_delivery_partner_gate(): void
    {
        foreach (self::GATED_ENDPOINTS as [$method, $path]) {
            $route = $this->routeFor(method: $method, uri: self::WHOLESALE_PREFIX.'/'.$path);

            $this->assertContains('vendor_delivery_partner_setup', $route->gatherMiddleware(), "Route [{$method} {$path}] is not gated.");
        }

        $track = $this->routeFor(method: 'GET', uri: self::WHOLESALE_PREFIX.'/track/{consignmentId}');

        $this->assertNotContains('vendor_delivery_partner_setup', $track->gatherMiddleware());
    }

    public function test_the_wholesale_controllers_inherit_their_retail_counterparts(): void
    {
        $this->assertTrue(is_subclass_of(CourierConfigController::class, RetailConfigController::class));
        $this->assertTrue(is_subclass_of(CourierProviderController::class, RetailProviderController::class));
        $this->assertTrue(is_subclass_of(DispatchController::class, RetailDispatchController::class));
        $this->assertTrue(is_subclass_of(CourierLocationController::class, SharedLocationController::class));
    }

    public function test_the_wholesale_surface_resolves_the_sellers_wholesale_account(): void
    {
        $this->onPath(method: 'GET', uri: self::WHOLESALE_PREFIX.'/config');

        $this->assertSame('vendor-'.self::SELLER_ID.'-'.BusinessModule::WHOLESALE, $this->resolvedOwner()->routeKey());
    }

    public function test_the_wholesale_surface_ignores_a_business_module_input(): void
    {
        $this->onPath(
            method: 'GET',
            uri: self::WHOLESALE_PREFIX.'/config',
            input: [HostCourierOwnerResolver::BUSINESS_MODULE_INPUT => BusinessModule::RETAIL],
        );

        $this->assertSame(BusinessModule::WHOLESALE, $this->resolvedOwner()->businessModule);
    }

    public function test_the_retail_surface_still_resolves_the_retail_account(): void
    {
        $this->onPath(method: 'GET', uri: self::RETAIL_CONFIG_PATH);

        $this->assertSame('vendor-'.self::SELLER_ID, $this->resolvedOwner()->routeKey());
    }

    public function test_the_wholesale_catalog_reads_the_wholesale_account_only(): void
    {
        $this->enableBothAccounts();

        $this->onPath(method: 'GET', uri: self::WHOLESALE_PREFIX.'/config');
        $wholesale = $this->app->make(CourierConfigController::class)->index()->getContent();

        $this->onPath(method: 'GET', uri: self::RETAIL_CONFIG_PATH);
        $retail = $this->app->make(RetailConfigController::class)->index()->getContent();

        $this->assertStringContainsString(self::WHOLESALE_TOKEN, $wholesale);
        $this->assertStringNotContainsString(self::RETAIL_TOKEN, $wholesale);
        $this->assertStringContainsString(self::RETAIL_TOKEN, $retail);
        $this->assertStringNotContainsString(self::WHOLESALE_TOKEN, $retail);
    }

    public function test_saving_on_the_wholesale_surface_leaves_the_retail_account_untouched(): void
    {
        $this->enableBothAccounts();

        $request = $this->onPath(
            method: 'PUT',
            uri: self::WHOLESALE_PREFIX.'/config',
            input: $this->settingData(token: 'wholesale-rotated-token'),
        );

        $this->app->make(CourierConfigController::class)->update($this->saveRequestFrom($request));

        $this->assertSame('wholesale-rotated-token', $this->tokenFor($this->wholesaleOwner()));
        $this->assertSame(self::RETAIL_TOKEN, $this->tokenFor($this->retailOwner()));
    }

    public function test_the_wholesale_surface_does_not_let_a_seller_write_the_retail_account(): void
    {
        $this->enableBothAccounts();

        $request = $this->onPath(
            method: 'PUT',
            uri: self::WHOLESALE_PREFIX.'/config',
            input: [
                ...$this->settingData(token: 'crafted-token'),
                HostCourierOwnerResolver::BUSINESS_MODULE_INPUT => BusinessModule::RETAIL,
            ],
        );

        $this->app->make(CourierConfigController::class)->update($this->saveRequestFrom($request));

        $this->assertSame(self::RETAIL_TOKEN, $this->tokenFor($this->retailOwner()));
        $this->assertSame('crafted-token', $this->tokenFor($this->wholesaleOwner()));
    }

    public function test_the_wholesale_config_service_extends_the_retail_one(): void
    {
        $this->assertTrue(is_subclass_of(WholesaleCourierConfigService::class, CourierConfigService::class));
    }

    public function test_the_retail_surface_ignores_a_business_module_input(): void
    {
        $this->enableBothAccounts();

        $this->onPath(
            method: 'GET',
            uri: self::RETAIL_CONFIG_PATH,
            input: [HostCourierOwnerResolver::BUSINESS_MODULE_INPUT => BusinessModule::WHOLESALE],
        );

        $catalog = $this->app->make(RetailConfigController::class)->index()->getContent();

        $this->assertSame(BusinessModule::RETAIL, $this->resolvedOwner()->businessModule);
        $this->assertStringContainsString(self::RETAIL_TOKEN, $catalog);
        $this->assertStringNotContainsString(self::WHOLESALE_TOKEN, $catalog);
    }

    public function test_no_api_surface_validates_a_business_module_field(): void
    {
        foreach ([self::RETAIL_CONFIG_PATH, self::WHOLESALE_PREFIX.'/config'] as $uri) {
            $request = $this->onPath(
                method: 'PUT',
                uri: $uri,
                input: [
                    ...$this->settingData(token: 'token'),
                    HostCourierOwnerResolver::BUSINESS_MODULE_INPUT => BusinessModule::WHOLESALE,
                ],
            );

            $validated = $this->saveRequestFrom($request)->validated();

            $this->assertArrayNotHasKey(HostCourierOwnerResolver::BUSINESS_MODULE_INPUT, $validated, "[{$uri}] still validates a business module.");
        }
    }

    public function test_the_wholesale_dispatch_surface_refuses_a_retail_order(): void
    {
        $order = Order::query()->where('business_module', BusinessModule::RETAIL)->orderBy('id')->first();

        if ($order === null) {
            $this->markTestSkipped('No retail order to reference.');
        }

        $this->enableBothAccounts();

        $request = $this->onPath(
            method: 'POST',
            uri: self::WHOLESALE_PREFIX.'/estimate',
            input: [HostCourierOwnerResolver::ORDER_REFERENCE_INPUT => (string) $order->id],
        );

        $estimate = EstimateCourierChargeRequest::createFrom($request);
        $estimate->setContainer($this->app);

        $response = $this->app->make(DispatchController::class)->estimate($estimate);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_the_wholesale_location_endpoints_read_the_wholesale_account(): void
    {
        $this->enableBothAccounts();

        $this->onPath(method: 'GET', uri: self::WHOLESALE_PREFIX.'/locations/cities', input: ['provider' => self::PROVIDER]);
        $wholesale = $this->app->make(CourierLocationController::class)->cities($this->app['request'])->getContent();

        $this->assertStringContainsString(self::WHOLESALE_TOKEN, $wholesale);
        $this->assertStringNotContainsString(self::RETAIL_TOKEN, $wholesale);
    }

    public function test_the_retail_surface_queries_only_retail_rows(): void
    {
        $this->enableBothAccounts();
        $this->onPath(method: 'GET', uri: self::RETAIL_CONFIG_PATH);

        $rows = $this->app->make(CourierConfigService::class)->getList(dataLimit: 'all');

        $this->assertNotEmpty($rows);
        $this->assertSame([BusinessModule::RETAIL], $rows->pluck('business_module')->unique()->values()->all());
    }

    public function test_the_wholesale_service_queries_only_wholesale_rows_on_any_surface(): void
    {
        $this->enableBothAccounts();

        foreach ([self::RETAIL_CONFIG_PATH, self::WHOLESALE_PREFIX.'/config'] as $uri) {
            $this->onPath(method: 'GET', uri: $uri);

            $rows = $this->app->make(WholesaleCourierConfigService::class)->getList(dataLimit: 'all');

            $this->assertNotEmpty($rows);
            $this->assertSame([BusinessModule::WHOLESALE], $rows->pluck('business_module')->unique()->values()->all(), "[{$uri}] leaked a retail row.");
        }
    }

    public function test_a_service_cannot_reach_the_other_modules_row_by_id(): void
    {
        $this->enableBothAccounts();
        $this->onPath(method: 'GET', uri: self::WHOLESALE_PREFIX.'/config');

        $retailRowId = $this->app->make(CourierConfigService::class)
            ->findByProvider(provider: self::PROVIDER, owner: $this->retailOwner())->id;

        $this->assertNull($this->app->make(WholesaleCourierConfigService::class)->find(id: $retailRowId));
        $this->assertNull($this->app->make(WholesaleCourierConfigService::class)->update(id: $retailRowId, data: ['is_active' => false]));
        $this->assertFalse($this->app->make(WholesaleCourierConfigService::class)->delete(id: $retailRowId));
        $this->assertSame(self::RETAIL_TOKEN, $this->tokenFor($this->retailOwner()));
    }

    private function routeFor(string $method, string $uri): ?RegisteredRoute
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }

    private function onPath(string $method, string $uri, array $input = []): Request
    {
        $seller = new Seller;
        $seller->id = self::SELLER_ID;

        $request = Request::create('/'.$uri, $method, $input);
        $request['seller'] = $seller;
        $this->app->instance('request', $request);

        foreach ([ProviderRegistry::class, CourierService::class] as $singleton) {
            $this->app->forgetInstance($singleton);
        }

        return $request;
    }

    private function saveRequestFrom(Request $request): SaveCourierProviderRequest
    {
        $saveRequest = SaveCourierProviderRequest::createFrom($request);
        $saveRequest->setContainer($this->app);
        $saveRequest->setRedirector($this->app->make(Redirector::class));
        $saveRequest->validateResolved();

        return $saveRequest;
    }

    private function resolvedOwner(): CourierOwner
    {
        return $this->app->make(HostCourierOwnerResolver::class)->resolve();
    }

    private function retailOwner(): CourierOwner
    {
        return CourierOwner::vendor(id: self::SELLER_ID);
    }

    private function wholesaleOwner(): CourierOwner
    {
        return $this->retailOwner()->inBusinessModule(BusinessModule::WHOLESALE);
    }

    private function tokenFor(CourierOwner $owner): ?string
    {
        return $this->configService()->findByProvider(provider: self::PROVIDER, owner: $owner)?->credentials['token'] ?? null;
    }

    private function enableBothAccounts(): void
    {
        $this->configService()->save(data: $this->settingData(token: self::RETAIL_TOKEN), owner: $this->retailOwner());
        $this->configService()->save(data: $this->settingData(token: self::WHOLESALE_TOKEN), owner: $this->wholesaleOwner());
    }

    private function settingData(string $token): array
    {
        return [
            'provider' => self::PROVIDER,
            'is_enabled' => true,
            'credentials' => ['token' => $token],
            'environment' => CourierProvider::ENVIRONMENT_SANDBOX,
        ];
    }

    private function configService(): CourierConfigService
    {
        return $this->app->make(CourierConfigService::class);
    }
}

class WholesaleLedgerProvider extends CourierProvider implements ResolvesLocations
{
    public function getName(): string
    {
        return 'ledger_partner';
    }

    public function displayName(): string
    {
        return 'Ledger Partner';
    }

    public function supportedCountries(): array
    {
        return ['BD'];
    }

    public function credentialFields(): array
    {
        return [['key' => 'token', 'label' => 'Token', 'required' => true]];
    }

    public function getCities(): array
    {
        return [new Location(id: '1', name: (string) ($this->credentials['token'] ?? ''), level: 'city')];
    }

    public function getZones(string $cityId): array
    {
        return [];
    }

    public function getAreas(string $zoneId): array
    {
        return [];
    }
}
