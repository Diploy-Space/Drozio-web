<?php

namespace Tests\Feature\Api\V6;

use App\Events\OrderStatusEvent;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\New\OrderTransactionService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\OrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleDeliverymanOrderProgressTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const BASE_URI = 'api/v6/wholesale/delivery-man/orders/';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_delivered_settles_wallet_history_and_loyalty(): void
    {
        $this->setSettings(['loyalty_point_status' => 1, 'loyalty_point_for_each_order' => 1, 'loyalty_point_item_purchase_point' => 10, 'wallet_status' => 1]);
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan, ['deliveryman_charge' => 7]);
        $detail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();
        $reward = $this->createLoyaltyReward(order: $order, detail: $detail);
        $customerLoyaltyBefore = (float) $order->customer->loyalty_point;

        $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => OrderService::STATUS_DELIVERED], $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_status_updated_200');

        $order->refresh();
        $this->assertSame(OrderService::STATUS_DELIVERED, $order->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(0, (int) $order->is_pause);
        $this->assertEqualsWithDelta(7, (float) DB::table('deliveryman_wallets')->where('delivery_man_id', $deliveryMan->id)->value('current_balance'), 0.001);
        $this->assertEqualsWithDelta((float) $order->order_amount, (float) DB::table('deliveryman_wallets')->where('delivery_man_id', $deliveryMan->id)->value('cash_in_hand'), 0.001);
        $this->assertDatabaseHas('delivery_man_transactions', ['delivery_man_id' => $deliveryMan->id, 'user_id' => $deliveryMan->id, 'user_type' => 'delivery_man', 'credit' => 7, 'transaction_type' => 'deliveryman_charge']);
        $this->assertDatabaseHas('order_transactions', ['order_id' => $order->id, 'received_by' => 'delivery man', 'delivered_by' => 'delivery man']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'user_id' => $deliveryMan->id, 'user_type' => 'delivery_man', 'status' => OrderService::STATUS_DELIVERED]);
        $this->assertDatabaseHas('loyalty_point_transactions', ['user_id' => $order->customer_id, 'business_module' => 'wholesale', 'reference' => (string) $order->id]);
        $this->assertGreaterThan($customerLoyaltyBefore, (float) $order->customer()->first()->loyalty_point);
        $this->assertSame(1, (int) $reward->refresh()->reward_delivered);
    }

    public function test_canceled_restores_stock_and_stores_cause(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $detail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();
        [$productBefore, $variantBefore] = $this->stockFor($detail);

        $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => OrderService::STATUS_CANCELED, 'cause' => 'Buyer refused'], $this->deliveryManHeaders($deliveryMan))
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderService::STATUS_CANCELED, $order->order_status);
        $this->assertSame('Buyer refused', $order->cause);
        [$productAfter, $variantAfter] = $this->stockFor($detail);
        $this->assertSame($productBefore + (int) $detail->qty, $productAfter);
        $this->assertSame($variantBefore + (int) $detail->qty, $variantAfter);
        $this->assertSame(0, (int) $detail->refresh()->is_stock_decreased);
    }

    public function test_returned_restores_stock_without_cause(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $detail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();
        [$productBefore, $variantBefore] = $this->stockFor($detail);

        $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => OrderService::STATUS_RETURNED, 'cause' => 'Damaged in transit'], $this->deliveryManHeaders($deliveryMan))
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderService::STATUS_RETURNED, $order->order_status);
        $this->assertNull($order->cause);
        [$productAfter, $variantAfter] = $this->stockFor($detail);
        $this->assertSame($productBefore + (int) $detail->qty, $productAfter);
        $this->assertSame($variantBefore + (int) $detail->qty, $variantAfter);
    }

    public function test_out_for_delivery_leaves_stock_untouched(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);
        $detail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();
        [$productBefore, $variantBefore] = $this->stockFor($detail);

        $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => OrderService::STATUS_OUT_FOR_DELIVERY], $this->deliveryManHeaders($deliveryMan))
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderService::STATUS_OUT_FOR_DELIVERY, $order->order_status);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => OrderService::STATUS_OUT_FOR_DELIVERY, 'user_type' => 'delivery_man']);
        $this->assertSame([$productBefore, $variantBefore], $this->stockFor($detail));
    }

    public function test_insufficient_stock_returns_403(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan, ['order_status' => OrderService::STATUS_CANCELED]);
        $detail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();
        $detail->forceFill(['is_stock_decreased' => 0])->save();
        ProductStock::query()->where(['product_id' => $detail->product_id, 'variant' => $detail->variant])->update(['qty' => 0]);
        Product::query()->withoutGlobalScopes()->whereKey($detail->product_id)->update(['current_stock' => 0]);

        $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => OrderService::STATUS_OUT_FOR_DELIVERY], $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_status_insufficient_stock_403');

        $this->assertSame(OrderService::STATUS_CANCELED, $order->refresh()->order_status);
    }

    public function test_status_rejects_statuses_outside_the_deliveryman_set(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        foreach ([OrderService::STATUS_FAILED, OrderService::STATUS_PENDING] as $orderStatus) {
            $this->putJson(self::BASE_URI.$order->id.'/status', ['status' => $orderStatus], $this->deliveryManHeaders($deliveryMan))
                ->assertUnprocessable()
                ->assertJsonPath('identical_code', 'unprocessable_entity_422')
                ->assertJsonPath('errors.0.error_code', 'status');
        }
    }

    public function test_status_already_delivered_and_settled_are_blocked(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $deliveredOrder = $this->createWholesaleOrder($deliveryMan, ['order_status' => OrderService::STATUS_DELIVERED, 'payment_status' => OrderService::PAYMENT_STATUS_PAID]);

        $this->putJson(self::BASE_URI.$deliveredOrder->id.'/status', ['status' => OrderService::STATUS_RETURNED], $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_status_already_delivered_403');

        $settledOrder = $this->createWholesaleOrder($deliveryMan);
        DB::table('order_transactions')->insert([
            'order_id' => $settledOrder->id,
            'seller_id' => $settledOrder->seller_id,
            'seller_is' => $settledOrder->seller_is,
            'status' => OrderTransactionService::STATUS_DISBURSE,
            'order_amount' => 100,
            'seller_amount' => 95,
            'admin_commission' => 5,
            'received_by' => 'admin',
            'delivered_by' => 'admin',
            'delivery_charge' => 0,
            'tax' => 0,
            'customer_id' => $settledOrder->customer_id,
            'shop_id' => 0,
            'payment_method' => 'cash_on_delivery',
            'transaction_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->putJson(self::BASE_URI.$settledOrder->id.'/status', ['status' => OrderService::STATUS_DELIVERED], $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_status_already_delivered_403');
    }

    public function test_status_refuses_a_retail_order_and_another_deliverymans_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $otherDeliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createRetailOrder($deliveryMan);
        $foreignOrder = $this->createWholesaleOrder($otherDeliveryMan);

        $this->putJson(self::BASE_URI.$retailOrder->id.'/status', ['status' => OrderService::STATUS_OUT_FOR_DELIVERY], $this->deliveryManHeaders($deliveryMan))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');

        $this->putJson(self::BASE_URI.$foreignOrder->id.'/status', ['status' => OrderService::STATUS_OUT_FOR_DELIVERY], $this->deliveryManHeaders($deliveryMan))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_expected_delivery_writes_history_and_dispatches_notification(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        $this->putJson(self::BASE_URI.$order->id.'/expected-delivery', ['expected_delivery_date' => '2030-05-06', 'cause' => 'Rider delayed'], $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_expected_delivery_updated_200');

        $order->refresh();
        $this->assertSame('2030-05-06', $order->expected_delivery_date);
        $this->assertSame('Rider delayed', $order->cause);
        $this->assertDatabaseHas('order_expected_delivery_histories', ['order_id' => $order->id, 'user_id' => $deliveryMan->id, 'user_type' => 'delivery_man', 'cause' => 'Rider delayed']);
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === OrderService::NOTIFICATION_ORDER_RESCHEDULED && $event->type === OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
    }

    public function test_expected_delivery_refuses_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createRetailOrder($deliveryMan);

        $this->putJson(self::BASE_URI.$retailOrder->id.'/expected-delivery', ['expected_delivery_date' => '2030-05-06'], $this->deliveryManHeaders($deliveryMan))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_pause_and_resume(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan);

        $this->putJson(self::BASE_URI.$order->id.'/pause', ['is_pause' => 1, 'cause' => 'Traffic jam'], $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_pause_status_updated_200');
        $this->assertSame(1, (int) $order->refresh()->is_pause);
        $this->assertSame('Traffic jam', $order->cause);

        $this->putJson(self::BASE_URI.$order->id.'/pause', ['is_pause' => 0, 'cause' => null], $this->deliveryManHeaders($deliveryMan))
            ->assertOk();
        $this->assertSame(0, (int) $order->refresh()->is_pause);
    }

    public function test_pause_refuses_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createRetailOrder($deliveryMan);

        $this->putJson(self::BASE_URI.$retailOrder->id.'/pause', ['is_pause' => 1], $this->deliveryManHeaders($deliveryMan))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_payment_status_writes_paid(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan, ['edit_due_amount' => 0]);

        $this->putJson(self::BASE_URI.$order->id.'/payment-status', ['payment_status' => OrderService::PAYMENT_STATUS_PAID], $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_payment_status_updated_200');

        $order->refresh();
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertEqualsWithDelta(100, (float) $order->order_amount, 0.001);
    }

    public function test_payment_status_refuses_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createRetailOrder($deliveryMan);

        $this->putJson(self::BASE_URI.$retailOrder->id.'/payment-status', ['payment_status' => OrderService::PAYMENT_STATUS_PAID], $this->deliveryManHeaders($deliveryMan))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    /** @return array{0: int, 1: int} */
    private function stockFor(OrderDetail $detail): array
    {
        return [
            (int) Product::query()->withoutGlobalScopes()->whereKey($detail->product_id)->value('current_stock'),
            (int) ProductStock::query()->where(['product_id' => $detail->product_id, 'variant' => $detail->variant])->value('qty'),
        ];
    }

    private function createLoyaltyReward(Order $order, OrderDetail $detail): OrderDetailsRewards
    {
        $reward = new OrderDetailsRewards;
        $reward->forceFill([
            'order_id' => $order->id,
            'order_details_id' => $detail->id,
            'reward_type' => 'loyalty_point',
            'reward_amount' => 10,
            'reward_details' => [],
            'reward_delivered' => 0,
        ])->save();

        return $reward;
    }

    private function setSettings(array $settings): void
    {
        foreach ($settings as $type => $value) {
            BusinessSetting::query()->updateOrInsert(['type' => $type], ['value' => $value]);
        }

        Cache::flush();
        Helpers::resetMemo();
    }
}
