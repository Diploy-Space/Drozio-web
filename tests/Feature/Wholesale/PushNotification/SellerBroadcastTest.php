<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Notification;
use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\NotificationService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class SellerBroadcastTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const BASE = 'api/v6/wholesale/seller/notifications';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_admin_broadcast_uses_the_wholesale_customer_topic(): void
    {
        $this->assertSame('sixvalley_wholesale', app(NotificationService::class)->pushTopic());
    }

    public function test_admin_broadcast_always_goes_to_customers(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.wholesale.notification.index'), [
                'title' => 'Warehouse audit',
                'description' => 'Stock audit runs this weekend',
                'sent_to' => NotificationService::AUDIENCE_SELLER,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue(DB::table('notifications')->where([
            'title' => 'Warehouse audit',
            'sent_to' => NotificationService::AUDIENCE_CUSTOMER,
            'business_module' => BusinessModule::WHOLESALE,
        ])->exists());
    }

    public function test_add_form_has_no_send_to_field(): void
    {
        $html = view('wholesale::admin-views.notification.offcanvas._notification-add')->render();

        $this->assertStringNotContainsString('name="sent_to"', $html);
    }

    public function test_seller_lists_and_marks_seen_its_wholesale_broadcasts(): void
    {
        $seller = $this->seller();
        $sellerBroadcast = $this->broadcast(audience: NotificationService::AUDIENCE_SELLER, title: 'For vendors');
        $customerBroadcast = $this->broadcast(audience: NotificationService::AUDIENCE_CUSTOMER, title: 'For customers');

        $content = $this->getJson(self::BASE, $this->sellerHeaders($seller))->assertOk()->json('content');
        $ids = array_column($content['data'], 'id');

        $this->assertContains($sellerBroadcast->id, $ids);
        $this->assertNotContains($customerBroadcast->id, $ids);
        $unseenBefore = $content['new_notification'];
        $this->assertGreaterThanOrEqual(1, $unseenBefore);

        $this->putJson(self::BASE.'/'.$sellerBroadcast->id.'/seen', [], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('content.notification_count', $unseenBefore - 1);

        $listed = collect($this->getJson(self::BASE, $this->sellerHeaders($seller))->json('content.data'))->firstWhere('id', $sellerBroadcast->id);
        $this->assertTrue($listed['is_seen']);

        $this->putJson(self::BASE.'/'.$customerBroadcast->id.'/seen', [], $this->sellerHeaders($seller))->assertNotFound();
    }

    public function test_one_seller_seeing_a_broadcast_leaves_it_unseen_for_another(): void
    {
        $seller = $this->seller();
        $broadcast = $this->broadcast(audience: NotificationService::AUDIENCE_SELLER, title: 'Shared');
        DB::table('notification_seens')->insert(['notification_id' => $broadcast->id, 'seller_id' => $seller->id + 100000, 'created_at' => now(), 'updated_at' => now()]);

        $listed = collect($this->getJson(self::BASE, $this->sellerHeaders($seller))->json('content.data'))->firstWhere('id', $broadcast->id);

        $this->assertFalse($listed['is_seen']);
    }

    public function test_vendor_panel_opens_a_wholesale_broadcast_and_marks_it_seen(): void
    {
        $seller = $this->seller();
        $broadcast = $this->broadcast(audience: NotificationService::AUDIENCE_SELLER, title: 'Panel broadcast');

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.notification.view'), ['id' => $broadcast->id])
            ->assertOk()
            ->assertJsonStructure(['notification_count', 'view']);

        $this->assertTrue(DB::table('notification_seens')->where(['notification_id' => $broadcast->id, 'seller_id' => $seller->id])->exists());
    }

    private function broadcast(string $audience, string $title): Notification
    {
        $notification = new Notification;
        $notification->forceFill([
            'sent_by' => NotificationService::SENT_BY_ADMIN,
            'sent_to' => $audience,
            'business_module' => BusinessModule::WHOLESALE,
            'title' => $title,
            'description' => $title.' description',
            'notification_count' => 1,
            'status' => 1,
        ])->save();

        return $notification;
    }

    private function seller(): Seller
    {
        $seller = $this->requireFixture(Seller::query()->where('status', 'approved')->orderBy('id')->first(), 'approved seller');
        $seller->forceFill(['auth_token' => Str::random(50), 'created_at' => now()->subYear()])->saveQuietly();

        return $seller;
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
