<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\User;
use App\Services\BusinessModule\BusinessModuleManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class OrderHistoryAccessTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->closeStorefront();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_storefront_pages_stay_closed_when_feature_availability_is_off(): void
    {
        $this->get(route('wholesale.products.index'))->assertRedirect(route('home'));
    }

    public function test_order_history_pages_stay_open_when_feature_availability_is_off(): void
    {
        $order = $this->requireWholesaleOrder();
        $customer = $this->requireFixture(User::query()->find($order->customer_id), 'wholesale order customer');
        $orderListUrl = route('wholesale.orders.my-orders');

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.details', ['id' => 0]))
            ->assertRedirect($orderListUrl);

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.invoice', ['id' => 0]))
            ->assertRedirect($orderListUrl);

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.refund-details', ['id' => 0]))
            ->assertRedirect($orderListUrl);
    }

    public function test_reorder_stays_closed_when_feature_availability_is_off(): void
    {
        $order = $this->requireWholesaleOrder();
        $customer = $this->requireFixture(User::query()->find($order->customer_id), 'wholesale order customer');

        $this->actingAs($customer, 'customer')
            ->getJson(route('wholesale.orders.reorder-preview', ['id' => $order->id]))
            ->assertForbidden();
    }

    public function test_order_list_keeps_the_wholesale_tab_only_for_a_customer_with_wholesale_orders(): void
    {
        $withHistory = customerProfileModuleTabs(
            menuKey: 'orders',
            activeModule: BusinessModule::RETAIL,
            historyModules: [BusinessModule::WHOLESALE],
        );

        $this->assertContains(BusinessModule::WHOLESALE, array_column($withHistory, 'key'));
        $this->assertSame([], customerProfileModuleTabs(menuKey: 'orders', activeModule: BusinessModule::RETAIL));
    }

    public function test_api_keeps_order_history_open_and_the_catalogue_closed(): void
    {
        $order = $this->requireWholesaleOrder();

        $this->getJson('/api/v6/wholesale/products')->assertForbidden();
        $this->postJson('/api/v6/wholesale/orders/track')->assertForbidden();

        $this->assertNotSame(
            Response::HTTP_FORBIDDEN,
            $this->getJson('/api/v6/wholesale/orders/'.$order->id.'/status-history')->getStatusCode(),
        );
    }

    private function closeStorefront(): void
    {
        $this->partialMock(BusinessModuleManager::class, function (MockInterface $manager): void {
            $manager->shouldReceive('isAvailable')->andReturnUsing(
                static fn (string $key): bool => $key !== BusinessModule::WHOLESALE,
            );
        });
    }

    private function requireWholesaleOrder(): Order
    {
        return $this->requireFixture(
            Order::query()
                ->where('business_module', BusinessModule::WHOLESALE)
                ->where('is_guest', 0)
                ->whereHas('details')
                ->latest('id')
                ->first(),
            'wholesale order',
        );
    }
}
