<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\User;
use App\Services\New\BusinessSettingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Events\OrderDeliveredEvent;
use Modules\Wholesale\app\Listeners\OrderDeliveredCreditLoyaltyPointListener;
use Modules\Wholesale\app\Services\OrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class LoyaltyPointCreditTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();
    }

    public function test_loyalty_points_are_credited_when_wallet_is_disabled(): void
    {
        $this->setSettings([
            'wallet_status' => 0,
            'loyalty_point_status' => 1,
            'loyalty_point_for_each_order' => 1,
            'loyalty_point_item_purchase_point' => 10,
        ]);

        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer);
        $detail = $this->createDetail(order: $order);
        $reward = $this->createLoyaltyReward(order: $order, detail: $detail);

        $freshSettingService = app()->make(BusinessSettingService::class);
        $this->assertSame('0', $freshSettingService->getValuesByTypes(types: ['wallet_status'])['wallet_status']);

        app(OrderDeliveredCreditLoyaltyPointListener::class)->handle(new OrderDeliveredEvent(
            order: $order,
            historyUserType: OrderActor::HISTORY_USER_TYPES[OrderActor::ADMIN],
            actorId: 0,
            receivedBy: OrderActor::WALLET_RECEIVED_BY[OrderActor::ADMIN],
        ));

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'reference' => (string) $order->id,
            'business_module' => BusinessModule::WHOLESALE,
        ]);
        $this->assertSame(1, (int) $reward->refresh()->reward_delivered);
    }

    public function test_free_delivery_order_credits_the_sum_of_item_rewards_once(): void
    {
        $this->setSettings([
            'loyalty_point_status' => 1,
            'loyalty_point_for_each_order' => 1,
            'loyalty_point_item_purchase_point' => 20,
        ]);

        $customer = $this->customer();
        $startingPoint = (float) $customer->loyalty_point;
        $order = $this->createOrder(customer: $customer);
        $order->forceFill(['order_amount' => 1755, 'shipping_cost' => 10.02, 'extra_discount' => 10.02, 'is_shipping_free' => 1])->save();
        $firstReward = $this->createLoyaltyReward(order: $order, detail: $this->createDetail(order: $order), rewardAmount: 351);
        $secondReward = $this->createLoyaltyReward(order: $order, detail: $this->createDetail(order: $order), rewardAmount: 12);

        $this->deliver(order: $order);
        $this->deliver(order: $order);

        $transactions = DB::table('loyalty_point_transactions')->where('reference', (string) $order->id)->get();

        $this->assertCount(1, $transactions);
        $this->assertEquals(363, (float) $transactions->first()->credit);
        $this->assertEquals($startingPoint + 363, (float) $customer->refresh()->loyalty_point);
        $this->assertSame(1, (int) $firstReward->refresh()->reward_delivered);
        $this->assertSame(1, (int) $secondReward->refresh()->reward_delivered);
    }

    private function deliver(Order $order): void
    {
        app(OrderDeliveredCreditLoyaltyPointListener::class)->handle(new OrderDeliveredEvent(
            order: $order,
            historyUserType: OrderActor::HISTORY_USER_TYPES[OrderActor::ADMIN],
            actorId: 0,
            receivedBy: OrderActor::WALLET_RECEIVED_BY[OrderActor::ADMIN],
        ));
    }

    private function setSettings(array $settings): void
    {
        foreach ($settings as $type => $value) {
            DB::table('business_settings')->updateOrInsert(['type' => $type], ['value' => $value]);
        }
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->orderBy('id')->first(), 'customer');
    }

    private function createOrder(User $customer): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + random_int(100000, 900000),
            'business_module' => BusinessModule::WHOLESALE,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'seller_id' => self::IN_HOUSE_SELLER_ID,
            'seller_is' => 'admin',
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-loyalty-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_DELIVERED,
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'admin_commission' => 5,
            'shipping_cost' => 0,
            'is_shipping_free' => 0,
            'shipping_responsibility' => 'inhouse_shipping',
            'discount_amount' => 0,
            'discount_type' => null,
            'coupon_code' => null,
            'extra_discount' => 0,
            'refer_and_earn_discount' => 0,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
            'delivery_type' => null,
            'is_pause' => 0,
            'created_at' => now(),
        ])->save();

        return $order;
    }

    private function createDetail(Order $order): OrderDetail
    {
        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => 0,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => 0, 'name' => 'Loyalty fixture', 'product_type' => 'physical']),
            'qty' => 1,
            'price' => 25,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => '',
            'variation' => [],
            'delivery_status' => 'delivered',
            'payment_status' => 'paid',
            'discount_type' => 'discount_on_product',
            'is_stock_decreased' => 0,
        ])->save();

        return $detail;
    }

    private function createLoyaltyReward(Order $order, OrderDetail $detail, int $rewardAmount = 10): OrderDetailsRewards
    {
        $reward = new OrderDetailsRewards;
        $reward->forceFill([
            'order_id' => $order->id,
            'order_details_id' => $detail->id,
            'reward_type' => 'loyalty_point',
            'reward_amount' => $rewardAmount,
            'reward_details' => [],
            'reward_delivered' => 0,
        ])->save();

        return $reward;
    }
}
