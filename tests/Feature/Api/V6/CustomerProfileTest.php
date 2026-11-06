<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Api\Support\ApiTestData;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();
    }

    public function test_profile_shares_the_retail_and_wholesale_order_counts_of_the_customer(): void
    {
        $customer = $this->requireFixture(ApiTestData::customerWithDeliveredOrder(), 'customer with a delivered order');

        $response = $this->getJson('api/v6/customer/profile', $this->actingAsCustomer($customer));

        $response->assertOk()
            ->assertJsonPath('content.data.id', $customer->id)
            ->assertJsonPath('content.data.retail_orders_count', $this->orderCount(customerId: $customer->id, businessModule: BusinessModule::RETAIL))
            ->assertJsonPath('content.data.wholesale_orders_count', $this->orderCount(customerId: $customer->id, businessModule: BusinessModule::WHOLESALE));
    }

    public function test_profile_counts_only_the_signed_in_customers_own_orders_per_module(): void
    {
        $customer = $this->requireFixture(ApiTestData::customerWithDeliveredOrder(), 'customer with a delivered order');
        $order = Order::where(['customer_id' => $customer->id, 'is_guest' => 0])->firstOrFail();
        $retailCount = $this->orderCount(customerId: $customer->id, businessModule: BusinessModule::RETAIL);
        $wholesaleCount = $this->orderCount(customerId: $customer->id, businessModule: BusinessModule::WHOLESALE);
        $isRetailOrder = $order->business_module === BusinessModule::RETAIL;

        $order->forceFill(['business_module' => $isRetailOrder ? BusinessModule::WHOLESALE : BusinessModule::RETAIL])->save();

        $this->getJson('api/v6/customer/profile', $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('content.data.retail_orders_count', $retailCount + ($isRetailOrder ? -1 : 1))
            ->assertJsonPath('content.data.wholesale_orders_count', $wholesaleCount + ($isRetailOrder ? 1 : -1));
    }

    public function test_profile_requires_a_signed_in_customer(): void
    {
        $this->getJson('api/v6/customer/profile')->assertUnauthorized();
    }

    private function orderCount(int $customerId, string $businessModule): int
    {
        return Order::where(['customer_id' => $customerId, 'is_guest' => 0, 'business_module' => $businessModule])->count();
    }
}
