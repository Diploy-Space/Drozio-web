<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Modules\Wholesale\app\Events\ReorderPlanRequestedEvent;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CUSTOMER_ID = 4;

    private const VARIANT_PRODUCT_TEMPLATE_ID = 460;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-reorder-views';

    private const STUB_VIEW_CONTENTS = 'stub';

    /** @var array<int, string> */
    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
        $this->rememberLanguageFiles();
        $this->stubThemeView(view: 'orders.partials._reorder-lines');
    }

    protected function tearDown(): void
    {
        foreach ($this->stubbedViewPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_reorder_preview_404s_for_a_non_delivered_order_and_succeeds_for_a_delivered_one(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $template = $this->requireFixture(
            Order::query()->where('business_module', BusinessModule::WHOLESALE)->where('order_type', OrderService::ORDER_TYPE_DEFAULT)->first(),
            'wholesale order',
        );

        $pendingOrder = $this->cloneOrderForCustomer(template: $template, customer: $customer, status: OrderService::STATUS_PENDING);
        $deliveredOrder = $this->cloneOrderForCustomer(template: $template, customer: $customer, status: OrderService::STATUS_DELIVERED);

        $this->actingAs($customer, 'customer')
            ->getJson(route('wholesale.orders.reorder-preview', ['id' => $pendingOrder->id]))
            ->assertNotFound()
            ->assertJsonPath('message', translate('Invalid order'));

        $this->actingAs($customer, 'customer')
            ->getJson(route('wholesale.orders.reorder-preview', ['id' => $deliveredOrder->id]))
            ->assertOk();
    }

    public function test_reorder_preview_raises_quantity_to_the_minimum_order_when_stock_allows(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneVariantProductTemplate();

        ProductStock::query()->where('product_id', $product->id)->where('variant', 'Standard')->update(['qty' => 100]);
        $product->update(['minimum_order_qty' => 80]);

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 10);

        $line = $this->dispatchReorderPlan(order: $order, customer: $customer)['lines'][0];

        $this->assertNull($line['reason']);
        $this->assertSame(80, $line['quantity']);
        $this->assertTrue($line['is_quantity_raised']);
    }

    public function test_reorder_preview_flags_a_renamed_or_removed_variant_and_shows_the_ordered_variant_name(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneVariantProductTemplate();

        $product->update(['choice_options' => json_encode([['name' => 'choice_6', 'title' => 'Size', 'options' => ['Compact', 'Large']]])]);
        ProductStock::query()->where('product_id', $product->id)->where('variant', 'Standard')->delete();

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 60);

        $line = $this->dispatchReorderPlan(order: $order, customer: $customer)['lines'][0];

        $this->assertSame(ProductWholesaleService::REASON_VARIATION_UNAVAILABLE, $line['reason']);
        $this->assertSame('Standard', $line['variant_key']);
    }

    public function test_reorder_preview_flags_a_line_whose_product_lost_all_variations(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneVariantProductTemplate();

        $product->update(['choice_options' => '[]', 'colors' => '[]', 'current_stock' => 100]);

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 60);

        $line = $this->dispatchReorderPlan(order: $order, customer: $customer)['lines'][0];

        $this->assertSame(ProductWholesaleService::REASON_VARIATION_UNAVAILABLE, $line['reason']);
    }

    public function test_reorder_preview_flags_a_line_whose_product_is_inactive(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneVariantProductTemplate();

        $product->update(['status' => 0]);

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 10);

        $line = $this->dispatchReorderPlan(order: $order, customer: $customer)['lines'][0];

        $this->assertSame(CartService::REASON_PRODUCT_UNAVAILABLE, $line['reason']);
    }

    public function test_order_add_to_cart_adds_the_available_lines_of_a_non_delivered_order(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneStockedVariantProduct();

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_PENDING);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.orders.add-to-cart', ['id' => $order->id]))
            ->assertOk()
            ->assertJsonPath('message', translate('The items of this order have been added to your cart'))
            ->assertJsonMissingPath('redirect_url');

        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id, 'product_id' => $product->id]);
    }

    public function test_order_add_to_cart_skips_unavailable_lines_and_reports_the_partial_count(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $availableProduct = $this->cloneStockedVariantProduct();
        $inactiveProduct = $this->cloneStockedVariantProduct();

        $inactiveProduct->update(['status' => 0]);

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_CONFIRMED);
        $this->addOrderLine(order: $order, product: $availableProduct, variant: 'Standard', qty: 10);
        $this->addOrderLine(order: $order, product: $inactiveProduct, variant: 'Standard', qty: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.orders.add-to-cart', ['id' => $order->id]))
            ->assertOk()
            ->assertJsonPath('message', translate('Only :count of :total items could be added to your cart', ['count' => 1, 'total' => 2]));

        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id, 'product_id' => $availableProduct->id]);
        $this->assertDatabaseMissing('carts', ['customer_id' => $customer->id, 'product_id' => $inactiveProduct->id]);
    }

    public function test_order_add_to_cart_rejects_an_order_with_no_available_line(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $inactiveProduct = $this->cloneStockedVariantProduct();

        $inactiveProduct->update(['status' => 0]);

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $inactiveProduct, variant: 'Standard', qty: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.orders.add-to-cart', ['id' => $order->id]))
            ->assertUnprocessable()
            ->assertJsonPath('message', translate('None of the items in this order can be added to your cart right now'));
    }

    public function test_order_add_to_cart_404s_for_another_customers_order(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $otherCustomer = $this->requireFixture(User::query()->whereKeyNot(self::CUSTOMER_ID)->first(), 'second customer');
        $product = $this->cloneStockedVariantProduct();

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $otherCustomer, status: OrderService::STATUS_DELIVERED);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.orders.add-to-cart', ['id' => $order->id]))
            ->assertNotFound();

        $this->assertDatabaseMissing('carts', ['customer_id' => $customer->id, 'product_id' => $product->id]);
    }

    public function test_reorder_still_rejects_a_non_delivered_order(): void
    {
        $customer = $this->requireFixture(User::query()->find(self::CUSTOMER_ID), 'customer 4');
        $product = $this->cloneStockedVariantProduct();

        $order = $this->cloneOrderForCustomer(template: $this->requireWholesaleOrderTemplate(), customer: $customer, status: OrderService::STATUS_PENDING);
        $this->addOrderLine(order: $order, product: $product, variant: 'Standard', qty: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.orders.reorder', ['id' => $order->id]))
            ->assertNotFound();
    }

    private function cloneStockedVariantProduct(): Model
    {
        $product = $this->cloneVariantProductTemplate();

        ProductStock::query()->where('product_id', $product->id)->where('variant', 'Standard')->update(['qty' => 100]);
        $product->update(['minimum_order_qty' => 1]);

        return $product;
    }

    private function requireWholesaleOrderTemplate(): Order
    {
        return $this->requireFixture(
            Order::query()->where('business_module', BusinessModule::WHOLESALE)->where('order_type', OrderService::ORDER_TYPE_DEFAULT)->first(),
            'wholesale order',
        );
    }

    private function cloneVariantProductTemplate(): Model
    {
        $template = $this->requireFixture(
            Product::query()->with('wholesaleDetail', 'stocks')->find(self::VARIANT_PRODUCT_TEMPLATE_ID),
            'variant product template '.self::VARIANT_PRODUCT_TEMPLATE_ID,
        );

        $product = $template->replicate();
        $product->forceFill(['id' => (int) Product::query()->max('id') + 1])->save();

        $wholesaleDetail = $template->wholesaleDetail?->replicate();
        $wholesaleDetail?->forceFill(['product_id' => $product->id])->save();

        foreach ($template->stocks as $stock) {
            $stock->replicate()->forceFill(['product_id' => $product->id])->save();
        }

        return $product->fresh(['wholesaleDetail', 'stocks']);
    }

    private function addOrderLine(Order $order, Model $product, string $variant, int $qty): OrderDetail
    {
        return OrderDetail::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $product->user_id,
            'qty' => $qty,
            'price' => 15.0,
            'variant' => $variant,
            'product_details' => json_encode(['name' => $product->name]),
        ]);
    }

    /** @return array{products: array<int, Model>, selections: array<int, array<string, string>>, lines: array<int, array<string, mixed>>} */
    private function dispatchReorderPlan(Order $order, User $customer): array
    {
        $event = new ReorderPlanRequestedEvent(orderId: $order->id, customerId: $customer->id, isAddingToCart: false);

        event($event);

        return $this->requireFixture($event->plan, 'reorder plan');
    }

    private function cloneOrderForCustomer(Order $template, User $customer, string $status): Order
    {
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + 1,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'order_status' => $status,
            'order_type' => OrderService::ORDER_TYPE_DEFAULT,
            'business_module' => BusinessModule::WHOLESALE,
        ])->save();

        return $order;
    }

    private function stubThemeView(string $view): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/'.str_replace('.', '/', $view).'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, self::STUB_VIEW_CONTENTS);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
