<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Services\CartService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleLoginCartMergeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const GUEST_ID = 987654321;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_customer_login_moves_the_wholesale_guest_cart_to_the_customer(): void
    {
        $customer = $this->customerWithoutCart();
        $guestRow = $this->createRow(businessModule: BusinessModule::WHOLESALE, ownerId: self::GUEST_ID, isGuest: true, productId: 796);

        $this->loginWithGuestSession(customer: $customer);

        $guestRow->refresh();
        $this->assertSame((int) $customer->id, (int) $guestRow->customer_id);
        $this->assertSame(CartService::CUSTOMER, (int) $guestRow->is_guest);
        $this->assertStringStartsWith((string) $customer->id, (string) $guestRow->cart_group_id);
        $this->assertSame(0, Cart::query()->where(['customer_id' => self::GUEST_ID, 'is_guest' => 1])->count());
    }

    public function test_merge_replaces_the_customers_row_for_the_same_product_instead_of_duplicating_it(): void
    {
        $customer = $this->customerWithoutCart();
        $this->createRow(businessModule: BusinessModule::WHOLESALE, ownerId: $customer->id, isGuest: false, productId: 796);
        $guestRow = $this->createRow(businessModule: BusinessModule::WHOLESALE, ownerId: self::GUEST_ID, isGuest: true, productId: 796);

        $this->loginWithGuestSession(customer: $customer);

        $customerRows = Cart::query()->where(['customer_id' => $customer->id, 'is_guest' => 0, 'business_module' => BusinessModule::WHOLESALE])->get();
        $this->assertCount(1, $customerRows);
        $this->assertSame((int) $guestRow->id, (int) $customerRows->first()->id);
    }

    public function test_login_without_a_guest_session_changes_nothing(): void
    {
        $customer = $this->customerWithoutCart();
        $guestRow = $this->createRow(businessModule: BusinessModule::WHOLESALE, ownerId: self::GUEST_ID, isGuest: true, productId: 796);

        auth('customer')->login($customer);

        $this->assertSame(self::GUEST_ID, (int) $guestRow->refresh()->customer_id);
    }

    public function test_the_wholesale_listener_leaves_retail_guest_rows_alone(): void
    {
        $customer = $this->customerWithoutCart();
        $retailRow = $this->createRow(businessModule: BusinessModule::RETAIL, ownerId: self::GUEST_ID, isGuest: true, productId: 1);

        $this->loginWithGuestSession(customer: $customer);

        $this->assertSame(self::GUEST_ID, (int) $retailRow->refresh()->customer_id);
        $this->assertSame(1, (int) $retailRow->is_guest);
    }

    public function test_the_listener_ignores_a_login_on_another_guard(): void
    {
        $customer = $this->customerWithoutCart();
        $guestRow = $this->createRow(businessModule: BusinessModule::WHOLESALE, ownerId: self::GUEST_ID, isGuest: true, productId: 796);

        session()->put('guest_id', self::GUEST_ID);
        event(new Login('seller', $customer, false));

        $this->assertSame(self::GUEST_ID, (int) $guestRow->refresh()->customer_id);
    }

    private function loginWithGuestSession(User $customer): void
    {
        session()->put('guest_id', self::GUEST_ID);
        auth('customer')->login($customer);
    }

    private function customerWithoutCart(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where('customer_id', $customer->id)->delete();
        Cart::query()->where('customer_id', self::GUEST_ID)->delete();

        return $customer;
    }

    private function createRow(string $businessModule, int $ownerId, bool $isGuest, int $productId): Cart
    {
        $cartGroupId = ($isGuest ? 'guest' : (string) $ownerId).'-merge-'.$businessModule.'-'.$productId;
        $id = Cart::query()->insertGetId([
            'business_module' => $businessModule,
            'customer_id' => $ownerId,
            'cart_group_id' => $cartGroupId,
            'product_id' => $productId,
            'variant' => '',
            'quantity' => 10,
            'price' => 10,
            'discount' => 0,
            'is_checked' => 1,
            'seller_id' => 1,
            'seller_is' => 'admin',
            'is_guest' => (int) $isGuest,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Cart::query()->findOrFail($id);
    }
}
