<?php

namespace Tests\Feature\Api\V6;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\OrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleDeliverymanEarningTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const WHOLESALE_URI = 'api/v6/wholesale/delivery-man/earnings';

    private const RETAIL_URI = 'api/v6/delivery-man/earnings';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();
    }

    public function test_lists_paid_wholesale_orders_newest_first_and_excludes_others(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $otherDeliveryMan = $this->createDeliveryMan();

        $older = $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'created_at' => now()->subDay(),
        ]);
        $newer = $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'created_at' => now(),
        ]);

        $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
        ]);
        $this->createRetailOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ]);
        $this->createWholesaleOrder($otherDeliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ]);

        $response = $this->getJson(self::WHOLESALE_URI, $this->deliveryManHeaders($deliveryMan));

        $response->assertOk();
        $this->assertSame([$newer->id, $older->id], array_column($response->json('content.data'), 'id'));
    }

    public function test_date_range_narrows_the_list(): void
    {
        $deliveryMan = $this->createDeliveryMan();

        $inRange = $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'created_at' => now()->subDays(2),
        ]);
        $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson(self::WHOLESALE_URI.'?'.http_build_query([
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
        ]), $this->deliveryManHeaders($deliveryMan));

        $response->assertOk();
        $this->assertSame([$inRange->id], array_column($response->json('content.data'), 'id'));
    }

    public function test_retail_earnings_endpoint_still_excludes_the_wholesale_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();

        $wholesaleOrder = $this->createWholesaleOrder($deliveryMan, [
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ]);

        $response = $this->getJson(self::RETAIL_URI, $this->deliveryManHeaders($deliveryMan));

        $response->assertOk();
        $this->assertNotContains($wholesaleOrder->id, array_column($response->json('content.data'), 'id'));
    }

    public function test_forbidden_when_wholesale_is_off(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $this->disableWholesaleModule();

        $this->getJson(self::WHOLESALE_URI, $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertExactJson([
                'identical_code' => 'wholesale_feature_disabled_403',
                'message' => 'Wholesale feature is not available for delivery men',
                'content' => null,
                'errors' => [],
            ]);
    }
}
