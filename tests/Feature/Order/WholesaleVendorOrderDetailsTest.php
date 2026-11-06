<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDeliveryVerification;
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
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Modules\Courier\app\Models\CourierProviderSetting;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\Services\New\CourierService;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\ValueObjects\CourierOwner;
use Modules\Courier\CourierProviders\CourierProvider;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Enums\OrderBlocker;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderStatusService;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleVendorOrderDetailsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-vendor-order-details-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const COURIER_PROVIDER = 'vendor_ledger_partner';

    private const SELLERWISE_SHIPPING = 'sellerwise_shipping';

    private const INHOUSE_SHIPPING = 'inhouse_shipping';

    private const DELETED_PRODUCT_ID_OFFSET = 900000;

    private int $nextOrderId = 0;

    /** @var array<string, mixed> */
    private array $overriddenSettings = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubVendorLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        foreach ($this->overriddenSettings as $type => $value) {
            $this->memoizedSetting(type: $type)?->setAttribute('value', $value);
        }

        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_vendor_courier_panel_uses_vendor_wholesale_settings(): void
    {
        if (! $this->hasCourierBusinessModuleSchema()) {
            $this->markTestSkipped('Test database lacks the Courier business_module migrations (2026_09_17_100011 to 100013).');
        }

        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING, 'delivery_type' => null]);

        config(['courier.providers' => [self::COURIER_PROVIDER => VendorLedgerPartnerProvider::class]]);
        $this->overrideSetting(type: 'vendor_delivery_partner_setup', value: 1);
        $this->overrideSetting(type: 'third_party_delivery_service', value: 1);

        $this->enableCourierAccount(owner: CourierOwner::vendor(id: $seller->id));
        $this->enableCourierAccount(owner: CourierOwner::platform(businessModule: BusinessModule::WHOLESALE));

        $withoutVendorWholesale = $this->renderDetailsWithFreshCourier(seller: $seller, order: $order);
        $this->assertFalse($withoutVendorWholesale->viewData('deliveryPartner')['is_enabled']);
        $withoutVendorWholesale->assertDontSee('value="third_party_delivery"', false);

        $this->enableCourierAccount(owner: CourierOwner::vendor(id: $seller->id, businessModule: BusinessModule::WHOLESALE));

        $withVendorWholesale = $this->renderDetailsWithFreshCourier(seller: $seller, order: $order);
        $this->assertTrue($withVendorWholesale->viewData('deliveryPartner')['is_enabled']);
        $this->assertTrue($withVendorWholesale->viewData('showDeliveryPartnerPanel'));
        $this->assertSame(BusinessModule::WHOLESALE, $withVendorWholesale->viewData('courierOrder')['business_module']);
        $withVendorWholesale
            ->assertSee('value="third_party_delivery"', false)
            ->assertSee('Vendor Ledger Partner');

        $this->assertSame(
            1,
            CourierProviderSetting::query()->where(['owner_type' => 'vendor', 'owner_id' => $seller->id, 'business_module' => BusinessModule::WHOLESALE, 'provider' => self::COURIER_PROVIDER])->count(),
        );
    }

    public function test_vendor_without_delivery_partner_setup_sees_no_courier_panel(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING, 'delivery_type' => null]);

        $this->overrideSetting(type: 'vendor_delivery_partner_setup', value: 0);

        if ($this->hasCourierBusinessModuleSchema()) {
            config(['courier.providers' => [self::COURIER_PROVIDER => VendorLedgerPartnerProvider::class]]);
            $this->enableCourierAccount(owner: CourierOwner::vendor(id: $seller->id, businessModule: BusinessModule::WHOLESALE));
        }

        $response = $this->renderDetailsWithFreshCourier(seller: $seller, order: $order)
            ->assertDontSee('value="third_party_delivery"', false)
            ->assertDontSee('Vendor Ledger Partner');

        $this->assertFalse($response->viewData('deliveryPartner')['is_enabled']);
        $this->assertFalse($response->viewData('showDeliveryPartnerPanel'));
        $this->assertArrayNotHasKey('third_party_delivery', $response->viewData('deliveryTypeOptions'));
    }

    public function test_details_renders_items_summary_and_side_cards(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: [
            'order_note' => 'Deliver to the back gate',
            'shipping_address_data' => ['contact_person_name' => 'Karim Traders', 'phone' => '01711000000', 'city' => 'Dhaka', 'zip' => '1207', 'address' => '12 Wholesale Road', 'country' => 'Bangladesh'],
        ]);
        $this->createDetail(order: $order, snapshotName: 'Bulk rice sack');
        $liveDetail = $this->createDetail(order: $order, snapshotName: 'Stale snapshot name', hasLiveProduct: true);

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee('Order Details (Wholesale)')
            ->assertSee('#'.$order->id)
            ->assertSee('Deliver to the back gate')
            ->assertSee('12 Wholesale Road')
            ->assertSee('Bulk rice sack')
            ->assertSee('Blue-Small')
            ->assertSee('wholesale-order-summary', false)
            ->assertSee('wholesale-order-payment-card', false)
            ->assertSee('wholesale-order-shipping-card', false)
            ->assertSee('wholesale-order-customer-card', false)
            ->assertSee(route('vendor.wholesale.orders.invoice', ['id' => $order->id]), false)
            ->assertDontSee('Shop Information')
            ->assertDontSee('Store');

        $detailRow = $response->viewData('detailRows')[0];
        $this->assertSame(1, $detailRow['serial']);
        $this->assertSame(10.0, $detailRow['unit_price']);
        $this->assertSame(30.0, $detailRow['item_price']);
        $this->assertSame(28.0, $detailRow['total_price']);
        $this->assertSame('Blue-Small', $detailRow['variation']);
        $this->assertNull($detailRow['product_url']);

        $liveRow = $response->viewData('detailRows')[1];
        $this->assertSame(Product::query()->find($liveDetail->product_id)->name, $liveRow['name']);
        $this->assertSame(route('vendor.wholesale.products.view.overview', ['id' => $liveDetail->product_id]), $liveRow['product_url']);

        $summaryLabels = array_column($response->viewData('summary'), 'label');
        $this->assertSame(['Item Price', 'Item Discount', 'Subtotal', 'Coupon Discount', 'VAT/TAX', 'Delivery Fee', 'Total'], $summaryLabels);
        $this->assertStringContainsString('#wholesale-order-shipping-address-modal', $response->getContent());
    }

    public function test_free_delivery_row_balances_the_summary(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: [
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

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk();

        $summaryRows = $response->viewData('summary');
        $freeDeliveryRow = collect($summaryRows)->first(fn (array $row): bool => str_starts_with($row['label'], translate('Free delivery')));
        $this->assertNotNull($freeDeliveryRow);
        $this->assertSame(10.0, $freeDeliveryRow['amount']);
        $this->assertTrue($freeDeliveryRow['is_deduction']);
        $this->assertStringContainsString(translate('Vendor'), $freeDeliveryRow['label']);

        $amounts = array_column($summaryRows, 'amount', 'label');
        $total = collect($summaryRows)->firstWhere('is_total', true)['amount'];
        $this->assertEqualsWithDelta(
            $amounts['Subtotal'] - $amounts['Coupon Discount'] + $amounts['VAT/TAX'] + $amounts['Delivery Fee'] - $freeDeliveryRow['amount'],
            $total,
            0.0001,
        );
    }

    public function test_retail_order_id_returns_404(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(seller: $seller);

        $this->assertOrderUnreachable(seller: $seller, order: $retailOrder);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.retail.orders.details', ['id' => $wholesaleOrder->id]))
            ->assertRedirect();
    }

    public function test_other_vendor_order_returns_404(): void
    {
        [$ownSeller, $otherSeller] = $this->sellers(count: 2);
        $otherOrder = $this->createOrder(seller: $otherSeller, attributes: ['order_status' => 'pending', 'payment_status' => 'unpaid']);
        $this->createDetail(order: $otherOrder, snapshotName: 'Other vendor item');

        $this->assertOrderUnreachable(seller: $ownSeller, order: $otherOrder);

        $otherOrder->refresh();
        $this->assertSame('pending', $otherOrder->order_status);
        $this->assertSame('unpaid', $otherOrder->payment_status);
    }

    public function test_linked_orders_and_adjacent_links_render(): void
    {
        [$seller, $otherSeller] = $this->sellers(count: 2);
        $groupId = 'wholesale-vendor-group-'.Str::lower(Str::random(12));
        $previousOrder = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $groupId]);
        $foreignLinkedOrder = $this->createOrder(seller: $otherSeller, attributes: ['order_group_id' => $groupId]);
        $order = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $groupId]);
        $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $nextOrder = $this->createOrder(seller: $seller);

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee(route('vendor.wholesale.orders.details', ['id' => $previousOrder->id]), false)
            ->assertSee(route('vendor.wholesale.orders.details', ['id' => $nextOrder->id]), false)
            ->assertDontSee('#'.$foreignLinkedOrder->id);

        $this->assertSame([$previousOrder->id], $response->viewData('linkedOrders')->pluck('id')->map(static fn ($id): int => (int) $id)->all());
        $this->assertSame(['previous' => $previousOrder->id, 'next' => $nextOrder->id], $response->viewData('adjacentOrderIds'));
    }

    public function test_vendor_status_options_follow_retail_vendor_rules(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $vendorShippingOrder = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING]);
        $inHouseShippingOrder = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::INHOUSE_SHIPPING]);

        $vendorShipping = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $vendorShippingOrder->id]))
            ->assertOk()
            ->assertSee('wholesale-order-delivery-type', false)
            ->assertSee('wholesale-order-delivery-man', false);

        $this->assertSame(
            ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'returned', 'failed', 'canceled'],
            array_keys($vendorShipping->viewData('allowedStatuses')),
        );
        $this->assertTrue($vendorShipping->viewData('canAssignDeliveryMan'));

        $inHouseShipping = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $inHouseShippingOrder->id]))
            ->assertOk()
            ->assertSee('This inhouse order must use the shipping option chosen at checkout.')
            ->assertDontSee('wholesale-order-delivery-type', false)
            ->assertDontSee('value="delivered"', false);

        $this->assertSame(['pending', 'confirmed', 'processing'], array_keys($inHouseShipping->viewData('allowedStatuses')));
        $this->assertFalse($inHouseShipping->viewData('canAssignDeliveryMan'));
        $this->assertSame([], $inHouseShipping->viewData('deliveryMen'));
    }

    public function test_status_update_calls_fulfilment_and_returns_json(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['order_status' => 'pending', 'payment_method' => 'cash_on_delivery']);
        $this->createDetail(order: $order, snapshotName: 'Status item');

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.status'), ['id' => $order->id, 'order_status' => 'confirmed'])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => 'Status changed successfully']);

        $this->assertSame('confirmed', $order->refresh()->order_status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $seller->id,
            'user_type' => 'seller',
            'status' => 'confirmed',
        ]);
    }

    public function test_vendor_cannot_deliver_an_admin_shipped_order(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::INHOUSE_SHIPPING, 'order_status' => 'pending', 'payment_method' => 'cash_on_delivery']);
        $this->createDetail(order: $order, snapshotName: 'Admin shipped item');

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.status'), ['id' => $order->id, 'order_status' => 'delivered'])
            ->assertOk()
            ->assertExactJson(['status' => 0, 'message' => 'Only the admin can update the delivery status of this order']);

        $this->assertSame('pending', $order->refresh()->order_status);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
    }

    public function test_blocked_status_update_returns_translated_message(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $this->mockOrderAction(serviceClass: OrderStatusService::class, method: 'changeStatus', results: [['order' => null, 'blocker' => OrderBlocker::PAYMENT_UNCONFIRMED_BEFORE_DELIVERY]], expectedSellerId: $seller->id);

        $response = $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.status'), ['id' => 991, 'order_status' => 'delivered'])
            ->assertOk()
            ->assertJson(['status' => 0, 'message' => 'Please update the payment status first']);

        $this->assertStringNotContainsString(OrderBlocker::PAYMENT_UNCONFIRMED_BEFORE_DELIVERY, $response->getContent());
    }

    public function test_payment_status_update(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $this->mockOrderAction(
            serviceClass: OrderService::class,
            method: 'updatePaymentStatus',
            results: [
                ['order' => null, 'blocker' => null],
                ['order' => null, 'blocker' => OrderBlocker::PAID_STATUS_IRREVERSIBLE],
            ],
            expectedSellerId: $seller->id,
            expectedArguments: ['orderId' => '992'],
        );

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.payment-status'), ['id' => 992, 'payment_status' => 'paid'])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => 'Status changed successfully']);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.payment-status'), ['id' => 992, 'payment_status' => 'unpaid'])
            ->assertOk()
            ->assertJson(['status' => 0, 'message' => 'Once payment status is paid, it cannot be changed to unpaid.']);
    }

    public function test_vendor_waits_for_admin_to_verify_an_offline_payment(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['payment_method' => OrderService::PAYMENT_METHOD_OFFLINE, 'payment_note' => 'Paid at the branch counter']);
        DB::table('offline_payments')->insert([
            'order_id' => $order->id,
            'payment_info' => json_encode(['method_id' => 1, 'method_name' => 'Bank Payment', 'account_number' => '876543']),
            'created_at' => now(),
        ]);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('isOfflinePaymentUnconfirmed', true)
            ->assertSee('876543')
            ->assertSee('Paid at the branch counter')
            ->assertSee(translate('This payment has not been verified yet.'));

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.payment-status'), ['id' => $order->id, 'payment_status' => OrderService::PAYMENT_STATUS_PAID])
            ->assertOk()
            ->assertJson(['status' => 0, 'message' => translate('Please confirm the offline payment information before changing the order status.')]);

        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->fresh()->payment_status);
    }

    public function test_delivery_man_assign_rejects_foreign_delivery_man(): void
    {
        [$seller, $otherSeller] = $this->sellers(count: 2);
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING, 'order_status' => 'confirmed', 'delivery_man_id' => null]);
        $foreignDeliveryMan = $this->createDeliveryMan(sellerId: $otherSeller->id);
        $ownDeliveryMan = $this->createDeliveryMan(sellerId: $seller->id);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.delivery-man'), ['id' => $order->id, 'delivery_man_id' => $foreignDeliveryMan->id])
            ->assertOk()
            ->assertJson(['status' => 0, 'message' => 'Delivery man cannot be assigned or changed in that status']);

        $this->assertNull($order->refresh()->delivery_man_id);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.delivery-man'), ['id' => $order->id, 'delivery_man_id' => $ownDeliveryMan->id])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertSame($ownDeliveryMan->id, (int) $order->refresh()->delivery_man_id);
    }

    public function test_delivery_schedule_update(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING, 'expected_delivery_date' => null]);
        $expectedDate = now()->addDays(3)->format('Y-m-d');

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.delivery-schedule'), ['id' => $order->id, 'field_name' => 'expected_delivery_date', 'field_val' => $expectedDate])
            ->assertOk()
            ->assertExactJson(['status' => 1, 'message' => 'Expected delivery date added successfully']);

        $this->assertSame($expectedDate, $order->refresh()->expected_delivery_date);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.orders.delivery-schedule'), ['id' => $order->id, 'field_name' => 'deliveryman_charge', 'field_val' => 'not-a-number'])
            ->assertStatus(422);
    }

    public function test_address_update(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: [
            'shipping_address_data' => ['contact_person_name' => 'Old Name', 'phone' => '0100', 'city' => 'Old City', 'zip' => '1000', 'address' => 'Old street', 'country' => 'Bangladesh', 'email' => 'kept@example.test'],
        ]);

        $this->actingAs($seller, 'seller')
            ->from(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->post(route('vendor.wholesale.orders.address'), [
                'id' => $order->id,
                'address_type' => 'shipping',
                'name' => 'New Name',
                'phone_number' => '0199',
                'country' => 'Bangladesh',
                'city' => 'New City',
                'zip' => '1207',
                'address' => 'New street',
                'latitude' => '23.7',
                'longitude' => '90.4',
            ])
            ->assertRedirect(route('vendor.wholesale.orders.details', ['id' => $order->id]));

        $shippingAddress = json_decode(json_encode($order->refresh()->shipping_address_data), true);
        $this->assertSame('New Name', $shippingAddress['contact_person_name']);
        $this->assertSame('New City', $shippingAddress['city']);
        $this->assertSame('kept@example.test', $shippingAddress['email']);
    }

    public function test_details_runs_constant_queries(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $smallOrder = $this->createOrder(seller: $seller);
        $largeOrder = $this->createOrder(seller: $seller);

        foreach (range(1, 2) as $index) {
            $this->createDetail(order: $smallOrder, snapshotName: 'Small line '.$index);
        }

        foreach (range(1, 10) as $index) {
            $this->createDetail(order: $largeOrder, snapshotName: 'Large line '.$index);
        }

        $this->actingAs($seller, 'seller')->get(route('vendor.wholesale.orders.details', ['id' => $smallOrder->id]))->assertOk();

        $this->assertSame(
            $this->countDetailsQueries(seller: $seller, order: $smallOrder),
            $this->countDetailsQueries(seller: $seller, order: $largeOrder),
        );
    }

    public function test_verification_code_follows_wholesale_setting(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: ['verification_code' => '482913']);

        $this->useDeliveryVerification(isEnabled: false);
        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertViewHas('isDeliveryVerificationEnabled', false)
            ->assertDontSee('482913');

        $this->useDeliveryVerification(isEnabled: true);
        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee('Order verification code')
            ->assertSee('482913');
    }

    public function test_delivery_proof_images_render_only_when_verified(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $verifiedOrder = $this->createOrder(seller: $seller, attributes: ['verification_status' => 1]);
        $this->createDetail(order: $verifiedOrder, snapshotName: 'Verified item');
        $this->createVerificationImage(order: $verifiedOrder);

        $verifiedResponse = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $verifiedOrder->id]))
            ->assertOk()
            ->assertSee('order_verification_modal', false)
            ->assertSee('Order verification');

        $this->assertCount(1, $verifiedResponse->viewData('verificationImageUrls'));

        $unverifiedOrder = $this->createOrder(seller: $seller, attributes: ['verification_status' => 0]);
        $this->createDetail(order: $unverifiedOrder, snapshotName: 'Unverified item');
        $this->createVerificationImage(order: $unverifiedOrder);

        $unverifiedResponse = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $unverifiedOrder->id]))
            ->assertOk()
            ->assertDontSee('order_verification_modal', false);

        $this->assertSame([], $unverifiedResponse->viewData('verificationImageUrls'));
    }

    public function test_third_party_option_hidden_when_partner_disabled(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $selfDeliveryOrder = $this->createOrder(seller: $seller, attributes: ['delivery_type' => 'self_delivery']);
        $assignedOrder = $this->createOrder(seller: $seller, attributes: [
            'delivery_type' => 'third_party_delivery',
            'delivery_service_name' => 'Manual Carrier',
            'third_party_delivery_tracking_id' => 'TRACK-778',
        ]);

        $selfDelivery = $this->renderDetailsWithFreshCourier(seller: $seller, order: $selfDeliveryOrder)
            ->assertSee('value="self_delivery"', false);

        if (! $selfDelivery->viewData('deliveryPartner')['is_enabled']) {
            $selfDelivery->assertDontSee('value="third_party_delivery"', false);
            $this->assertFalse($selfDelivery->viewData('showDeliveryPartnerPanel'));
        }

        if (! $this->hasCourierBusinessModuleSchema()) {
            $this->markTestIncomplete('The assigned-partner half renders the Courier panel, which needs the Courier business_module migrations (2026_09_17_100011 to 100013).');
        }

        $assigned = $this->renderDetailsWithFreshCourier(seller: $seller, order: $assignedOrder)
            ->assertSee('value="third_party_delivery"', false);

        $this->assertTrue($assigned->viewData('deliveryPartner')['is_assigned']);
        $this->assertSame('third_party_delivery', $assigned->viewData('selectedDeliveryType'));
        $this->assertTrue($assigned->viewData('showDeliveryPartnerPanel'));
    }

    public function test_invoice_downloads_pdf(): void
    {
        $seller = $this->sellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller);
        $this->createDetail(order: $order, snapshotName: 'Invoice line item');
        $invoiceData = null;

        View::composer('wholesale::vendor-views.orders.invoice', function ($view) use (&$invoiceData): void {
            if ($invoiceData === null) {
                $invoiceData = $view->getData();

                throw new InvoiceRendered;
            }
        });

        try {
            $this->withoutExceptionHandling()->actingAs($seller, 'seller')->get(route('vendor.wholesale.orders.invoice', ['id' => $order->id]));
        } catch (InvoiceRendered) {
        }

        $this->assertNotNull($invoiceData, 'The invoice view was not rendered.');
        $this->assertSame((int) $order->id, $invoiceData['invoice']['order_id']);

        $content = view('wholesale::vendor-views.orders.invoice', $invoiceData)->render();

        $this->assertStringContainsString('Invoice line item', $content);
        $this->assertStringContainsString('#'.$order->id, $content);
        $this->assertStringContainsString('Blue-Small', $content);
    }

    private function assertOrderUnreachable(Seller $seller, Order $order): void
    {
        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertNotFound();

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.invoice', ['id' => $order->id]))
            ->assertNotFound();

        $actions = [
            'vendor.wholesale.orders.status' => ['order_status' => 'confirmed'],
            'vendor.wholesale.orders.payment-status' => ['payment_status' => 'paid'],
            'vendor.wholesale.orders.delivery-man' => ['delivery_man_id' => 1],
            'vendor.wholesale.orders.delivery-schedule' => ['field_name' => 'expected_delivery_date', 'field_val' => now()->format('Y-m-d')],
        ];

        foreach ($actions as $routeName => $input) {
            $this->actingAs($seller, 'seller')
                ->postJson(route($routeName), ['id' => $order->id, ...$input])
                ->assertNotFound()
                ->assertJson(['status' => 0, 'message' => 'Not found']);
        }

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.orders.address'), ['id' => $order->id, 'address_type' => 'shipping', 'name' => 'Nobody'])
            ->assertNotFound();
    }

    private function countDetailsQueries(Seller $seller, Order $order): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($seller, 'seller')->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function renderDetailsWithFreshCourier(Seller $seller, Order $order): TestResponse
    {
        foreach ([ProviderRegistry::class, CourierService::class] as $singleton) {
            $this->app->forgetInstance($singleton);
        }

        return $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.details', ['id' => $order->id]))
            ->assertOk();
    }

    private function enableCourierAccount(CourierOwner $owner): void
    {
        $this->app->make(CourierConfigService::class)->save(
            data: [
                'provider' => self::COURIER_PROVIDER,
                'is_enabled' => true,
                'credentials' => ['token' => 'token-'.$owner->routeKey()],
                'environment' => CourierProvider::ENVIRONMENT_SANDBOX,
            ],
            owner: $owner,
        );
    }

    private function hasCourierBusinessModuleSchema(): bool
    {
        return addon_published_status('Courier') && Schema::hasColumn('courier_provider_settings', 'business_module');
    }

    private function useDeliveryVerification(bool $isEnabled): void
    {
        $orderService = $this->app->resolved(OrderService::class) ? $this->app->make(OrderService::class) : null;

        if (! $orderService instanceof DeliveryVerificationOrderService) {
            $orderService = new DeliveryVerificationOrderService(app(Order::class));
            $this->app->instance(OrderService::class, $orderService);
        }

        $orderService->isDeliveryVerificationEnabled = $isEnabled;
    }

    private function mockOrderAction(string $serviceClass, string $method, array $results, int $expectedSellerId, array $expectedArguments = []): void
    {
        $this->mock($serviceClass, function (MockInterface $mock) use ($serviceClass, $method, $results, $expectedSellerId, $expectedArguments): void {
            $mock->shouldIgnoreMissing();
            $mock->shouldReceive($method)
                ->times(count($results))
                ->withArgs(function (...$arguments) use ($serviceClass, $method, $expectedSellerId, $expectedArguments): bool {
                    $namedArguments = $this->namedArguments(serviceClass: $serviceClass, method: $method, arguments: $arguments);

                    foreach ($expectedArguments as $name => $value) {
                        if ((string) ($namedArguments[$name] ?? '') !== (string) $value) {
                            return false;
                        }
                    }

                    return (! array_key_exists('actorType', $namedArguments) || $namedArguments['actorType'] === OrderActor::VENDOR)
                        && (int) $namedArguments['sellerId'] === $expectedSellerId;
                })
                ->andReturn(...$results);
        });
    }

    private function namedArguments(string $serviceClass, string $method, array $arguments): array
    {
        if (! array_is_list($arguments)) {
            return $arguments;
        }

        $names = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionMethod($serviceClass, $method))->getParameters(),
        );

        return array_combine(array_slice($names, 0, count($arguments)), $arguments);
    }

    private function overrideSetting(string $type, mixed $value): void
    {
        getWebConfig(name: 'vendor_order_details_loader_'.Str::lower(Str::random(12)));

        $resolved = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['resolved'] ?? [];

        if (array_key_exists($type, $resolved)) {
            if ((string) $resolved[$type] === (string) $value) {
                return;
            }

            $this->markTestSkipped('getWebConfig() already memoized '.$type.' in this process; run this test on its own.');
        }

        $setting = $this->memoizedSetting(type: $type);

        if ($setting === null) {
            $setting = new BusinessSetting(['type' => $type, 'value' => $value]);
            (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']->push($setting);
            $this->overriddenSettings[$type] = null;

            return;
        }

        $this->overriddenSettings[$type] ??= $setting->getAttribute('value');
        $setting->setAttribute('value', $value);
    }

    private function memoizedSetting(string $type): ?BusinessSetting
    {
        return (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']?->firstWhere('type', $type);
    }

    private function createOrder(Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'customer_id' => $this->customer()->id,
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash_on_delivery',
            'shipping_responsibility' => self::SELLERWISE_SHIPPING,
            'delivery_type' => 'self_delivery',
            'delivery_man_id' => null,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'order_group_id' => 'wholesale-vendor-details-'.Str::lower(Str::random(16)),
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, string $snapshotName, bool $hasLiveProduct = false): OrderDetail
    {
        $productId = $hasLiveProduct
            ? (int) $this->requireFixture(Product::query()->where('status', 1)->orderBy('id')->first(), 'active product')->id
            : (int) Product::query()->max('id') + self::DELETED_PRODUCT_ID_OFFSET;

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $productId,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode([
                'id' => $productId,
                'name' => $snapshotName,
                'added_by' => 'seller',
                'product_type' => 'physical',
            ]),
            'qty' => 3,
            'price' => 10,
            'discount' => 2,
            'tax' => 1.4,
            'tax_model' => 'exclude',
            'variant' => 'Blue-Small',
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'is_stock_decreased' => 0,
            'discount_type' => 'discount_on_product',
        ])->save();

        return $detail;
    }

    private function createVerificationImage(Order $order): OrderDeliveryVerification
    {
        $image = new OrderDeliveryVerification;
        $image->forceFill(['order_id' => $order->id, 'image' => 'proof-'.Str::lower(Str::random(8)).'.jpg'])->save();

        return $image;
    }

    private function createDeliveryMan(int $sellerId): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => $sellerId,
            'f_name' => 'Rider',
            'l_name' => Str::random(6),
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => Str::lower(Str::random(10)).'@rider.test',
            'password' => bcrypt(Str::random(12)),
            'is_active' => 1,
        ])->save();

        return $deliveryMan;
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(500000, 900000);
        }

        return ++$this->nextOrderId;
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('is_active', 1)->where('email', '!=', 'walking@customer.com')->orderBy('id')->first(), 'active customer');
    }

    /** @return array<int, Seller> */
    private function sellers(int $count): array
    {
        $sellers = Seller::query()
            ->where('status', 'approved')
            ->where('id', '!=', 1)
            ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
            ->orderBy('id')
            ->limit($count)
            ->get()
            ->all();

        if (count($sellers) < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' approved sellers with a vendor shop.');
        }

        return $sellers;
    }

    private function stubVendorLayout(): void
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

class InvoiceRendered extends \RuntimeException {}

class VendorLedgerPartnerProvider extends CourierProvider
{
    public function getName(): string
    {
        return 'vendor_ledger_partner';
    }

    public function displayName(): string
    {
        return 'Vendor Ledger Partner';
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
