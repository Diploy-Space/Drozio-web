<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Events\OrderPlacedEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\ShippingType;
use App\Models\User;
use App\Services\New\ColorService;
use App\Services\New\SettingService;
use App\Services\New\ShippingAddressService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Wholesale\app\Enums\CheckoutPaymentMethod;
use Modules\Wholesale\app\Events\CheckoutOrdersPlacingEvent;
use Modules\Wholesale\app\Events\DigitalPaymentLinkRequestedEvent;
use Modules\Wholesale\app\Events\DigitalPaymentOrdersPlacingEvent;
use Modules\Wholesale\app\Http\Controllers\RestAPI\v6\Customer\CartController as ApiCartController;
use Modules\Wholesale\app\Http\Controllers\RestAPI\v6\Customer\CheckoutController;
use Modules\Wholesale\app\Http\Controllers\RestAPI\v6\Customer\OrderPlacementController;
use Modules\Wholesale\app\Http\Controllers\Web\Orders\CartController;
use Modules\Wholesale\app\Http\Controllers\Web\Orders\CheckoutCompleteController;
use Modules\Wholesale\app\Http\Controllers\Web\Orders\PaymentRequestController;
use Modules\Wholesale\app\Listeners\CheckoutOrderPlacementListener;
use Modules\Wholesale\app\Listeners\DigitalPaymentLinkListener;
use Modules\Wholesale\app\Listeners\DigitalPaymentReceivedListener;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\CheckoutService;
use Modules\Wholesale\app\Services\CouponService;
use Modules\Wholesale\app\Services\CurrencyService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Modules\Wholesale\app\Utils\CheckoutViewSections;
use Modules\Wholesale\app\Utils\ProductViewSections;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class CheckoutRulesTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CHECKOUT_URI = '/api/v6/wholesale/customer/checkout';

    private const CASH_ON_DELIVERY_URI = '/api/v6/wholesale/customer/orders/cash-on-delivery';

    public const CURRENCY_CACHE_SESSION_KEYS = ['system_default_currency_info', 'currency_code', 'currency_symbol', 'currency_exchange_rate', 'usd', 'default'];

    private const WEB_CONTROLLER_NAMESPACE = 'Modules\\Wholesale\\app\\Http\\Controllers\\Web\\';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
        Event::fake([OrderPlacedEvent::class]);

        $this->rememberLanguageFiles();
        ShippingType::query()->where(['seller_id' => 0, 'context' => BusinessModule::WHOLESALE])->delete();
        ShippingType::query()->create(['seller_id' => 0, 'shipping_type' => ShippingTypeService::TYPE_CATEGORY_WISE, 'context' => BusinessModule::WHOLESALE]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_api_checkout_controllers_do_not_extend_web_controllers(): void
    {
        foreach ([CheckoutController::class, OrderPlacementController::class] as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            $this->assertSame(BaseApiController::class, $reflection->getParentClass()->getName(), $controllerClass);

            for ($parent = $reflection->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
                $this->assertStringStartsNotWith(self::WEB_CONTROLLER_NAMESPACE, $parent->getName(), $controllerClass);
            }
        }
    }

    public function test_web_and_api_checkout_share_one_checkout_service(): void
    {
        foreach ([CartController::class, CheckoutController::class, OrderPlacementController::class] as $class) {
            $this->assertContains(CheckoutService::class, $this->getConstructorTypes(class: $class), $class);
        }

        foreach ([CheckoutCompleteController::class, PaymentRequestController::class, OrderPlacementController::class] as $class) {
            $this->assertNotContains(CheckoutOrderPlacementListener::class, $this->getConstructorTypes(class: $class), $class);
        }

        $this->assertCount(1, Event::getListeners(CheckoutOrdersPlacingEvent::class));
        $this->assertCount(1, Event::getListeners(DigitalPaymentOrdersPlacingEvent::class));
        $this->assertCount(1, Event::getListeners(DigitalPaymentLinkRequestedEvent::class));
        $this->assertNull((new ReflectionClass(DigitalPaymentReceivedListener::class))->getConstructor());
        $this->assertNotNull((new ReflectionClass(CheckoutOrderPlacementListener::class))->getConstructor());
        $this->assertNotNull((new ReflectionClass(DigitalPaymentLinkListener::class))->getConstructor());
    }

    public function test_no_wholesale_service_injects_another_service(): void
    {
        $offenders = [];

        foreach ($this->wholesaleServiceClasses() as $class) {
            $constructor = (new ReflectionClass($class))->getConstructor();

            if ($constructor === null || $constructor->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $inheritedTypes = $this->parentConstructorTypes(class: $class);

            foreach ($constructor->getParameters() as $parameter) {
                $type = (string) $parameter->getType();

                if (str_ends_with($type, 'Service') && ! $this->isInheritedCollaborator(type: $type, inheritedTypes: $inheritedTypes)) {
                    $offenders[] = $class.'::$'.$parameter->getName();
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    /** @return array<int, string> */
    private function parentConstructorTypes(string $class): array
    {
        $parent = (new ReflectionClass($class))->getParentClass();

        return $parent === false || $parent->getConstructor() === null
            ? []
            : array_map(
                static fn (\ReflectionParameter $parameter): string => (string) $parameter->getType(),
                $parent->getConstructor()->getParameters(),
            );
    }

    /** @param  array<int, string>  $inheritedTypes */
    private function isInheritedCollaborator(string $type, array $inheritedTypes): bool
    {
        foreach ($inheritedTypes as $inheritedType) {
            if ($type === $inheritedType || is_subclass_of($type, $inheritedType)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, class-string> */
    private function wholesaleServiceClasses(): array
    {
        $classes = [];

        foreach (Finder::create()->files()->name('*.php')->in(base_path('Modules/Wholesale/app/Services')) as $file) {
            /** @var SplFileInfo $file */
            $class = 'Modules\\Wholesale\\app\\Services\\'.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));

            if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    public function test_api_cart_composes_through_the_checkout_service(): void
    {
        $apiCartTypes = $this->getConstructorTypes(class: ApiCartController::class);

        $this->assertContains(CheckoutService::class, $apiCartTypes);
        $this->assertNotContains(CouponService::class, $apiCartTypes);
        $this->assertNotContains(OrderService::class, $apiCartTypes);
    }

    public function test_digital_payment_amount_follows_the_retail_usd_conversion(): void
    {
        $currencyService = app(CurrencyService::class);
        $gateway = (string) $this->requireFixture(app(SettingService::class)->getActivePaymentGateways()->pluck('key_name')->first(), 'active payment gateway');
        $defaultCurrencyCode = $currencyService->find(id: getWebConfig(name: 'system_default_currency'))?->code;
        $gatewayPayment = $currencyService->getGatewayPayment(payableAmount: 250.0, paymentMethod: $gateway, currentCurrencyCode: $defaultCurrencyCode);

        if (getWebConfig(name: 'currency_model') === 'multi_currency') {
            $this->assertSame(usdToAnotherCurrencyConverter(currencyCode: $gatewayPayment['currency_code'], amount: 250.0), $gatewayPayment['amount']);
            $this->assertSame('EUR', $currencyService->getBringChangeAmountCurrencyCode(currentCurrencyCode: 'EUR'));
            $this->assertSame($defaultCurrencyCode, $currencyService->getBringChangeAmountCurrencyCode(currentCurrencyCode: null));
        } else {
            $this->assertSame(['currency_code' => $defaultCurrencyCode, 'amount' => 250.0], $gatewayPayment);
            $this->assertSame($defaultCurrencyCode, $currencyService->getBringChangeAmountCurrencyCode(currentCurrencyCode: 'EUR'));
        }

        $payload = app(OrderService::class)->buildPaymentPayload(customerId: 1, cartGroupIds: ['group'], groupCouponDiscounts: [], groupReferralDiscounts: ['group' => 12.5], payableAmount: 250.0, paymentPlatform: 'app', checkoutSelections: []);
        $this->assertSame(250.0, $payload[OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT]);
        $this->assertSame(['group' => 12.5], $payload['group_referral_discounts']);
    }

    public function test_api_checkout_never_touches_the_session(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);
        $body = ['address_id' => $address->id, 'billing_address_id' => $address->id, 'order_note' => 'Session free'];

        $this->useSessionStoreThatRefusesWrites();

        $this->getJson(self::CHECKOUT_URI.'?'.http_build_query($body), $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('content.data.is_purchasable', true);

        $this->postJson(self::CASH_ON_DELIVERY_URI, ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY, ...$body], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_placed_200');

        $this->assertFalse(Cart::query()->whereKey($row->id)->exists());
    }

    public function test_checkout_failure_reasons_come_from_the_cart_service(): void
    {
        $cartService = app(CartService::class);
        $customer = $this->customer();

        $this->assertSame(
            [CartService::REASON_CART_EMPTY],
            array_column($cartService->getCheckoutBlockerList(customerId: $customer->id, guestId: null, activeShippingMethodCounts: [], closedShopNames: [], hasUnavailableItems: false), 'reason'),
        );

        $row = $this->addRow(customer: $customer, quantity: 60);
        $cartItems = $cartService->getOwnerItems(customerId: $customer->id, guestId: null, relations: ['product']);

        $this->assertSame([], $cartService->getCheckoutBlockerList(customerId: $customer->id, guestId: null, activeShippingMethodCounts: [], closedShopNames: [], hasUnavailableItems: false));
        $this->assertSame(
            [['reason' => ProductViewSections::REASON_SHOP_CLOSED, 'shop_name' => 'Closed shop'], ['reason' => CartService::REASON_UNAVAILABLE_PRODUCT]],
            $cartService->getCheckoutBlockerList(customerId: $customer->id, guestId: null, activeShippingMethodCounts: [], closedShopNames: ['Closed shop'], hasUnavailableItems: true),
        );

        $this->assertNull($cartService->getCheckoutFailure(customerId: $customer->id, blockers: [], cartItems: $cartItems, quotes: [$row->id => $this->quoteLimits(minimum: 10, maximum: null)]));

        $blockers = [['reason' => CartService::REASON_UNAVAILABLE_PRODUCT]];
        $blocked = $cartService->getCheckoutFailure(customerId: $customer->id, blockers: $blockers, cartItems: $cartItems, quotes: []);
        $this->assertSame(['reason' => CartService::REASON_CHECKOUT_BLOCKED, 'blockers' => $blockers], $blocked);
        $this->assertSame([translate('Please remove this unavailable product to continue')], CheckoutViewSections::formatCheckoutFailureMessages(checkoutFailure: $blocked));

        $belowMinimum = $cartService->getCheckoutFailure(customerId: $customer->id, blockers: [], cartItems: $cartItems, quotes: [$row->id => $this->quoteLimits(minimum: 100, maximum: null)]);
        $this->assertSame(CartService::REASON_QUANTITY_OUT_OF_RANGE, $belowMinimum['reason']);
        $this->assertSame(100, $belowMinimum['minimum_order_qty']);
        $this->assertArrayNotHasKey('maximum_order_qty', $belowMinimum);

        $aboveMaximum = $cartService->getCheckoutFailure(customerId: $customer->id, blockers: [], cartItems: $cartItems, quotes: [$row->id => $this->quoteLimits(minimum: 10, maximum: 20)]);
        $this->assertSame(CartService::REASON_QUANTITY_OUT_OF_RANGE, $aboveMaximum['reason']);
        $this->assertSame(20, $aboveMaximum['maximum_order_qty']);
        $this->assertStringContainsString('20', CheckoutViewSections::formatCheckoutFailureMessages(checkoutFailure: $aboveMaximum)[0]);

        $addressService = app(ShippingAddressService::class);
        $address = $this->createAddress(customer: $customer);
        $this->assertTrue($addressService->hasDeliveryLocation(address: $address));
        $this->assertFalse($addressService->hasDeliveryLocation(address: $address->fill(['zip' => ''])));
        $this->assertNull($addressService->getDeliveryRestrictionReason(isCountryDeliverable: true, isZipDeliverable: true));
        $this->assertSame(
            match (true) {
                (bool) getWebConfig(name: 'delivery_country_restriction') => ShippingAddressService::REASON_DELIVERY_COUNTRY_UNAVAILABLE,
                (bool) getWebConfig(name: 'delivery_zip_code_area_restriction') => ShippingAddressService::REASON_DELIVERY_ZIP_UNAVAILABLE,
                default => null,
            },
            $addressService->getDeliveryRestrictionReason(isCountryDeliverable: false, isZipDeliverable: false),
        );
    }

    public function test_offered_payment_methods_come_from_the_order_service(): void
    {
        $orderService = app(OrderService::class);
        $customer = $this->customer();
        $customer->wallet_balance = 500;

        $offered = $orderService->getOfferedPaymentMethods(payableAmount: 400, customer: $customer);

        $this->assertSame($this->isSettingActive(name: 'cash_on_delivery'), $offered['cash_on_delivery']);
        $this->assertSame($this->isSettingActive(name: 'digital_payment'), $offered['digital']);
        $this->assertSame($this->isSettingActive(name: 'offline_payment'), $offered['offline']);
        $this->assertSame(['enabled' => $this->isSettingActive(name: 'wallet_status'), 'balance' => 500.0, 'payable_amount' => 400.0, 'remaining_balance' => 100.0, 'is_sufficient' => true], $offered['wallet']);
        $this->assertFalse($orderService->getOfferedPaymentMethods(payableAmount: 500.01, customer: $customer)['wallet']['is_sufficient']);
        $this->assertSame(0.0, $orderService->getOfferedPaymentMethods(payableAmount: 1, customer: null)['wallet']['balance']);

        $everythingOn = ['cash_on_delivery' => true, 'digital' => true, 'offline' => true, 'wallet' => ['enabled' => true, 'balance' => 10.0, 'is_sufficient' => false]];

        $this->assertTrue($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::CASH_ON_DELIVERY, gateway: null, digitalGatewayKeys: [], offlineMethodIds: []));
        $this->assertTrue($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::DIGITAL, gateway: 'stripe', digitalGatewayKeys: ['stripe'], offlineMethodIds: []));
        $this->assertFalse($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::DIGITAL, gateway: 'paypal', digitalGatewayKeys: ['stripe'], offlineMethodIds: []));
        $this->assertFalse($orderService->isPaymentMethodOffered(offeredPaymentMethods: [...$everythingOn, 'digital' => false], method: CheckoutPaymentMethod::DIGITAL, gateway: 'stripe', digitalGatewayKeys: ['stripe'], offlineMethodIds: []));
        $this->assertTrue($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::OFFLINE, gateway: '7', digitalGatewayKeys: [], offlineMethodIds: ['7']));
        $this->assertFalse($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::OFFLINE, gateway: null, digitalGatewayKeys: [], offlineMethodIds: ['7']));
        $this->assertFalse($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::WALLET, gateway: null, digitalGatewayKeys: [], offlineMethodIds: []));
        $this->assertFalse($orderService->isPaymentMethodOffered(offeredPaymentMethods: $everythingOn, method: 'unknown', gateway: null, digitalGatewayKeys: [], offlineMethodIds: []));

        $this->assertSame(
            OrderService::CHECKOUT_REASON_INSUFFICIENT_WALLET_BALANCE,
            $orderService->getPaymentFailureReason(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::WALLET, gateway: null, digitalGatewayKeys: [], offlineMethodIds: []),
        );
        $this->assertSame(
            OrderService::CHECKOUT_REASON_PAYMENT_METHOD_UNAVAILABLE,
            $orderService->getPaymentFailureReason(offeredPaymentMethods: [...$everythingOn, 'wallet' => ['enabled' => false, 'balance' => 10.0, 'is_sufficient' => false]], method: CheckoutPaymentMethod::WALLET, gateway: null, digitalGatewayKeys: [], offlineMethodIds: []),
        );
        $this->assertNull($orderService->getPaymentFailureReason(offeredPaymentMethods: $everythingOn, method: CheckoutPaymentMethod::CASH_ON_DELIVERY, gateway: null, digitalGatewayKeys: [], offlineMethodIds: []));

        $this->addRow(customer: $customer, quantity: 60);
        $data = $this->getJson(self::CHECKOUT_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');
        $offeredForCart = $orderService->getOfferedPaymentMethods(payableAmount: (float) $data['summary']['total'], customer: $customer);

        $this->assertSame($offeredForCart['cash_on_delivery'], $data['payment_methods']['cash_on_delivery']);
        $this->assertSame($offeredForCart['wallet']['enabled'], $data['payment_methods']['wallet']['enabled']);
        $this->assertSame($offeredForCart['wallet']['is_sufficient'], $data['payment_methods']['wallet']['is_sufficient']);
    }

    /** @return array<int, string> */
    private function getConstructorTypes(string $class): array
    {
        return array_map(
            static fn (\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            (new ReflectionClass($class))->getConstructor()->getParameters(),
        );
    }

    private function useSessionStoreThatRefusesWrites(): void
    {
        $store = new class('wholesale-api-checkout', new ArraySessionHandler(1)) extends Store
        {
            public function put($key, $value = null)
            {
                if (is_string($key) && in_array($key, CheckoutRulesTest::CURRENCY_CACHE_SESSION_KEYS, true)) {
                    parent::put($key, $value);

                    return;
                }

                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function push($key, $value)
            {
                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function forget($keys)
            {
                if (array_diff((array) $keys, CheckoutRulesTest::CURRENCY_CACHE_SESSION_KEYS) === []) {
                    parent::forget($keys);

                    return;
                }

                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function remove($key)
            {
                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function flash($key, $value = true)
            {
                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function flush()
            {
                throw new RuntimeException('The API checkout wrote to the session.');
            }

            public function save()
            {
                throw new RuntimeException('The API checkout wrote to the session.');
            }
        };

        $this->app->instance('session', $store);
        $this->app->instance('session.store', $store);
    }

    /** @return array{minimum_quantity: int, maximum_quantity: ?int, available_stock: int, reason: null} */
    private function quoteLimits(int $minimum, ?int $maximum): array
    {
        return ['minimum_quantity' => $minimum, 'maximum_quantity' => $maximum, 'available_stock' => 1000, 'reason' => null];
    }

    private function isSettingActive(string $name): bool
    {
        $setting = getWebConfig(name: $name);

        return is_array($setting) ? (int) ($setting['status'] ?? 0) === 1 : (bool) $setting;
    }

    private function requireSetting(string $name): void
    {
        if (! $this->isSettingActive(name: $name)) {
            $this->markTestSkipped('Test database has '.$name.' turned off.');
        }
    }

    private function customer(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where(['customer_id' => $customer->id])->delete();

        return $customer;
    }

    private function createAddress(User $customer): ShippingAddress
    {
        if (getWebConfig(name: 'delivery_country_restriction')) {
            $this->markTestSkipped('Test database restricts delivery countries.');
        }

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale checkout rules fixture',
            'address_type' => 'office',
            'address' => 'Warehouse road 1',
            'city' => 'Dhaka',
            'zip' => '1200',
            'country' => 'Bangladesh',
            'phone' => '01700000000',
            'is_billing' => 0,
        ]);
    }

    private function addRow(User $customer, int $quantity): Cart
    {
        $productService = app(ProductWholesaleService::class);
        $categoryId = (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
        $product = $productService->create(
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale checkout rules fixture'],
                'description' => ['Wholesale checkout rules fixture'],
                'code' => 'WSRULE'.random_int(10000, 99999),
                'category_id' => $categoryId,
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                'minimum_order_qty' => 10,
                'current_stock' => 1000,
                'quantity_based_price' => 1,
                'tier_min_qty' => [10, 50],
                'tier_max_qty' => ['49', ''],
                'tier_unit_price' => [50, 40],
            ],
            colorNames: [],
        );
        $productService->updateStatus($product->id, true);
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        $product = $this->reload(productId: $product->id);
        $quote = $productService->getPriceQuote($product, [], $quantity, app(ColorService::class)->getNameMap());

        return app(CartService::class)->addWholesaleItem(
            product: $this->reload(productId: $product->id),
            quote: $quote,
            customerId: $customer->id,
            guestId: null,
            selection: [],
            shippingType: ShippingTypeService::TYPE_CATEGORY_WISE,
            shippingCost: 0.0,
        );
    }

    private function reload(int $productId): Model
    {
        return app(ProductWholesaleService::class)->findUnscoped($productId, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }
}
