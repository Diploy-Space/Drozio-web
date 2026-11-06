<?php

namespace Tests\Feature\Contract\Web;

use App\Services\Cache\AppCache;
use App\Utils\Helpers;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Proves the per-request memoization added to App\Utils\Helpers (and the currency
 * / settings helpers routed through it) collapses repeated DB reads to one query
 * per distinct argument for the lifetime of a request — with no cache driver, no
 * changed return values.
 *
 * "Before" is simulated by calling Helpers::resetMemo() ahead of each invocation,
 * which reproduces the pre-memoization behaviour (every call re-queries). "After"
 * lets the static store stand, as it does inside one real request. Both paths run
 * against the same seeded tables so the query-count delta is the whole effect.
 *
 * Each surface below repeats the helper mix its hot render loop actually performs
 * (product cards / price cells / currency_list) so the numbers map to real pages.
 */
class HelpersMemoizationContractTest extends ContractTestCase
{
    private const REPEAT = 50;

    #[Test]
    public function memoization_collapses_repeated_helper_queries_per_surface(): void
    {
        $surfaces = [
            // Homepage renders many product cards; each price cell resolves the
            // exchange rate and the default currency row.
            'WEB-HOMEPAGE' => fn() => [
                exchangeRate('USD'),
                Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency')),
            ],
            // Product listing adds the paginator's page-size lookup on top of the
            // same per-price currency resolution.
            'PRODUCT-LISTING' => fn() => [
                exchangeRate('USD'),
                Helpers::pagination_limit(),
                Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency')),
            ],
            // User API /config builds currency_list + resolves the active code.
            'USER-API-HOME' => fn() => [
                Helpers::getCurrencyByCode('USD'),
                getCurrencyCode(type: 'default'),
                Helpers::getCurrencyById(getWebConfig(name: 'system_default_currency')),
            ],
        ];

        foreach ($surfaces as $label => $callMix) {
            $before = $this->countQueries(fn() => $this->repeat($callMix, resetEachCall: true));
            $after = $this->countQueries(fn() => $this->repeat($callMix, resetEachCall: false));

            fwrite(STDERR, "\n[MEMO][{$label}] {$this->summary($before)} => {$this->summary($after)} "
                . "(over " . self::REPEAT . " render iterations)\n");

            // After memoization the query count must not scale with the number of
            // render iterations: it is bounded by the distinct arguments touched.
            $this->assertLessThan(
                $before,
                $after,
                "{$label}: memoization did not reduce query count."
            );
            $this->assertLessThanOrEqual(
                8,
                $after,
                "{$label}: memoized query count grew with iterations — memo not holding."
            );
        }
    }

    /**
     * Runs the call mix REPEAT times; when $resetEachCall, clears BOTH cache
     * layers before each call (request memo + AppCache groups) to reproduce the
     * fully-uncached "before" behaviour. Otherwise both layers stand, as in one
     * real request.
     */
    private function repeat(callable $callMix, bool $resetEachCall): void
    {
        for ($i = 0; $i < self::REPEAT; $i++) {
            if ($resetEachCall) {
                Helpers::resetMemo();
                AppCache::forgetAllGroups();
            }
            $callMix();
        }
    }

    private function countQueries(callable $work): int
    {
        Helpers::resetMemo();
        AppCache::forgetAllGroups();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $work();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        return $count;
    }

    private function summary(int $queries): string
    {
        return "{$queries} queries";
    }
}
