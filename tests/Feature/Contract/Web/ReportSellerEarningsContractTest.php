<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Admin\EarningReportController;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class ReportSellerEarningsContractTest extends ContractTestCase
{
    use SeedsCatalog;

    #[Test]
    public function seller_names_are_loaded_with_a_single_query(): void
    {
        $this->seedSellerEarnings(sellers: 6);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = app(EarningReportController::class)
            ->seller_earning_query_table('2000-01-01 00:00:00', '2035-01-01 00:00:00');
        $sellerQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'from `sellers`'))
            ->count();
        DB::disableQueryLog();

        fwrite(STDERR, "\n[REPORT] sellers in report=".count($result['seller_earn_table'])
            .", sellers-table queries={$sellerQueries}\n");

        $this->assertNotEmpty($result['seller_earn_table']);
        $firstRow = reset($result['seller_earn_table']);
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertStringContainsString('Vend', $firstRow['name']);

        $this->assertLessThanOrEqual(2, $sellerQueries, "Ran {$sellerQueries} sellers queries — Seller::find N+1 regression.");
    }
}
