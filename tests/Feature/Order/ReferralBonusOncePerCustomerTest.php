<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Models\Admin;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\Seller;
use App\Models\User;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class ReferralBonusOncePerCustomerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const IN_HOUSE_SELLER_ID = 1;

    private const ENTRY_ADMIN_WEB = 'admin web';

    private const ENTRY_VENDOR_WEB = 'vendor web';

    private const ENTRY_VENDOR_API = 'vendor api';

    private const ENTRY_DELIVERYMAN_API = 'deliveryman api';

    private User $referrer;

    private User $customer;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        Event::fake([OrderStatusEvent::class]);

        foreach (['ref_earning_status' => 1, 'ref_earning_exchange_rate' => 20, 'wallet_status' => 1] as $type => $value) {
            BusinessSetting::query()->updateOrInsert(['type' => $type], ['value' => $value]);
        }

        Cache::flush();
        Helpers::resetMemo();

        $this->customer = $this->requireFixture(User::query()->where('id', '>', 0)->whereDoesntHave('orders')->orderBy('id')->first(), 'customer without orders');
        $this->referrer = $this->requireFixture(User::query()->where('id', '>', 0)->whereKeyNot($this->customer->id)->orderBy('id')->first(), 'second customer');
        User::query()->whereKey($this->customer->id)->update(['referred_by' => $this->referrer->id]);
        DB::table('referral_customers')->where('user_id', $this->customer->id)->delete();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_record_referred_customer_delivery_flips_once_and_allows_customers_without_a_record(): void
    {
        $service = app(ReferralCustomerService::class);

        $this->assertSame(['notifies_promoter' => false, 'allows_referrer_bonus' => true], $service->recordReferredCustomerDelivery(customerId: $this->customer->id));

        $this->createReferralRecord(deliveredNotify: 0);

        $this->assertSame(['notifies_promoter' => true, 'allows_referrer_bonus' => true], $service->recordReferredCustomerDelivery(customerId: $this->customer->id));
        $this->assertSame(['notifies_promoter' => false, 'allows_referrer_bonus' => false], $service->recordReferredCustomerDelivery(customerId: $this->customer->id));
        $this->assertSame(1, (int) DB::table('referral_customers')->where('user_id', $this->customer->id)->value('delivered_notify'));
    }

    #[DataProvider('retailEntryPoints')]
    public function test_retail_delivery_pays_no_bonus_when_the_customer_was_delivered_in_another_module(string $entryPoint): void
    {
        $this->createReferralRecord(deliveredNotify: 1);
        $creditsBefore = $this->referralCredits();

        $this->deliverRetailOrder(entryPoint: $entryPoint);

        $this->assertSame($creditsBefore, $this->referralCredits());
        $this->assertSame(0, $this->promoterNotificationCount());
    }

    #[DataProvider('retailEntryPoints')]
    public function test_retail_first_delivery_pays_the_bonus_and_marks_the_customer(string $entryPoint): void
    {
        $this->createReferralRecord(deliveredNotify: 0);
        $creditsBefore = $this->referralCredits();

        $this->deliverRetailOrder(entryPoint: $entryPoint);

        $this->assertSame($creditsBefore + 1, $this->referralCredits());
        $this->assertSame(1, (int) DB::table('referral_customers')->where('user_id', $this->customer->id)->value('delivered_notify'));
        $this->assertSame(1, $this->promoterNotificationCount());
    }

    public static function retailEntryPoints(): array
    {
        return [
            self::ENTRY_ADMIN_WEB => [self::ENTRY_ADMIN_WEB],
            self::ENTRY_VENDOR_WEB => [self::ENTRY_VENDOR_WEB],
            self::ENTRY_VENDOR_API => [self::ENTRY_VENDOR_API],
            self::ENTRY_DELIVERYMAN_API => [self::ENTRY_DELIVERYMAN_API],
        ];
    }

    private function deliverRetailOrder(string $entryPoint): void
    {
        $seller = $this->approvedSeller();
        $deliveryMan = $entryPoint === self::ENTRY_DELIVERYMAN_API ? $this->createDeliveryMan() : null;
        $order = $this->createRetailOrder(seller: $seller, deliveryManId: $deliveryMan?->id);
        $payload = ['id' => $order->id, 'order_status' => OrderService::STATUS_DELIVERED];

        $response = match ($entryPoint) {
            self::ENTRY_ADMIN_WEB => $this->actingAs($this->admin(), 'admin')->postJson(route('admin.retail.orders.status'), $payload),
            self::ENTRY_VENDOR_WEB => $this->actingAs($seller, 'seller')->postJson(route('vendor.retail.orders.status'), $payload),
            self::ENTRY_VENDOR_API => $this->putJson('api/v6/seller/orders/'.$order->id.'/status', ['order_status' => OrderService::STATUS_DELIVERED], $this->sellerHeaders($this->withAuthToken(model: $seller))),
            self::ENTRY_DELIVERYMAN_API => $this->putJson('api/v6/delivery-man/orders/'.$order->id.'/status', ['status' => OrderService::STATUS_DELIVERED], $this->deliveryManHeaders($deliveryMan)),
        };

        $response->assertOk();
        $this->assertSame(OrderService::STATUS_DELIVERED, $order->refresh()->order_status, $entryPoint.': '.$response->getContent());
    }

    private function createRetailOrder(Seller $seller, ?int $deliveryManId): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + random_int(100000, 900000),
            'business_module' => BusinessModule::RETAIL,
            'customer_id' => $this->customer->id,
            'is_guest' => 0,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'order_type' => 'default_type',
            'order_group_id' => 'referral-once-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_OUT_FOR_DELIVERY,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'admin_commission' => 5,
            'shipping_cost' => 0,
            'is_shipping_free' => 0,
            'shipping_responsibility' => 'inhouse_shipping',
            'discount_amount' => 0,
            'discount_type' => null,
            'coupon_code' => null,
            'extra_discount' => 0,
            'refer_and_earn_discount' => 0,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'delivery_man_id' => $deliveryManId,
            'deliveryman_charge' => 0,
            'is_pause' => 0,
            'created_at' => now(),
        ])->save();

        return $order;
    }

    private function createReferralRecord(int $deliveredNotify): void
    {
        DB::table('referral_customers')->insert([
            'user_id' => $this->customer->id,
            'refer_by' => $this->referrer->id,
            'delivered_notify' => $deliveredNotify,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDeliveryMan(): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => 0,
            'f_name' => 'Referral',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'referral-rider-'.Str::lower(Str::random(10)).'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => 1,
            'auth_token' => Str::random(50),
        ])->save();

        return $deliveryMan;
    }

    private function withAuthToken(Seller $model): Seller
    {
        $model->forceFill(['auth_token' => Str::random(50)])->save();

        return $model->fresh();
    }

    private function referralCredits(): int
    {
        return DB::table('wallet_transactions')->where(['user_id' => $this->referrer->id, 'reference' => 'earned_by_referral'])->count();
    }

    private function promoterNotificationCount(): int
    {
        return Event::dispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->type === OrderService::NOTIFICATION_TYPE_PROMOTER)->count();
    }

    private function approvedSeller(): Seller
    {
        return $this->requireFixture(
            Seller::query()
                ->where('status', 'approved')
                ->where('id', '!=', self::IN_HOUSE_SELLER_ID)
                ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
                ->orderBy('id')
                ->first(),
            'approved seller with a vendor shop',
        );
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
