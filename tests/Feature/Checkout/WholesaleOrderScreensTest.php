<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Seller;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOrderScreensTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-order-screen-views';

    private const STUBBED_ADMIN_VIEWS = ['list', 'details', 'refund-list', 'refund-details'];

    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();

        foreach (self::STUBBED_ADMIN_VIEWS as $view) {
            $this->stubView(path: 'admin-views/orders/'.$view, contents: $view);
        }
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

    public function test_admin_lists_wholesale_orders_only(): void
    {
        $sellers = $this->approvedSellers();
        $wholesaleOrder = $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $sellers[0]);
        $retailOrder = $this->createOrder(businessModule: BusinessModule::RETAIL, seller: $sellers[0]);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'searchValue' => $wholesaleOrder->id]))
            ->assertOk();

        $this->assertTrue($response->viewData('orders')->getCollection()->contains('id', $wholesaleOrder->id));

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'searchValue' => $retailOrder->id]))
            ->assertOk()
            ->assertViewHas('orders', static fn ($orders): bool => ! $orders->getCollection()->contains('id', $retailOrder->id));
    }

    public function test_admin_opens_a_wholesale_order_and_never_a_retail_one(): void
    {
        $seller = $this->approvedSellers()[0];
        $wholesaleOrder = $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $seller);
        $retailOrder = $this->createOrder(businessModule: BusinessModule::RETAIL, seller: $seller);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', [$wholesaleOrder->id]))
            ->assertOk()
            ->assertViewHas('order', static fn ($order): bool => (int) $order->id === (int) $wholesaleOrder->id && $order->shop_name !== null);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.details', [$retailOrder->id]))
            ->assertNotFound();
    }

    public function test_admin_refund_list_renders(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.refund-section.refund.list', ['pending']))
            ->assertOk()
            ->assertViewHas('statusCounts', static fn (array $counts): bool => array_keys($counts) === ['pending', 'approved', 'refunded', 'rejected']);
    }

    public function test_a_vendor_cannot_open_another_vendors_wholesale_order(): void
    {
        $sellers = $this->approvedSellers();

        if (count($sellers) < 2) {
            $this->markTestSkipped('Test database has fewer than two approved sellers.');
        }

        $ownOrder = $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $sellers[0]);
        $otherOrder = $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $sellers[1]);

        $this->actingAs($sellers[0], 'seller')
            ->get(route('vendor.wholesale.orders.details', [$ownOrder->id]))
            ->assertOk();

        $this->actingAs($sellers[0], 'seller')
            ->get(route('vendor.wholesale.orders.details', [$otherOrder->id]))
            ->assertNotFound();
    }

    public function test_a_vendor_sees_only_refunds_on_their_own_wholesale_orders(): void
    {
        $sellers = $this->approvedSellers();

        if (count($sellers) < 2) {
            $this->markTestSkipped('Test database has fewer than two approved sellers.');
        }

        $ownRefund = $this->createRefund(order: $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $sellers[0]));
        $otherRefund = $this->createRefund(order: $this->createOrder(businessModule: BusinessModule::WHOLESALE, seller: $sellers[1]));

        $this->stubView(path: 'vendor-views/orders/refund-list', contents: '@foreach ($refundRequests as $refundRequest)[refund-{{ $refundRequest->id }}]@endforeach');

        $this->actingAs($sellers[0], 'seller')
            ->get(route('vendor.wholesale.refund.list', ['pending']))
            ->assertOk()
            ->assertSee('[refund-'.$ownRefund->id.']', false)
            ->assertDontSee('[refund-'.$otherRefund->id.']', false);

        $this->actingAs($sellers[0], 'seller')
            ->get(route('vendor.wholesale.refund.details', [$otherRefund->id]))
            ->assertNotFound();
    }

    private function createRefund(Order $order): RefundRequest
    {
        $refund = new RefundRequest;
        $refund->forceFill([
            'order_details_id' => 0,
            'customer_id' => $order->customer_id,
            'status' => 'pending',
            'amount' => 10,
            'product_id' => 0,
            'order_id' => $order->id,
            'refund_reason' => 'Wholesale refund scope fixture',
        ])->save();

        return $refund;
    }

    private function createOrder(string $businessModule, Seller $seller): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + 1,
            'business_module' => $businessModule,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'is_guest' => 0,
        ])->save();

        return $order;
    }

    /** @return array<int, Seller> */
    private function approvedSellers(): array
    {
        $sellers = Seller::query()->where('status', 'approved')->has('shop')->orderBy('id')->limit(2)->get()->all();

        if ($sellers === []) {
            $this->markTestSkipped('Test database has no approved seller with a shop.');
        }

        return $sellers;
    }

    private function stubView(string $path, string $contents): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.$path.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
