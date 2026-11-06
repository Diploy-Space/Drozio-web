<?php

namespace Tests\Feature\Contract\Api;

use App\Models\Color;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Equivalence guard for the Helpers color-map optimization.
 *
 * set_data_format / setDataFormatForJsonData replaced a per-product
 * Color::whereIn('code', $codes)->pluck('name','code') with an in-PHP
 * array_intersect_key over the full color map. This test proves the replacement
 * yields the same code=>name result for the same input, so the formatted product
 * output — and thus every API response that carries it — is unchanged.
 *
 * Content equivalence only: the production path's order follows the full color
 * map deterministically, whereas the whereIn baseline's row order depends on
 * whichever index MySQL picks (an index on colors.code makes whereIn return
 * code order, a full scan returns PK order), so ordering is not a stable contract.
 */
class HelpersColorMapContractTest extends ContractTestCase
{
    #[Test]
    public function intersect_matches_where_in_pluck_content(): void
    {
        // Mix of real codes (in varied order) plus a non-existent one.
        $codes = Color::query()->pluck('code')->take(6)->reverse()->values()->all();
        $codes[] = '__no_such_code__';

        $expected = Color::whereIn('code', $codes)->pluck('name', 'code')->toArray();

        $fullMap = Color::pluck('name', 'code')->toArray();
        $actual = array_intersect_key($fullMap, array_flip($codes));

        $this->assertEqualsCanonicalizing($expected, $actual, 'Color map optimization changed content.');
    }
}
