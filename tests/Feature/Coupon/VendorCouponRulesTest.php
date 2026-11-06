<?php

namespace Tests\Feature\Coupon;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Seller;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorCouponRulesTest extends TestCase
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
    }

    public static function panels(): array
    {
        return [
            'retail' => ['vendor.retail.coupon', BusinessModule::RETAIL],
            'wholesale' => ['vendor.wholesale.coupon', BusinessModule::WHOLESALE],
        ];
    }

    #[DataProvider('panels')]
    public function test_limit_for_same_user_must_be_a_positive_whole_number_within_range(string $routePrefix, string $businessModule): void
    {
        $this->skipUnlessPanelAvailable(businessModule: $businessModule);
        $seller = $this->seller();

        foreach ([0, 1.5, 2147483648] as $invalidLimit) {
            $this->actingAs($seller, 'seller')
                ->post(route($routePrefix.'.add'), $this->couponPayload(limit: $invalidLimit))
                ->assertSessionHasErrors('limit');
        }

        $this->actingAs($seller, 'seller')
            ->post(route($routePrefix.'.add'), $this->couponPayload(limit: 1))
            ->assertSessionHasNoErrors();
    }

    public static function adminPanels(): array
    {
        return [
            'retail' => ['/admin/retail/coupon/add', BusinessModule::RETAIL],
            'wholesale' => ['/admin/wholesale/coupon/add', BusinessModule::WHOLESALE],
        ];
    }

    #[DataProvider('adminPanels')]
    public function test_admin_limit_for_same_user_must_be_a_positive_whole_number_within_range(string $addUri, string $businessModule): void
    {
        $this->skipUnlessPanelAvailable(businessModule: $businessModule);
        $admin = $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
        $adminPayload = ['coupon_bearer' => 'inhouse', 'seller_id' => 'inhouse'];

        foreach ([0, 1.5, 2147483648] as $invalidLimit) {
            $this->actingAs($admin, 'admin')
                ->post($addUri, $this->couponPayload(limit: $invalidLimit) + $adminPayload)
                ->assertSessionHasErrors('limit');
        }

        $this->actingAs($admin, 'admin')
            ->post($addUri, $this->couponPayload(limit: 1) + $adminPayload)
            ->assertSessionDoesntHaveErrors('limit');
    }

    #[DataProvider('panels')]
    public function test_vendor_cannot_delete_a_coupon_used_by_an_order(string $routePrefix, string $businessModule): void
    {
        $this->skipUnlessPanelAvailable(businessModule: $businessModule);
        $seller = $this->seller();
        $usedCoupon = $this->createCoupon(sellerId: $seller->id, businessModule: $businessModule);
        $unusedCoupon = $this->createCoupon(sellerId: $seller->id, businessModule: $businessModule);
        $this->createOrderUsingCoupon(sellerId: $seller->id, couponCode: $usedCoupon->code, businessModule: $businessModule);

        $this->actingAs($seller, 'seller')->delete(route($routePrefix.'.delete', ['id' => $usedCoupon->id]));
        $this->assertNotNull($usedCoupon->fresh());

        $this->actingAs($seller, 'seller')->delete(route($routePrefix.'.delete', ['id' => $unusedCoupon->id]));
        $this->assertNull($unusedCoupon->fresh());
    }

    private function skipUnlessPanelAvailable(string $businessModule): void
    {
        if ($businessModule === BusinessModule::WHOLESALE) {
            $this->skipUnlessWholesaleAddonPublished();
        }
    }

    /** @return array<string, mixed> */
    private function couponPayload(int|float $limit): array
    {
        return [
            'coupon_type' => 'discount_on_purchase',
            'title' => 'Limit check',
            'code' => strtoupper(Str::random(10)),
            'customer_id' => 0,
            'limit' => $limit,
            'discount_type' => 'amount',
            'discount' => 5,
            'min_purchase' => 100,
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addDays(5)->toDateString(),
        ];
    }

    private function createCoupon(int $sellerId, string $businessModule): Coupon
    {
        return Coupon::query()->create([
            'added_by' => 'seller',
            'business_module' => $businessModule,
            'status' => 1,
            'coupon_type' => 'discount_on_purchase',
            'coupon_bearer' => 'seller',
            'customer_id' => 0,
            'seller_id' => $sellerId,
            'title' => 'Delete check',
            'code' => strtoupper(Str::random(10)),
            'start_date' => now()->toDateString(),
            'expire_date' => now()->addDays(5)->toDateString(),
            'min_purchase' => 100,
            'max_discount' => 0,
            'discount' => 5,
            'discount_type' => 'amount',
            'limit' => 1,
        ]);
    }

    private function createOrderUsingCoupon(int $sellerId, string $couponCode, string $businessModule): void
    {
        DB::table('orders')->insert([
            'customer_id' => 1,
            'is_guest' => 0,
            'seller_id' => $sellerId,
            'seller_is' => 'seller',
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
            'order_type' => 'default_type',
            'order_amount' => 100,
            'coupon_code' => $couponCode,
            'business_module' => $businessModule,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seller(): Seller
    {
        return $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'approved seller',
        );
    }
}
