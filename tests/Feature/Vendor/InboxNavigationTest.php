<?php

namespace Tests\Feature\Vendor;

use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Chatting;
use App\Models\DeliveryMan;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class InboxNavigationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-inbox-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->stubVendorLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_inbox_lists_customer_and_delivery_man_conversations_in_separate_sections(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $deliveryMan = $this->deliveryMan();

        $this->customerMessage(seller: $seller, customer: $customer, message: 'Customer thread preview');
        $this->deliveryManMessage(seller: $seller, deliveryMan: $deliveryMan, message: 'Delivery thread preview');

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.index'))
            ->assertOk();

        $response->assertViewIs('vendor-views.chatting.inbox');
        $sections = collect($response->viewData('inboxSections'))->keyBy('type');

        $this->assertSame(['customer', 'delivery-man'], $sections->keys()->all());
        $this->assertSame([$customer->id], array_column($sections['customer']['conversations'], 'id'));
        $this->assertSame([$deliveryMan->id], array_column($sections['delivery-man']['conversations'], 'id'));
        $this->assertSame(
            route('vendor.messages.chat', ['type' => 'customer', 'user_id' => $customer->id]),
            $sections['customer']['conversations'][0]['chatRoute'],
        );
        $this->assertSame(
            route('vendor.messages.chat', ['type' => 'delivery-man', 'delivery_man_id' => $deliveryMan->id]),
            $sections['delivery-man']['conversations'][0]['chatRoute'],
        );

        $response->assertDontSee('chatting-messages-section');
    }

    public function test_inbox_counts_unread_messages_per_section(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();

        $this->customerMessage(seller: $seller, customer: $customer, message: 'Unread one', seenBySeller: 0);
        $this->customerMessage(seller: $seller, customer: $customer, message: 'Unread two', seenBySeller: 0);

        $sections = collect(
            $this->actingAs($seller, 'seller')
                ->get(route('vendor.messages.index'))
                ->assertOk()
                ->viewData('inboxSections')
        )->keyBy('type');

        $this->assertSame(2, $sections['customer']['unreadCount']);
        $this->assertSame(2, $sections['customer']['conversations'][0]['unreadCount']);
        $this->assertSame(0, $sections['delivery-man']['unreadCount']);
    }

    public function test_inbox_renders_empty_state_when_no_conversation_exists(): void
    {
        $sections = collect(
            $this->actingAs($this->seller(), 'seller')
                ->get(route('vendor.messages.index'))
                ->assertOk()
                ->viewData('inboxSections')
        )->keyBy('type');

        $this->assertSame([], $sections['customer']['conversations']);
        $this->assertSame([], $sections['delivery-man']['conversations']);
        $this->assertSame('No delivery man conversation yet', $sections['delivery-man']['emptyText']);
    }

    public function test_chat_page_opens_only_the_selected_conversation(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $otherCustomer = $this->customer();

        $this->customerMessage(seller: $seller, customer: $customer, message: 'First thread');
        $this->customerMessage(seller: $seller, customer: $otherCustomer, message: 'Second thread');

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.chat', ['type' => 'customer', 'user_id' => $otherCustomer->id]))
            ->assertOk();

        $response->assertViewIs('vendor-views.chatting.index');
        $this->assertSame($otherCustomer->id, $response->viewData('activeUser')['id']);
        $this->assertSame('customer', $response->viewData('userType'));
    }

    public function test_chat_page_without_a_selected_conversation_redirects_to_the_inbox(): void
    {
        $seller = $this->seller();
        $this->customerMessage(seller: $seller, customer: $this->customer(), message: 'Thread');

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.chat', ['type' => 'customer']))
            ->assertRedirect(route('vendor.messages.index'));
    }

    public function test_chat_page_with_an_unknown_type_redirects_to_the_inbox(): void
    {
        $this->actingAs($this->seller(), 'seller')
            ->get(route('vendor.messages.chat', ['type' => 'supplier', 'user_id' => 1]))
            ->assertRedirect(route('vendor.messages.index'));
    }

    public function test_chat_page_marks_the_opened_thread_as_seen(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $message = $this->customerMessage(seller: $seller, customer: $customer, message: 'Unread', seenBySeller: 0);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.chat', ['type' => 'customer', 'user_id' => $customer->id]))
            ->assertOk();

        $this->assertSame(1, (int) $message->fresh()->seen_by_seller);
    }

    private function seller(): Seller
    {
        return Seller::query()->create([
            'f_name' => 'Inbox',
            'l_name' => 'Vendor',
            'phone' => '0190000'.random_int(1000, 9999),
            'email' => 'inbox-vendor-'.random_int(1000, 999999).'@example.com',
            'password' => bcrypt('12345678'),
            'status' => 'approved',
        ]);
    }

    private function customer(): User
    {
        return User::query()->create([
            'f_name' => 'Inbox',
            'l_name' => 'Customer',
            'phone' => '0180000'.random_int(1000, 9999),
            'email' => 'inbox-customer-'.random_int(1000, 999999).'@example.com',
            'password' => bcrypt('12345678'),
        ]);
    }

    private function deliveryMan(): DeliveryMan
    {
        return DeliveryMan::query()->create([
            'f_name' => 'Inbox',
            'l_name' => 'Delivery',
            'phone' => '0170000'.random_int(1000, 9999),
            'email' => 'inbox-delivery-'.random_int(1000, 999999).'@example.com',
            'password' => bcrypt('12345678'),
            'identity_number' => (string) random_int(100000, 999999),
            'identity_type' => 'nid',
            'is_active' => 1,
        ]);
    }

    private function customerMessage(Seller $seller, User $customer, string $message, int $seenBySeller = 1): Chatting
    {
        return Chatting::query()->create([
            'user_id' => $customer->id,
            'seller_id' => $seller->id,
            'message' => $message,
            'sent_by_customer' => 1,
            'seen_by_seller' => $seenBySeller,
            'notification_receiver' => 'seller',
        ]);
    }

    private function deliveryManMessage(Seller $seller, DeliveryMan $deliveryMan, string $message): Chatting
    {
        return Chatting::query()->create([
            'delivery_man_id' => $deliveryMan->id,
            'seller_id' => $seller->id,
            'message' => $message,
            'sent_by_delivery_man' => 1,
            'seen_by_seller' => 1,
            'notification_receiver' => 'seller',
        ]);
    }

    private function stubVendorLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }
}
