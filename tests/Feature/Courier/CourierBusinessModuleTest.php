<?php

namespace Tests\Feature\Courier;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\Seller;
use App\Services\Courier\HostCourierOwnerResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Courier\app\DataTransferObjects\Requests\OrderData;
use Modules\Courier\app\DataTransferObjects\Requests\QuoteData;
use Modules\Courier\app\DataTransferObjects\Requests\RecipientData;
use Modules\Courier\app\DataTransferObjects\Responses\QuoteResult;
use Modules\Courier\app\DataTransferObjects\Responses\ShipmentResult;
use Modules\Courier\app\Enums\ShipmentStatus;
use Modules\Courier\app\Events\ShipmentDispatched;
use Modules\Courier\app\Events\ShipmentStatusUpdated;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierDispatchController as SellerV6CourierDispatchController;
use Modules\Courier\app\Http\Controllers\SellerApi\CourierConfigController as SellerApiCourierConfigController;
use Modules\Courier\app\Http\Requests\Admin\EstimateCourierChargeRequest;
use Modules\Courier\app\Http\Requests\SaveCourierProviderRequest;
use Modules\Courier\app\Models\CourierProviderSetting;
use Modules\Courier\app\Models\CourierShipment;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\CourierService;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\ValueObjects\CourierOwner;
use Modules\Courier\CourierProviders\Contracts\CalculatesPrice;
use Modules\Courier\CourierProviders\Contracts\CreatesOrders;
use Modules\Courier\CourierProviders\Contracts\TracksOrders;
use Modules\Courier\CourierProviders\CourierProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class CourierBusinessModuleTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const PROVIDER = 'ledger_partner';

    private const RETAIL_TOKEN = 'retail-account-token';

    private const WHOLESALE_TOKEN = 'wholesale-account-token';

    private const DISPATCH_ROUTE = 'admin.settings.courier.dispatch';

    private const TRACK_ROUTE = 'admin.settings.courier.track';

    private const CONFIG_UPDATE_ROUTE = 'admin.settings.courier.config.update';

    private const V3_CONFIG_PATH = 'api/v3/seller/courier/config';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        if (! addon_published_status('Courier')) {
            $this->markTestSkipped('Courier addon is not published.');
        }

        config(['courier.providers' => [self::PROVIDER => LedgerPartnerProvider::class]]);

        Event::fake([ShipmentDispatched::class, ShipmentStatusUpdated::class]);

        LedgerPartnerProvider::$credentialsSeen = [];
    }

    public function test_existing_retail_route_keys_are_unchanged(): void
    {
        $this->assertSame('platform-0', CourierOwner::platform()->routeKey());
        $this->assertSame('vendor-12', CourierOwner::vendor(id: 12)->routeKey());
        $this->assertTrue(CourierOwner::fromRouteKey('vendor-12')->equals(CourierOwner::vendor(id: 12)));
        $this->assertTrue(CourierOwner::fromRouteKey(null)->equals(CourierOwner::platform()));
        $this->assertSame('vendor-12-wholesale', CourierOwner::vendor(id: 12, businessModule: BusinessModule::WHOLESALE)->routeKey());
        $this->assertTrue(CourierOwner::fromRouteKey('vendor-12-wholesale')->equals(CourierOwner::vendor(id: 12, businessModule: BusinessModule::WHOLESALE)));

        $seller = new Seller;
        $seller->id = 77;
        $this->app['auth']->guard('seller')->setUser($seller);

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();

            if (! str_contains($name, 'courier.')) {
                continue;
            }

            $owner = $this->resolveOwnerOn(routeName: $name);
            $isVendorRoute = str_starts_with($name, 'vendor.');

            $this->assertSame(! $isVendorRoute, $owner->isPlatform(), "Route [{$name}] resolves the wrong owner.");
            $this->assertSame(BusinessModule::RETAIL, $owner->businessModule, "Route [{$name}] no longer resolves retail.");
            $this->assertSame($isVendorRoute ? 'vendor-77' : 'platform-0', $owner->routeKey(), "Route [{$name}] changed its route key.");
        }
    }

    public function test_vendor_can_hold_retail_and_wholesale_settings_for_one_provider(): void
    {
        $retail = CourierOwner::vendor(id: 77);
        $wholesale = $retail->inBusinessModule(BusinessModule::WHOLESALE);

        $this->configService()->save(data: $this->settingData(token: self::RETAIL_TOKEN), owner: $retail);
        $this->configService()->save(data: $this->settingData(token: self::WHOLESALE_TOKEN), owner: $wholesale);

        $this->assertSame(2, CourierProviderSetting::query()->where('owner_type', 'vendor')->where('owner_id', 77)->where('provider', self::PROVIDER)->count());
        $this->assertSame(self::RETAIL_TOKEN, $this->configService()->findByProvider(provider: self::PROVIDER, owner: $retail)->credentials['token']);
        $this->assertSame(self::WHOLESALE_TOKEN, $this->configService()->findByProvider(provider: self::PROVIDER, owner: $wholesale)->credentials['token']);
        $this->assertSame(BusinessModule::WHOLESALE, $this->configService()->findByProvider(provider: self::PROVIDER, owner: $wholesale)->business_module);
    }

    public function test_wholesale_order_dispatch_uses_wholesale_settings(): void
    {
        $this->enableBothAccounts();
        $order = $this->createOrder(businessModule: BusinessModule::WHOLESALE);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $order->id]);

        $this->courier()->ship($this->orderData(hostOrderReference: (string) $order->id), self::PROVIDER);

        $this->assertSame([self::WHOLESALE_TOKEN], LedgerPartnerProvider::$credentialsSeen);
    }

    public function test_retail_order_dispatch_uses_retail_settings(): void
    {
        $this->enableBothAccounts();
        $order = $this->createOrder(businessModule: BusinessModule::RETAIL);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $order->id]);

        $this->courier()->ship($this->orderData(hostOrderReference: (string) $order->id), self::PROVIDER);

        $this->assertSame([self::RETAIL_TOKEN], LedgerPartnerProvider::$credentialsSeen);
        $this->assertTrue($this->app->make(ProviderRegistry::class)->owner()->equals(CourierOwner::platform()));
    }

    public function test_shipment_is_stamped_with_order_business_module(): void
    {
        $this->enableBothAccounts();
        $wholesaleOrder = $this->createOrder(businessModule: BusinessModule::WHOLESALE);
        $retailOrder = $this->createOrder(businessModule: BusinessModule::RETAIL);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $wholesaleOrder->id]);
        $this->courier()->ship($this->orderData(hostOrderReference: (string) $wholesaleOrder->id), self::PROVIDER);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $retailOrder->id]);
        $this->courier()->ship($this->orderData(hostOrderReference: (string) $retailOrder->id), self::PROVIDER);

        $this->assertSame(BusinessModule::WHOLESALE, CourierShipment::query()->forHostReference((string) $wholesaleOrder->id)->value('business_module'));
        $this->assertSame(BusinessModule::RETAIL, CourierShipment::query()->forHostReference((string) $retailOrder->id)->value('business_module'));
    }

    public function test_track_rebuilds_registry_with_shipment_module(): void
    {
        $this->enableBothAccounts();
        $order = $this->createOrder(businessModule: BusinessModule::WHOLESALE);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $order->id]);
        $shipment = $this->courier()->ship($this->orderData(hostOrderReference: (string) $order->id), self::PROVIDER);

        LedgerPartnerProvider::$credentialsSeen = [];
        $this->onRoute(routeName: self::TRACK_ROUTE);

        $this->assertTrue($this->app->make(ProviderRegistry::class)->owner()->isRetail());

        $this->courier()->track($shipment->consignmentId);

        $this->assertSame([self::WHOLESALE_TOKEN], LedgerPartnerProvider::$credentialsSeen);
    }

    public function test_wholesale_order_without_wholesale_settings_is_not_enabled(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->enableAccount(owner: CourierOwner::platform(), token: self::RETAIL_TOKEN);
        $order = $this->createOrder(businessModule: BusinessModule::WHOLESALE);

        $this->onRoute(routeName: self::DISPATCH_ROUTE, input: ['host_order_reference' => (string) $order->id]);

        $this->assertSame([], $this->app->make(ProviderRegistry::class)->enabled());
        $this->assertFalse($this->app->make(CourierService::class)->isEnabled());
        $this->assertSame([], $this->app->make(ProviderRegistry::class)->forBusinessModule(BusinessModule::WHOLESALE)->enabledOptions());

        $this->onRoute(routeName: 'admin.wholesale.orders.details');

        $this->assertSame([], $this->app->make(ProviderRegistry::class)->enabled());
    }

    public function test_invalid_business_module_input_resolves_retail(): void
    {
        foreach ([BusinessModule::AUCTION, 'garbage', '', ['wholesale']] as $input) {
            $owner = $this->resolveOwnerOn(routeName: self::CONFIG_UPDATE_ROUTE, input: ['business_module' => $input]);

            $this->assertTrue($owner->equals(CourierOwner::platform()), 'business_module ['.json_encode($input).'] did not resolve retail.');
        }

        $this->onRoute(routeName: self::CONFIG_UPDATE_ROUTE, input: ['business_module' => BusinessModule::AUCTION]);

        $request = SaveCourierProviderRequest::create('/admin/settings/courier/config', 'PUT', [
            'provider' => self::PROVIDER,
            'is_enabled' => '0',
            'business_module' => BusinessModule::AUCTION,
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        try {
            $request->validateResolved();
            $this->fail('An auction business module was accepted on the courier config screen.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('business_module', $exception->errors());
        }
    }

    public function test_v3_seller_courier_config_is_unchanged(): void
    {
        $seller = new Seller;
        $seller->id = 77;
        $retail = CourierOwner::vendor(id: 77);
        $wholesale = $retail->inBusinessModule(BusinessModule::WHOLESALE);

        $this->enableAccount(owner: $retail, token: self::RETAIL_TOKEN);
        $this->enableAccount(owner: $wholesale, token: self::WHOLESALE_TOKEN);

        $expected = response()->json(['providers' => $this->app->make(ProviderRegistry::class)->forOwner($retail)->catalog()])->getContent();

        $this->onPath(method: 'GET', uri: self::V3_CONFIG_PATH, seller: $seller);
        $plain = $this->app->make(SellerApiCourierConfigController::class)->index()->getContent();

        $this->onPath(method: 'GET', uri: self::V3_CONFIG_PATH, seller: $seller, input: ['business_module' => BusinessModule::WHOLESALE]);
        $withModule = $this->app->make(SellerApiCourierConfigController::class)->index()->getContent();

        $this->assertSame($expected, $plain);
        $this->assertSame($plain, $withModule);
        $this->assertStringContainsString(self::RETAIL_TOKEN, $plain);
        $this->assertStringNotContainsString(self::WHOLESALE_TOKEN, $plain);

        $payload = [...$this->settingData(token: 'v3-rotated-token'), 'is_enabled' => '1', 'business_module' => BusinessModule::WHOLESALE];
        $request = $this->onPath(method: 'PUT', uri: self::V3_CONFIG_PATH, seller: $seller, input: $payload);
        $saveRequest = SaveCourierProviderRequest::createFrom($request);
        $saveRequest->setContainer($this->app);
        $saveRequest->setRedirector($this->app->make(Redirector::class));
        $saveRequest->validateResolved();
        $this->app->make(SellerApiCourierConfigController::class)->update($saveRequest);

        $this->assertSame('v3-rotated-token', $this->configService()->findByProvider(provider: self::PROVIDER, owner: $retail)->credentials['token']);
        $this->assertSame(self::WHOLESALE_TOKEN, $this->configService()->findByProvider(provider: self::PROVIDER, owner: $wholesale)->credentials['token']);
    }

    public function test_the_request_business_module_follows_the_order_reference(): void
    {
        $order = Order::query()->where('business_module', BusinessModule::WHOLESALE)->first();

        if ($order === null) {
            $this->markTestSkipped('No wholesale order to reference.');
        }

        $this->app->instance('request', Request::create('/admin/settings/courier/dispatch', 'POST', [
            HostCourierOwnerResolver::ORDER_REFERENCE_INPUT => (string) $order->id,
        ]));

        $this->assertSame(BusinessModule::WHOLESALE, deliveryPartnerRequestBusinessModule());
    }

    public function test_the_request_business_module_is_retail_when_nothing_names_another(): void
    {
        $this->app->instance('request', Request::create('/admin/settings/courier/dispatch', 'POST'));

        $this->assertSame(BusinessModule::RETAIL, deliveryPartnerRequestBusinessModule());
    }

    public function test_the_v6_seller_estimate_endpoint_does_not_call_missing_methods(): void
    {
        $source = file_get_contents(base_path('Modules/Courier/app/Http/Controllers/RestAPI/v6/Seller/CourierDispatchController.php'));

        $this->assertMatchesRegularExpression('/function estimate\(.*?\n    \}/s', $source);
        preg_match('/function estimate\(.*?\n    \}/s', $source, $matches);
        $estimateMethod = $matches[0];

        $this->assertStringNotContainsString('estimateDeliveryCharge', $estimateMethod);
        $this->assertStringNotContainsString('toOrderData()', $estimateMethod);
        $this->assertStringContainsString('toQuoteData()', $estimateMethod);
        $this->assertStringContainsString('->estimate(', $estimateMethod);
    }

    public function test_the_v6_seller_estimate_endpoint_returns_a_supported_quote(): void
    {
        config(['courier.providers' => [self::PROVIDER => QuotingLedgerPartnerProvider::class]]);
        $this->enableAccount(owner: CourierOwner::platform(), token: self::RETAIL_TOKEN);
        $this->app->instance('request', Request::create('/api/v6/seller/courier/estimate', 'POST'));
        $this->refreshCourierSingletons();

        $response = $this->app->make(SellerV6CourierDispatchController::class)->estimate($this->estimateRequest());
        $payload = json_decode($response->getContent(), true);

        $this->assertTrue($payload['data']['supported']);
        $this->assertSame(42.5, $payload['data']['total']);
        $this->assertSame('BDT', $payload['data']['currency']);
    }

    public function test_the_v6_seller_estimate_endpoint_reports_unsupported_when_the_provider_cannot_quote(): void
    {
        $this->enableAccount(owner: CourierOwner::platform(), token: self::RETAIL_TOKEN);
        $this->app->instance('request', Request::create('/api/v6/seller/courier/estimate', 'POST'));
        $this->refreshCourierSingletons();

        $response = $this->app->make(SellerV6CourierDispatchController::class)->estimate($this->estimateRequest());
        $payload = json_decode($response->getContent(), true);

        $this->assertFalse($payload['data']['supported']);
        $this->assertArrayNotHasKey('total', $payload['data']);
    }

    private function estimateRequest(): EstimateCourierChargeRequest
    {
        $request = EstimateCourierChargeRequest::create('/api/v6/seller/courier/estimate', 'POST', [
            'provider' => self::PROVIDER,
            'weight' => 1.5,
            'cod_amount' => 100,
        ]);
        $request->setContainer($this->app);

        return $request;
    }

    private function resolveOwnerOn(string $routeName, array $input = []): CourierOwner
    {
        $this->onRoute(routeName: $routeName, input: $input);

        return $this->app->make(HostCourierOwnerResolver::class)->resolve();
    }

    private function onRoute(string $routeName, array $input = []): void
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

        $request = $this->app['request'];
        $request->query->replace($input);
        $request->request->replace($input);
        $request->setRouteResolver(fn () => $route);

        $this->refreshCourierSingletons();
    }

    private function onPath(string $method, string $uri, Seller $seller, array $input = []): Request
    {
        $request = Request::create('/'.$uri, $method, $input);
        $request['seller'] = $seller;
        $this->app->instance('request', $request);

        $this->refreshCourierSingletons();

        return $request;
    }

    private function refreshCourierSingletons(): void
    {
        foreach ([ProviderRegistry::class, CourierService::class] as $singleton) {
            $this->app->forgetInstance($singleton);
        }
    }

    private function enableBothAccounts(): void
    {
        $this->enableAccount(owner: CourierOwner::platform(), token: self::RETAIL_TOKEN);
        $this->enableAccount(owner: CourierOwner::platform(businessModule: BusinessModule::WHOLESALE), token: self::WHOLESALE_TOKEN);
    }

    private function enableAccount(CourierOwner $owner, string $token): void
    {
        $this->configService()->save(data: $this->settingData(token: $token), owner: $owner);
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

    private function courier(): CourierService
    {
        return $this->app->make(CourierService::class);
    }

    private function createOrder(string $businessModule): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + 1,
            'business_module' => $businessModule,
            'seller_id' => 1,
            'seller_is' => 'admin',
        ])->save();

        return $order;
    }

    private function orderData(string $hostOrderReference): OrderData
    {
        return new OrderData(
            hostOrderReference: $hostOrderReference,
            recipient: new RecipientData(name: 'Test Receiver', phone: '01700000000', address: '1/B/3 Farmgate, Dhaka'),
            codAmount: 250.0,
            weight: 1.5,
        );
    }
}

class LedgerPartnerProvider extends CourierProvider implements CreatesOrders, TracksOrders
{
    /** @var array<int, string|null> */
    public static array $credentialsSeen = [];

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

    public function createOrder(OrderData $order): ShipmentResult
    {
        static::$credentialsSeen[] = $this->credentials['token'] ?? null;

        return new ShipmentResult(
            consignmentId: 'ledger-'.$order->hostOrderReference,
            status: ShipmentStatus::Pending,
            trackingCode: 'ledger-track-'.$order->hostOrderReference,
        );
    }

    public function trackOrder(string $consignmentId): array
    {
        static::$credentialsSeen[] = $this->credentials['token'] ?? null;

        return [];
    }
}

class QuotingLedgerPartnerProvider extends CourierProvider implements CalculatesPrice
{
    public function getName(): string
    {
        return 'ledger_partner';
    }

    public function calculatePrice(QuoteData $data): QuoteResult
    {
        return new QuoteResult(totalCost: 42.5, currency: 'BDT');
    }
}
