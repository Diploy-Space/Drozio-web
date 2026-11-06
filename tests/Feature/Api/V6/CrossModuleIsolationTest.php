<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class CrossModuleIsolationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const RETAIL_ORDERS_URI = '/api/v6/customer/orders';

    private const WHOLESALE_ORDERS_URI = '/api/v6/wholesale/customer/orders';

    private const RETAIL_WISHLIST_URI = '/api/v6/customer/wishlist';

    private const WHOLESALE_WISHLIST_URI = '/api/v6/wholesale/customer/wishlist';

    private const RETAIL_REFUND_ELIGIBILITY_URI = '/api/v6/customer/refunds/eligibility';

    private const WHOLESALE_REFUND_ELIGIBILITY_URI = '/api/v6/wholesale/customer/refunds/eligibility';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_the_wholesale_wishlist_delete_leaves_a_retail_row_in_place(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $product = $this->product(businessModule: BusinessModule::RETAIL);
        $wishlist = $this->wishlist(customer: $customer, product: $product);

        $this->deleteJson(self::WHOLESALE_WISHLIST_URI.'/'.$product->id, [], $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'wishlist_item_not_found_404');

        $this->assertNotNull(Wishlist::query()->find($wishlist->id));
    }

    public function test_the_retail_wishlist_delete_leaves_a_wholesale_row_in_place(): void
    {
        $customer = $this->customer();
        $product = $this->product(businessModule: BusinessModule::WHOLESALE);
        $wishlist = $this->wishlist(customer: $customer, product: $product);

        $this->deleteJson(self::RETAIL_WISHLIST_URI.'/'.$product->id, [], $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'wishlist_item_not_found_404');

        $this->assertNotNull(Wishlist::query()->find($wishlist->id));
    }

    public function test_the_wholesale_refund_eligibility_rejects_a_retail_order_line(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $orderDetail = $this->orderLine(customer: $customer, businessModule: BusinessModule::RETAIL);

        $this->getJson(self::WHOLESALE_REFUND_ELIGIBILITY_URI.'/'.$orderDetail->id, $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'refund_order_item_not_found_404');
    }

    public function test_the_retail_refund_eligibility_rejects_a_wholesale_order_line(): void
    {
        $customer = $this->customer();
        $orderDetail = $this->orderLine(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $this->getJson(self::RETAIL_REFUND_ELIGIBILITY_URI.'/'.$orderDetail->id, $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'refund_order_item_not_found_404');
    }

    public function test_the_wholesale_order_endpoints_reject_a_retail_order(): void
    {
        $customer = $this->customer();
        $order = $this->order(customer: $customer, businessModule: BusinessModule::RETAIL);

        $this->getJson(self::WHOLESALE_ORDERS_URI.'/'.$order->id, $this->actingAsCustomer($customer))->assertNotFound();
        $this->getJson(self::WHOLESALE_ORDERS_URI.'/'.$order->id.'/items', $this->actingAsCustomer($customer))->assertNotFound();
    }

    public function test_the_retail_order_endpoints_reject_a_wholesale_order(): void
    {
        $customer = $this->customer();
        $order = $this->order(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $this->getJson(self::RETAIL_ORDERS_URI.'/'.$order->id, $this->actingAsCustomer($customer))->assertNotFound();
        $this->getJson(self::RETAIL_ORDERS_URI.'/'.$order->id.'/items', $this->actingAsCustomer($customer))->assertNotFound();
    }

    public function test_the_wholesale_product_detail_rejects_a_retail_slug(): void
    {
        $product = $this->product(businessModule: BusinessModule::RETAIL);

        $this->getJson('/api/v6/wholesale/products/'.$product->slug)->assertNotFound();
    }

    public function test_the_retail_product_detail_rejects_a_wholesale_slug(): void
    {
        $product = $this->product(businessModule: BusinessModule::WHOLESALE);

        $this->getJson('/api/v6/products/'.$product->slug)->assertNotFound();
    }

    private function customer(): User
    {
        return $this->requireFixture(
            User::query()->where('id', '>', 0)->where('is_active', 1)->first(),
            'active customer',
        );
    }

    private function product(string $businessModule): Model
    {
        return $this->requireFixture(
            Product::query()->where('business_module', $businessModule)->whereNotNull('slug')->orderBy('id')->first(),
            $businessModule.' product',
        );
    }

    private function wishlist(User $customer, Model $product): Model
    {
        Wishlist::query()->where(['customer_id' => $customer->id, 'product_id' => $product->id])->delete();

        return Wishlist::query()->create(['customer_id' => $customer->id, 'product_id' => $product->id]);
    }

    private function order(User $customer, string $businessModule): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + 1,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'business_module' => $businessModule,
            'order_status' => 'delivered',
            'payment_status' => 'paid',
        ])->save();

        return $order;
    }

    private function orderLine(User $customer, string $businessModule): Model
    {
        $order = $this->order(customer: $customer, businessModule: $businessModule);
        $product = $this->product(businessModule: $businessModule);

        return OrderDetail::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => (int) $product->user_id,
            'product_details' => json_encode($product->toArray()),
            'qty' => 1,
            'price' => 10,
            'discount' => 0,
            'discount_type' => 'discount_on_product',
            'tax' => 0,
            'delivery_status' => 'delivered',
            'payment_status' => 'paid',
            'variant' => '',
            'variation' => [],
        ]);
    }
}
