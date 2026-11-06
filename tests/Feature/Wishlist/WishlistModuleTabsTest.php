<?php

namespace Tests\Feature\Wishlist;

use App\Enums\BusinessModule;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\WishlistService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class WishlistModuleTabsTest extends TestCase
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
        $this->bootActiveTheme();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_retail_tab_rerender_lists_only_retail_products_with_retail_links(): void
    {
        [$customer, $retailProduct, $wholesaleProduct] = $this->customerWithBothWishlists();
        $secondRetailProduct = $this->product(module: BusinessModule::RETAIL, exceptId: $retailProduct->id);
        $this->addToWishlist(customer: $customer, product: $secondRetailProduct);

        $html = $this->removeFromWishlist(customer: $customer, productId: $retailProduct->id, businessModule: null)
            ->assertJsonPath('count', 2)
            ->json('wishlist');

        $this->assertStringContainsString(route('product', $secondRetailProduct->slug), $html);
        $this->assertStringNotContainsString($wholesaleProduct->slug, $html);
    }

    public function test_unknown_wishlist_tab_is_rejected(): void
    {
        [$customer] = $this->customerWithBothWishlists();

        $this->actingAs($customer, 'customer')
            ->from(route('wishlists.index'))
            ->get(route('wishlists.index', ['business_module' => 'auction']))
            ->assertRedirect(route('wishlists.index'))
            ->assertSessionHasErrors('business_module');
    }

    public function test_removing_from_the_wholesale_tab_rerenders_that_tab_and_reports_the_total_count(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        [$customer, $retailProduct, $wholesaleProduct] = $this->customerWithBothWishlists();
        $secondWholesaleProduct = $this->product(module: BusinessModule::WHOLESALE, exceptId: $wholesaleProduct->id);
        $this->addToWishlist(customer: $customer, product: $secondWholesaleProduct);

        $html = $this->removeFromWishlist(customer: $customer, productId: $wholesaleProduct->id, businessModule: BusinessModule::WHOLESALE)
            ->assertJsonPath('count', 2)
            ->json('wishlist');

        $this->assertStringContainsString(route('wholesale.products.details', ['slug' => $secondWholesaleProduct->slug]), $html);
        $this->assertStringNotContainsString(route('product', $secondWholesaleProduct->slug), $html);
        $this->assertStringNotContainsString($retailProduct->slug, $html);
    }

    public function test_wishlist_counts_are_split_by_business_module(): void
    {
        [$customer] = $this->customerWithBothWishlists();

        $this->assertSame(
            [BusinessModule::RETAIL => 1, BusinessModule::WHOLESALE => 1],
            collect(app(WishlistService::class)->getCustomerCountsByBusinessModule(customerId: $customer->id))->sortKeys()->all(),
        );
        $this->assertSame([], app(WishlistService::class)->getCustomerCountsByBusinessModule(customerId: null));
    }

    public function test_clearing_one_module_wishlist_keeps_the_other(): void
    {
        [$customer, $retailProduct] = $this->customerWithBothWishlists();

        $this->actingAs($customer, 'customer')
            ->from(route('wishlists.index'))
            ->delete(route('wishlists.destroy-all', ['business_module' => BusinessModule::WHOLESALE]))
            ->assertRedirect(route('wishlists.index'));

        $this->assertSame([$retailProduct->id], Wishlist::query()->where('customer_id', $customer->id)->pluck('product_id')->all());
    }

    public function test_header_wishlist_badge_counts_modules_with_items_and_links_each_tab(): void
    {
        [$customer] = $this->customerWithBothWishlists();

        foreach ([route('wishlists.header'), route('wholesale.wishlist.header')] as $headerUrl) {
            $html = $this->actingAs($customer, 'customer')->getJson($headerUrl)->assertOk()->json('html');

            $this->assertMatchesRegularExpression('/<span[^>]*>\s*2\s*<\/span>/', $html);
            $this->assertStringContainsString(route('wholesale.wishlist.index'), $html);
            $this->assertStringContainsString(e(route('wishlists.destroy-all', ['business_module' => BusinessModule::RETAIL])), $html);
        }
    }

    public function test_unavailable_wholesale_module_is_left_out_of_every_wishlist_count(): void
    {
        [$customer, $retailProduct] = $this->customerWithBothWishlists();

        $this->partialMock(BusinessModuleManager::class)
            ->shouldReceive('isAvailable')
            ->andReturnUsing(fn (string $key): bool => $key !== BusinessModule::WHOLESALE);

        $wishlistService = app(WishlistService::class);

        $this->assertSame(1, $wishlistService->countForCustomer(customerId: $customer->id));
        $this->assertSame([$retailProduct->id], $wishlistService->getProductIds(customerId: $customer->id));
        $this->assertSame([BusinessModule::RETAIL => 1], $wishlistService->getCustomerCountsByBusinessModule(customerId: $customer->id));

        $html = $this->actingAs($customer, 'customer')
            ->withSession(['wish_list' => [1, 2, 3, 4, 5]])
            ->getJson(route('wishlists.header'))
            ->assertOk()
            ->json('html');

        $this->assertMatchesRegularExpression('/<span[^>]*>\s*1\s*<\/span>/', $html);
    }

    private function removeFromWishlist(User $customer, int $productId, ?string $businessModule): TestResponse
    {
        return $this->actingAs($customer, 'customer')
            ->deleteJson(
                route('wishlists.destroy', array_filter(['productId' => $productId, 'business_module' => $businessModule])),
                [],
                ['X-Requested-With' => 'XMLHttpRequest'],
            )
            ->assertOk();
    }

    private function bootActiveTheme(): void
    {
        $themePath = base_path('resources/themes/'.config('app.theme'));

        if (! defined('VIEW_FILE_NAMES')) {
            define('VIEW_FILE_NAMES', include $themePath.'/file_names.php');
        }

        $this->app['view']->addLocation($themePath);
    }

    /** @return array{0: User, 1: Product, 2: Product} */
    private function customerWithBothWishlists(): array
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Wishlist::query()->where('customer_id', $customer->id)->delete();

        $retailProduct = $this->product(module: BusinessModule::RETAIL);
        $wholesaleProduct = $this->product(module: BusinessModule::WHOLESALE);

        $this->addToWishlist(customer: $customer, product: $retailProduct);
        $this->addToWishlist(customer: $customer, product: $wholesaleProduct);

        return [$customer, $retailProduct, $wholesaleProduct];
    }

    private function product(string $module, ?int $exceptId = null): Product
    {
        return $this->requireFixture(
            Product::query()->active()->businessModule($module)->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))->first(),
            'active '.$module.' product',
        );
    }

    private function addToWishlist(User $customer, Product $product): void
    {
        Wishlist::query()->create(['customer_id' => $customer->id, 'product_id' => $product->id]);
    }
}
