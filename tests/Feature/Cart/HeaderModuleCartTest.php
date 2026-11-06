<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\New\CartService as RetailCartService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Services\CartService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class HeaderModuleCartTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

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

    public function test_owner_cart_summary_counts_only_its_own_business_module(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customerWithEmptyCart();
        $this->addCartRow(customer: $customer, module: BusinessModule::RETAIL, price: 100, discount: 0, quantity: 1);
        $this->addCartRow(customer: $customer, module: BusinessModule::RETAIL, price: 100, discount: 0, quantity: 2);
        $this->addCartRow(customer: $customer, module: BusinessModule::WHOLESALE, price: 50, discount: 5, quantity: 10);

        $retailSummary = app(RetailCartService::class)->getOwnerCartSummary(customerId: $customer->id, guestId: null);
        $wholesaleSummary = app(CartService::class)->getOwnerCartSummary(customerId: $customer->id, guestId: null);

        $this->assertSame(2, $retailSummary['item_count']);
        $this->assertSame(1, $wholesaleSummary['item_count']);
        $this->assertEqualsWithDelta(450.0, $wholesaleSummary['total_amount'], 0.0001);
    }

    public function test_owner_cart_summary_is_empty_without_an_owner(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->assertSame(
            ['item_count' => 0, 'total_amount' => 0.0],
            app(CartService::class)->getOwnerCartSummary(customerId: null, guestId: null),
        );
    }

    public function test_header_cart_badge_counts_modules_with_items_not_products(): void
    {
        $customer = $this->customerWithEmptyCart();
        $this->addCartRow(customer: $customer, module: BusinessModule::RETAIL, price: 100, discount: 0, quantity: 3);
        $this->addCartRow(customer: $customer, module: BusinessModule::RETAIL, price: 100, discount: 0, quantity: 1);
        $this->addCartRow(customer: $customer, module: BusinessModule::WHOLESALE, price: 50, discount: 0, quantity: 10);

        $html = $this->actingAs($customer, 'customer')
            ->getJson(route('wholesale.cart.header'))
            ->assertOk()
            ->json('html');

        $this->assertMatchesRegularExpression('/<span class="[^"]*count[^"]*"[^>]*>\s*2\s*<\/span>/', $html);
        $this->assertStringContainsString(route('shop-cart'), $html);
        $this->assertStringContainsString(route('wholesale.shop-cart'), $html);
        $this->assertStringContainsString(route('wholesale.cart.clear'), $html);
    }

    public function test_clearing_the_wholesale_cart_keeps_the_retail_cart(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customerWithEmptyCart();
        $this->addCartRow(customer: $customer, module: BusinessModule::RETAIL, price: 100, discount: 0, quantity: 1);
        $this->addCartRow(customer: $customer, module: BusinessModule::WHOLESALE, price: 50, discount: 0, quantity: 10);

        $this->actingAs($customer, 'customer')
            ->from(route('wholesale.products.index'))
            ->post(route('wholesale.cart.clear'))
            ->assertRedirect(route('wholesale.products.index'));

        $this->assertSame(0, $this->cartRowCount(customer: $customer, module: BusinessModule::WHOLESALE));
        $this->assertSame(1, $this->cartRowCount(customer: $customer, module: BusinessModule::RETAIL));
    }

    private function customerWithEmptyCart(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER])->delete();

        return $customer;
    }

    private function addCartRow(User $customer, string $module, float $price, float $discount, int $quantity): void
    {
        $product = $this->requireFixture(
            Product::query()->active()->where('business_module', $module)->whereDoesntHave('clearanceSale')->where('discount', 0)->inRandomOrder()->first(),
            'active '.$module.' product without a discount',
        );

        $cart = new Cart;
        $cart->forceFill([
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'cart_group_id' => $customer->id.'-test-'.$module,
            'product_id' => $product->id,
            'product_type' => $product->product_type,
            'variant' => uniqid(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'tax' => 0,
            'is_checked' => 1,
            'slug' => $product->slug,
            'name' => $product->name,
            'seller_id' => 1,
            'seller_is' => 'admin',
            'business_module' => $module,
        ])->save();
    }

    private function cartRowCount(User $customer, string $module): int
    {
        return Cart::query()->businessModule($module)->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER])->count();
    }
}
