<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Enums\VendorStatus;
use App\Events\OrderStatusEvent;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\CourierService;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\ValueObjects\CourierOwner;
use Modules\Courier\CourierProviders\CourierProvider;
use Modules\Wholesale\app\Enums\OrderBlocker;
use Modules\Wholesale\app\Http\Controllers\Admin\Orders\OrderController;
use Modules\Wholesale\app\Http\Requests\Shared\Order\StatusUpdateRequest;
use Modules\Wholesale\app\Services\OrderService;
use ReflectionClass;
use ReflectionFunction;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleAdminOrderDetailsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private const IN_HOUSE_DELIVERY_MAN_OWNER_ID = 0;

    private const COURIER_PROVIDER = DetailsPartnerProvider::NAME;

    private const COURIER_PROVIDER_LABEL = DetailsPartnerProvider::LABEL;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-admin-order-details-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        if (addon_published_status('Courier') && ! Schema::hasColumn('courier_provider_settings', 'business_module')) {
            $this->markTestSkipped('The test database lacks the Courier business_module migrations (2026_09_17_100011 to 100013).');
        }

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_details_renders_items_summary_and_side_cards(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller());
        $detail = $this->createDetail(order: $order, snapshotName: 'Details widget');
        $liveProductName = Product::query()->withoutGlobalScopes()->whereKey($detail->product_id)->value('name');

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee($liveProductName)
            ->assertSee('Blue-Small')
            ->assertSee('wholesale-order-item-row', false)
            ->assertSee('wholesale-order-summary', false)
            ->assertSee('wholesale-order-payment-card', false)
            ->assertSee('id="wholesale-order-status"', false)
            ->assertSee('id="choose_delivery_type"', false)
            ->assertSee('id="wholesale-order-delivery-man"', false)
            ->assertSee('wholesale-order-address-card', false)
            ->assertSee('wholesale-order-customer-card', false)
            ->assertSee('wholesale-order-shop-card', false)
            ->assertSee('shippingAddressUpdateModal', false)
            ->assertSee(route('admin.wholesale.orders.invoice', ['id' => $order->id]), false)
            ->assertSee(route('admin.wholesale.orders.status'), false);

        $summary = $response->viewData('summary');
        $this->assertSame(30.0, $summary['item_price']);
        $this->assertSame(2.0, $summary['item_discount']);
        $this->assertSame(['previous', 'next'], array_keys($response->viewData('adjacentOrderIds')));
        $this->assertSame(StatusUpdateRequest::ORDER_STATUSES, $response->viewData('allowedStatuses'));
        $this->assertSame(14.29, $response->viewData('detailRows')[$order->details()->value('id')]['tax_percent']);
        $this->assertSame('Wholesale receiver', $response->viewData('addressCards')[0]['address']->contact_person_name);
        $this->assertFalse($response->viewData('shop')['is_in_house']);
        $this->assertNotNull($response->viewData('customer'));
        $this->assertSame(BusinessModule::WHOLESALE, $response->viewData('courierSelectionOrder')['business_module']);
    }

    public function test_shop_card_marks_only_a_verified_vendor_as_verified(): void
    {
        $seller = $this->approvedSeller();
        $seller->forceFill(['is_verified' => 1])->save();
        Seller::query()->whereKey(self::IN_HOUSE_SELLER_ID)->update(['is_verified' => 1]);

        $vendorOrder = $this->createOrder(seller: $seller);
        $inHouseOrder = $this->createOrder(seller: null);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $vendorOrder->id]))
            ->assertOk()
            ->assertViewHas('shop', fn (array $shop): bool => $shop['is_verified'] === true);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $inHouseOrder->id]))
            ->assertOk()
            ->assertViewHas('shop', fn (array $shop): bool => $shop['is_in_house'] === true && $shop['is_verified'] === false);

        $seller->forceFill(['status' => VendorStatus::SUSPENDED])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $vendorOrder->id]))
            ->assertOk()
            ->assertViewHas('shop', fn (array $shop): bool => $shop['is_verified'] === false);

        $seller->forceFill(['status' => VendorStatus::APPROVED, 'is_verified' => 0])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $vendorOrder->id]))
            ->assertOk()
            ->assertViewHas('shop', fn (array $shop): bool => $shop['is_verified'] === false);
    }

    public function test_free_delivery_row_balances_the_summary(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: [
            'shipping_cost' => 10,
            'is_shipping_free' => 1,
            'free_delivery_bearer' => OrderService::SELLER_IS_SELLER,
            'extra_discount' => 10,
            'extra_discount_type' => 'flat',
            'discount_amount' => 0,
            'refer_and_earn_discount' => 0,
            'total_tax_amount' => 0,
        ]);
        $this->createDetail(order: $order, snapshotName: 'Free delivery widget');

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee('wholesale-order-free-delivery', false);

        $summary = $response->viewData('summary');
        $this->assertSame(10.0, $summary['delivery_fee_discount']);
        $this->assertSame(translate('Vendor'), $summary['free_delivery_bearer']);
        $this->assertEqualsWithDelta(
            $summary['sub_total'] - $summary['coupon_discount'] - $summary['referral_discount'] + $summary['tax'] + $summary['delivery_fee'] - $summary['delivery_fee_discount'],
            $summary['total'],
            0.0001,
        );
    }

    public function test_retail_order_id_returns_404(): void
    {
        $retailOrder = $this->createOrder(seller: null, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(seller: null);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('admin.wholesale.orders.details', ['id' => $retailOrder->id]))->assertNotFound();
        $this->actingAs($admin, 'admin')->get(route('admin.wholesale.orders.invoice', ['id' => $retailOrder->id]))->assertNotFound();

        $actions = [
            'admin.wholesale.orders.status' => ['order_status' => OrderService::STATUS_CONFIRMED],
            'admin.wholesale.orders.payment-status' => ['payment_status' => OrderService::PAYMENT_STATUS_PAID],
            'admin.wholesale.orders.delivery-man' => ['delivery_man_id' => 1],
            'admin.wholesale.orders.delivery-schedule' => ['field_name' => 'deliveryman_charge', 'field_val' => 5],
        ];

        foreach ($actions as $routeName => $payload) {
            $this->actingAs($admin, 'admin')
                ->postJson(route($routeName), ['id' => $retailOrder->id, ...$payload])
                ->assertNotFound()
                ->assertJson(['status' => 0, 'message' => translate('Order not found')]);
        }

        $this->actingAs($admin, 'admin')
            ->post(route('admin.wholesale.orders.address'), ['id' => $retailOrder->id, 'address_type' => OrderService::ADDRESS_TYPE_SHIPPING, 'name' => 'Nobody'])
            ->assertNotFound();

        $this->assertSame(OrderService::STATUS_PENDING, $retailOrder->fresh()->order_status);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.retail.orders.details', ['id' => $wholesaleOrder->id]))
            ->assertRedirect(route('admin.retail.orders.list', ['status' => 'all']));
    }

    public function test_linked_orders_and_adjacent_links_render(): void
    {
        $firstOrder = $this->createOrder(seller: null);
        $linkedOrder = $this->createOrder(seller: null, attributes: ['order_group_id' => $firstOrder->order_group_id]);
        $retailBetween = $this->createOrder(seller: null, businessModule: BusinessModule::RETAIL);
        $nextOrder = $this->createOrder(seller: null);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $linkedOrder->id]))
            ->assertOk()
            ->assertSee(translate('Linked orders'))
            ->assertSee(route('admin.wholesale.orders.details', ['id' => $firstOrder->id]), false)
            ->assertSee(route('admin.wholesale.orders.details', ['id' => $nextOrder->id]), false)
            ->assertDontSee(route('admin.wholesale.orders.details', ['id' => $retailBetween->id]), false);

        $this->assertSame([$firstOrder->id], $response->viewData('linkedOrders')->pluck('id')->all());
        $this->assertSame(['previous' => $firstOrder->id, 'next' => $nextOrder->id], $response->viewData('adjacentOrderIds'));
    }

    public function test_verification_code_follows_wholesale_setting(): void
    {
        $verificationCode = 'WV'.random_int(1000, 9999);
        $order = $this->createOrder(seller: null, attributes: ['verification_code' => $verificationCode]);
        $webConfigMemo = (new ReflectionFunction('getWebConfig'))->getStaticVariables();

        if (! array_key_exists(OrderService::DELIVERY_VERIFICATION_SETTING, $webConfigMemo['resolved'] ?? [])) {
            $wholesaleValue = (int) ! (bool) (int) getWebConfig(name: 'order_verification');
            $setting = BusinessSetting::query()->updateOrCreate(['type' => OrderService::DELIVERY_VERIFICATION_SETTING], ['value' => $wholesaleValue]);
            $settings = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings'];
            $settings?->firstWhere('type', OrderService::DELIVERY_VERIFICATION_SETTING)?->setAttribute('value', $wholesaleValue) ?? $settings?->push($setting);
        }

        $isEnabled = app(OrderService::class)->isDeliveryVerificationEnabled();

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('isDeliveryVerificationEnabled', $isEnabled);

        $isEnabled
            ? $response->assertSee($verificationCode)->assertSee('wholesale-order-verification-code', false)
            : $response->assertDontSee($verificationCode)->assertDontSee('wholesale-order-verification-code', false);
    }

    public function test_status_update_calls_fulfilment_and_returns_json(): void
    {
        $order = $this->createOrder(seller: null);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.status'), ['id' => $order->id, 'order_status' => OrderService::STATUS_CONFIRMED])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Order status changed successfully')]);

        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->fresh()->order_status);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => OrderService::STATUS_CONFIRMED, 'user_type' => 'admin']);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.status'), ['id' => $order->id, 'order_status' => 'shipped-to-moon'])
            ->assertUnprocessable();
    }

    public function test_blocked_status_update_returns_translated_message(): void
    {
        $order = $this->createOrder(seller: null, attributes: ['customer_id' => (int) User::query()->max('id') + 1000]);

        $response = $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.status'), ['id' => $order->id, 'order_status' => OrderService::STATUS_CONFIRMED])
            ->assertOk()
            ->assertExactJson(['status' => 0, 'message' => translate('Account has been deleted, you cannot change the status')]);

        $this->assertStringNotContainsString(OrderBlocker::CUSTOMER_ACCOUNT_DELETED, $response->getContent());
        $this->assertSame(OrderService::STATUS_PENDING, $order->fresh()->order_status);

        $controller = app(OrderController::class);
        $blockerMessage = (new ReflectionClass($controller))->getMethod('blockerMessage');
        $fallbackMessage = $blockerMessage->invoke($controller, 'unknown_blocker');

        foreach ((new ReflectionClass(OrderBlocker::class))->getConstants() as $name => $blocker) {
            $message = $blockerMessage->invoke($controller, $blocker);
            $this->assertNotSame($blocker, $message, $name);
            $this->assertNotSame($fallbackMessage, $message, $name.' has no sentence of its own.');
        }
    }

    public function test_payment_status_update(): void
    {
        $order = $this->createOrder(seller: null, attributes: ['payment_method' => 'stripe']);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.payment-status'), ['id' => $order->id, 'payment_status' => OrderService::PAYMENT_STATUS_PAID])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Payment status changed successfully')]);

        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->fresh()->payment_status);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.payment-status'), ['id' => $order->id, 'payment_status' => OrderService::PAYMENT_STATUS_UNPAID])
            ->assertOk()
            ->assertExactJson(['status' => 0, 'message' => translate('A paid payment status cannot be changed back to unpaid.')]);

        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->fresh()->payment_status);
    }

    public function test_admin_confirms_an_unverified_offline_payment(): void
    {
        $order = $this->createOrder(seller: null, attributes: ['payment_method' => OrderService::PAYMENT_METHOD_OFFLINE, 'payment_note' => 'Paid at the branch counter']);
        DB::table('offline_payments')->insert([
            'order_id' => $order->id,
            'payment_info' => json_encode(['method_id' => 1, 'method_name' => 'Bank Payment', 'account_number' => '876543']),
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('canConfirmOfflinePayment', true)
            ->assertSee('wholesale-order-offline-payment-confirm', false)
            ->assertSee('876543');

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.payment-status'), ['id' => $order->id, 'payment_status' => OrderService::PAYMENT_STATUS_PAID])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Payment status changed successfully')]);

        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->fresh()->payment_status);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('canConfirmOfflinePayment', false)
            ->assertDontSee('wholesale-order-offline-payment-confirm', false);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.status'), ['id' => $order->id, 'order_status' => OrderService::STATUS_CONFIRMED])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Order status changed successfully')]);
    }

    public function test_delivery_man_assign_rejects_foreign_delivery_man(): void
    {
        $order = $this->createOrder(seller: null);
        $foreignDeliveryMan = $this->createDeliveryMan(ownerId: $this->approvedSeller()->id);
        $ownDeliveryMan = $this->createDeliveryMan(ownerId: self::IN_HOUSE_DELIVERY_MAN_OWNER_ID);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.delivery-man'), ['id' => $order->id, 'delivery_man_id' => $foreignDeliveryMan->id])
            ->assertOk()
            ->assertExactJson(['status' => 0, 'message' => translate('Delivery man cannot be assigned or changed in that status')]);

        $this->assertNull($order->fresh()->delivery_man_id);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.delivery-man'), ['id' => $order->id, 'delivery_man_id' => $ownDeliveryMan->id])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertSame($ownDeliveryMan->id, (int) $order->fresh()->delivery_man_id);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('deliveryMen', fn (array $deliveryMen): bool => array_key_exists($ownDeliveryMan->id, $deliveryMen) && ! array_key_exists($foreignDeliveryMan->id, $deliveryMen))
            ->assertSee('wholesale-order-deliveryman-charge', false);
    }

    public function test_delivery_schedule_update(): void
    {
        $order = $this->createOrder(seller: null, attributes: ['delivery_man_id' => $this->createDeliveryMan(ownerId: self::IN_HOUSE_DELIVERY_MAN_OWNER_ID)->id]);
        $expectedDate = now()->addDays(3)->toDateString();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.delivery-schedule'), ['id' => $order->id, 'field_name' => 'expected_delivery_date', 'field_val' => $expectedDate])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Expected delivery date updated successfully')]);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.delivery-schedule'), ['id' => $order->id, 'field_name' => 'deliveryman_charge', 'field_val' => 12])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => translate('Deliveryman charge updated successfully')]);

        $order->refresh();
        $this->assertSame($expectedDate, (string) $order->expected_delivery_date);
        $this->assertEqualsWithDelta(currencyConverter(amount: 12), (float) $order->deliveryman_charge, 0.001);
        $this->assertDatabaseHas('order_expected_delivery_histories', ['order_id' => $order->id, 'expected_delivery_date' => $expectedDate]);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.orders.delivery-schedule'), ['id' => $order->id, 'field_name' => 'order_amount', 'field_val' => 1])
            ->assertUnprocessable();
    }

    public function test_address_update(): void
    {
        $order = $this->createOrder(seller: null);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.wholesale.orders.address'), [
                'id' => $order->id,
                'address_type' => OrderService::ADDRESS_TYPE_SHIPPING,
                'name' => 'Updated receiver',
                'phone_number' => '01711111111',
                'country' => 'Bangladesh',
                'city' => 'Chattogram',
                'zip' => '4000',
                'address' => 'Agrabad 12',
                'latitude' => '22.33',
                'longitude' => '91.81',
            ])
            ->assertRedirect(route('admin.wholesale.orders.details', ['id' => $order->id]));

        $shippingAddress = $order->fresh()->shipping_address_data;
        $this->assertSame('Updated receiver', $shippingAddress->contact_person_name);
        $this->assertSame('Chattogram', $shippingAddress->city);
        $this->assertSame('Agrabad 12', $shippingAddress->address);
        $this->assertSame('receiver@example.test', $shippingAddress->email);
    }

    public function test_courier_panel_uses_wholesale_settings(): void
    {
        $this->skipUnlessCourierPublished();
        $this->skipUnlessThirdPartyDeliveryServiceOn();
        config(['courier.providers' => [self::COURIER_PROVIDER => DetailsPartnerProvider::class]]);

        $order = $this->createOrder(seller: null);
        $this->enableCourierAccount(owner: CourierOwner::platform());

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('deliveryPartner', fn (array $state): bool => $state['is_enabled'] === false)
            ->assertDontSee(self::COURIER_PROVIDER_LABEL);

        $this->enableCourierAccount(owner: CourierOwner::platform(businessModule: BusinessModule::WHOLESALE));

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('deliveryPartner', fn (array $state): bool => $state['is_enabled'] === true)
            ->assertViewHas('deliveryTypeOptions', fn (array $options): bool => array_key_exists(OrderService::DELIVERY_TYPE_THIRD_PARTY, $options))
            ->assertSee(self::COURIER_PROVIDER_LABEL)
            ->assertSee('value="'.OrderService::DELIVERY_TYPE_THIRD_PARTY.'"', false);
    }

    public function test_third_party_option_hidden_when_partner_disabled(): void
    {
        $order = $this->createOrder(seller: null);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('deliveryTypeOptions', fn (array $options): bool => ! array_key_exists(OrderService::DELIVERY_TYPE_THIRD_PARTY, $options))
            ->assertDontSee('value="'.OrderService::DELIVERY_TYPE_THIRD_PARTY.'"', false)
            ->assertSee('value="'.OrderService::DELIVERY_TYPE_SELF.'"', false);
    }

    public function test_invoice_downloads_pdf(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller());
        $this->createDetail(order: $order, snapshotName: 'Invoice widget');

        ob_start();
        try {
            $this->actingAs($this->admin(), 'admin')
                ->get(route('admin.wholesale.orders.invoice', ['id' => $order->id]))
                ->assertOk();
        } finally {
            $pdf = (string) ob_get_clean();
        }

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_details_runs_constant_queries(): void
    {
        $seller = $this->approvedSeller();
        $shortOrder = $this->createOrder(seller: $seller);
        $longOrder = $this->createOrder(seller: $seller);

        for ($line = 0; $line < 2; $line++) {
            $this->createDetail(order: $shortOrder, snapshotName: 'Short line '.$line);
        }

        for ($line = 0; $line < 10; $line++) {
            $this->createDetail(order: $longOrder, snapshotName: 'Long line '.$line);
        }

        $this->countDetailsQueries(order: $shortOrder);

        $this->assertSame(
            $this->countDetailsQueries(order: $shortOrder),
            $this->countDetailsQueries(order: $longOrder),
        );
    }

    private function countDetailsQueries(Order $order): int
    {
        $admin = $this->admin();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin, 'admin')->get(route('admin.wholesale.orders.details', ['id' => $order->id]))->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }

    private function enableCourierAccount(CourierOwner $owner): void
    {
        app(CourierConfigService::class)->save(
            data: [
                'provider' => self::COURIER_PROVIDER,
                'is_enabled' => true,
                'credentials' => ['token' => 'details-token-'.$owner->businessModule],
                'environment' => CourierProvider::ENVIRONMENT_SANDBOX,
            ],
            owner: $owner,
        );

        foreach ([ProviderRegistry::class, CourierService::class] as $singleton) {
            $this->app->forgetInstance($singleton);
        }
    }

    private function skipUnlessCourierPublished(): void
    {
        if (! addon_published_status('Courier')) {
            $this->markTestSkipped('Courier addon is not published.');
        }
    }

    private function skipUnlessThirdPartyDeliveryServiceOn(): void
    {
        if (! (bool) getWebConfig(name: 'third_party_delivery_service')) {
            $this->markTestSkipped('The third party delivery service setting is off in the test database.');
        }
    }

    private function createOrder(?Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_id' => $seller?->id ?? self::IN_HOUSE_SELLER_ID,
            'seller_is' => $seller === null ? 'admin' : 'seller',
            'customer_id' => $this->customer()->id,
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-details-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_PENDING,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'shipping_cost' => 0,
            'is_shipping_free' => 0,
            'shipping_responsibility' => 'inhouse_shipping',
            'delivery_type' => null,
            'delivery_service_name' => null,
            'third_party_delivery_tracking_id' => null,
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'edited_status' => 0,
            'verification_status' => 0,
            'shipping_address_data' => [
                'contact_person_name' => 'Wholesale receiver',
                'phone' => '01700000000',
                'email' => 'receiver@example.test',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'zip' => '1216',
                'address' => 'House 1, Road 2',
                'latitude' => '0',
                'longitude' => '0',
            ],
            'billing_address_data' => null,
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, string $snapshotName): OrderDetail
    {
        $productId = (int) $this->requireFixture(Product::query()->withoutGlobalScopes()->where('status', 1)->value('id'), 'active product');

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $productId,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $productId, 'name' => $snapshotName, 'product_type' => 'physical']),
            'qty' => 3,
            'price' => 10,
            'discount' => 2,
            'tax' => 4,
            'tax_model' => 'exclude',
            'variant' => 'Blue-Small',
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
        ])->save();

        return $detail;
    }

    private function createDeliveryMan(int $ownerId): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => $ownerId,
            'f_name' => 'Details',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'details-rider-'.Str::lower(Str::random(10)).'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => 1,
        ])->save();

        return $deliveryMan;
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->whereNotNull('phone')->orderBy('id')->first(), 'customer');
    }

    private function approvedSeller(): Seller
    {
        return $this->requireFixture(
            Seller::query()
                ->where('status', 'approved')
                ->where('id', '!=', self::IN_HOUSE_SELLER_ID)
                ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
                ->orderBy('id')
                ->first(),
            'approved seller with a vendor shop',
        );
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function stubAdminLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }
}

class DetailsPartnerProvider extends CourierProvider
{
    public const NAME = 'details_partner';

    public const LABEL = 'Details Partner';

    public function getName(): string
    {
        return self::NAME;
    }

    public function displayName(): string
    {
        return self::LABEL;
    }

    public function supportedCountries(): array
    {
        return ['BD'];
    }

    public function credentialFields(): array
    {
        return [['key' => 'token', 'label' => 'Token', 'required' => true]];
    }
}
