<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\DeliverymanNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\OrderService as WholesaleOrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class DeliverymanCrossModuleTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const ONLINE_STATUS_URI = 'api/v6/delivery-man/online-status';

    private const NOTIFICATIONS_URI = 'api/v6/delivery-man/notifications';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_offline_is_refused_while_a_wholesale_order_is_out_for_delivery(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => WholesaleOrderService::STATUS_OUT_FOR_DELIVERY,
            'is_pause' => 0,
        ]);

        $this->putJson(self::ONLINE_STATUS_URI, ['is_online' => 0], $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'delivery_in_progress_403');

        $order->forceFill(['is_pause' => 1])->save();

        $this->putJson(self::ONLINE_STATUS_URI, ['is_online' => 0], $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('identical_code', 'online_status_off_200');
    }

    public function test_offline_is_still_refused_for_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $this->createRetailOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => WholesaleOrderService::STATUS_OUT_FOR_DELIVERY,
            'is_pause' => 0,
        ]);

        $this->putJson(self::ONLINE_STATUS_URI, ['is_online' => 0], $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'delivery_in_progress_403');
    }

    public function test_notifications_carry_the_order_business_module(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $deliveryMan);
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan);

        DeliverymanNotification::query()->create([
            'delivery_man_id' => $deliveryMan->id,
            'order_id' => $wholesaleOrder->id,
            'description' => 'Wholesale order assigned',
        ]);
        DeliverymanNotification::query()->create([
            'delivery_man_id' => $deliveryMan->id,
            'order_id' => $retailOrder->id,
            'description' => 'Retail order assigned',
        ]);

        $rows = $this->getJson(self::NOTIFICATIONS_URI, $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->json('content.data');

        $businessModuleByOrderId = collect($rows)->pluck('business_module', 'order_id');

        $this->assertSame(BusinessModule::WHOLESALE, $businessModuleByOrderId[$wholesaleOrder->id]);
        $this->assertSame(BusinessModule::RETAIL, $businessModuleByOrderId[$retailOrder->id]);
    }
}
