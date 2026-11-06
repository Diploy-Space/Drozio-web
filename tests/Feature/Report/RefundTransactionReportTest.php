<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\RefundTransactionReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class RefundTransactionReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/refund-transaction-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private int $nextOrderId = 0;

    private array $refundIds = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $this->refundIds[$businessModule] = $this->createRefundTransaction(businessModule: $businessModule)->id;
        }
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public static function modulePages(): array
    {
        return self::withWholesaleDataSets(
            dataSets: [
                'retail' => [BusinessModule::RETAIL, BusinessModule::WHOLESALE],
            ],
            wholesaleDataSets: [
                'wholesale' => [BusinessModule::WHOLESALE, BusinessModule::RETAIL],
            ],
        );
    }

    #[DataProvider('modulePages')]
    public function test_page_lists_only_its_own_module_refunds(string $businessModule, string $otherModule): void
    {
        $refundId = $this->refundIds[$businessModule];
        $otherRefundId = $this->refundIds[$otherModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.refund-transaction-list', ['payment_method' => 'cash']))
            ->assertOk()
            ->assertViewHas('refundTransactions', fn (LengthAwarePaginator $refunds): bool => $refunds->getCollection()->contains('id', $refundId)
                && ! $refunds->getCollection()->contains('id', $otherRefundId)
                && $refunds->getCollection()->every(fn (RefundTransaction $refund): bool => $refund->order->business_module === $businessModule));
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_refunds(string $businessModule, string $otherModule): void
    {
        Excel::fake();

        $refundId = $this->refundIds[$businessModule];
        $otherRefundId = $this->refundIds[$otherModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.refund-transaction-export', ['payment_method' => 'all']))
            ->assertOk();

        Excel::assertDownloaded('Refund-Transaction-Report-List.xlsx', function (RefundTransactionReportExport $export) use ($refundId, $otherRefundId): bool {
            $transactions = $export->view()->getData()['data']['transactions'];

            return $transactions->contains('id', $refundId) && ! $transactions->contains('id', $otherRefundId);
        });
    }

    public function test_unknown_payment_method_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.transaction.refund-transaction-list', ['payment_method' => 'bitcoin']))
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_method']);
    }

    private function createRefundTransaction(string $businessModule): RefundTransaction
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'order_group_id' => 'refund-'.Str::lower(Str::random(16)),
        ])->save();

        $refund = $this->requireFixture(RefundTransaction::query()->orderBy('id')->first(), 'refund transaction')->replicate();
        $refund->forceFill([
            'order_id' => $order->id,
            'payment_method' => 'cash',
        ])->save();

        return $refund;
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
    }

    private function stubAdminLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
