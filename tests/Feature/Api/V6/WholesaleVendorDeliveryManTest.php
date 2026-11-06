<?php

namespace Tests\Feature\Api\V6;

use App\Models\OrderStatusHistory;
use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\OrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleVendorDeliveryManTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const WHOLESALE_BASE = 'api/v6/wholesale/seller/delivery-men';

    private const RETAIL_BASE = 'api/v6/seller/delivery-men';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
    }

    public function test_orders_and_earnings_list_only_own_wholesale_orders(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $deliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $seller->id]);
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $deliveryMan);
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan);

        $orders = $this->getJson(self::WHOLESALE_BASE.'/'.$deliveryMan->id.'/orders', $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content.data');
        $orderIds = array_column($orders, 'id');

        $this->assertContains($wholesaleOrder->id, $orderIds);
        $this->assertNotContains($retailOrder->id, $orderIds);

        $earnings = $this->getJson(self::WHOLESALE_BASE.'/'.$deliveryMan->id.'/earnings', $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content');
        $earningOrderIds = array_column($earnings['data'], 'id');

        $this->assertContains($wholesaleOrder->id, $earningOrderIds);
        $this->assertNotContains($retailOrder->id, $earningOrderIds);
        $this->assertArrayHasKey('total_earn', $earnings);
        $this->assertArrayHasKey('withdrawable_balance', $earnings);
    }

    public function test_retail_endpoints_still_exclude_wholesale_orders(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $deliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $seller->id]);
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $deliveryMan);
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan);

        $orders = $this->getJson(self::RETAIL_BASE.'/'.$deliveryMan->id.'/orders', $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content.data');
        $orderIds = array_column($orders, 'id');

        $this->assertContains($retailOrder->id, $orderIds);
        $this->assertNotContains($wholesaleOrder->id, $orderIds);

        $earnings = $this->getJson(self::RETAIL_BASE.'/'.$deliveryMan->id.'/earnings', $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content');
        $earningOrderIds = array_column($earnings['data'], 'id');

        $this->assertContains($retailOrder->id, $earningOrderIds);
        $this->assertNotContains($wholesaleOrder->id, $earningOrderIds);
    }

    public function test_another_vendor_delivery_man_is_not_found(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $foreignDeliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $otherSeller->id]);
        $order = $this->createWholesaleOrder(deliveryMan: $foreignDeliveryMan);

        $this->getJson(self::WHOLESALE_BASE.'/'.$foreignDeliveryMan->id.'/orders', $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'delivery_man_not_found_404');

        $this->getJson(self::WHOLESALE_BASE.'/'.$foreignDeliveryMan->id.'/earnings', $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'delivery_man_not_found_404');

        $this->getJson(self::WHOLESALE_BASE.'/'.$foreignDeliveryMan->id.'/orders', $this->sellerHeaders($otherSeller))
            ->assertOk()
            ->assertJsonPath('content.data.0.id', $order->id);
    }

    public function test_order_status_history_for_own_wholesale_order(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $deliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $seller->id]);
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $deliveryMan);
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan);
        $otherSellerDeliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $otherSeller->id]);
        $otherSellerOrder = $this->createWholesaleOrder(deliveryMan: $otherSellerDeliveryMan);

        OrderStatusHistory::query()->create([
            'order_id' => $wholesaleOrder->id,
            'user_id' => $seller->id,
            'user_type' => 'seller',
            'status' => OrderService::STATUS_PENDING,
        ]);

        $this->getJson(self::WHOLESALE_BASE.'/order-status-history/'.$wholesaleOrder->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('content.data.0.order_id', $wholesaleOrder->id);

        $this->getJson(self::WHOLESALE_BASE.'/order-status-history/'.$retailOrder->id, $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');

        $this->getJson(self::WHOLESALE_BASE.'/order-status-history/'.$otherSellerOrder->id, $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_endpoints_forbidden_when_wholesale_is_off(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $deliveryMan = $this->createDeliveryMan(overrides: ['seller_id' => $seller->id]);
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan);

        $this->disableWholesaleModule();

        foreach ([
            self::WHOLESALE_BASE.'/'.$deliveryMan->id.'/orders',
            self::WHOLESALE_BASE.'/'.$deliveryMan->id.'/earnings',
            self::WHOLESALE_BASE.'/order-status-history/'.$order->id,
        ] as $uri) {
            $this->getJson($uri, $this->sellerHeaders($seller))
                ->assertForbidden()
                ->assertJsonPath('identical_code', 'wholesale_feature_disabled_403');
        }
    }

    /** @return array<int, Seller> */
    private function approvedSellers(int $count): array
    {
        $sellers = Seller::query()
            ->where('status', 'approved')
            ->where('id', '!=', 1)
            ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
            ->orderBy('id')
            ->limit($count)
            ->get();

        if ($sellers->count() < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' approved sellers with a vendor shop.');
        }

        return $sellers
            ->map(static function (Seller $seller): Seller {
                $seller->forceFill(['auth_token' => Str::random(50)])->saveQuietly();

                return $seller;
            })
            ->all();
    }
}
