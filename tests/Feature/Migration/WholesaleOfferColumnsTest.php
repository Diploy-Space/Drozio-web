<?php

namespace Tests\Feature\Migration;

use Illuminate\Support\Facades\Schema;
use Modules\Wholesale\app\Models\ProductWholesale;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOfferColumnsTest extends TestCase
{
    use RequiresWholesaleAddon;

    public function test_products_wholesale_carries_the_offer_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('products_wholesale', [
            'discount_percentage',
            'previous_min_price',
            'previous_max_price',
            'price_dropped_at',
            'previous_minimum_order_qty',
            'moq_reduced_at',
        ]));
    }

    public function test_offer_columns_are_fillable_and_cast(): void
    {
        $detail = new ProductWholesale;

        foreach (['discount_percentage', 'previous_min_price', 'previous_max_price', 'price_dropped_at', 'previous_minimum_order_qty', 'moq_reduced_at'] as $column) {
            $this->assertContains($column, $detail->getFillable());
        }

        $casts = $detail->getCasts();

        $this->assertSame('float', $casts['discount_percentage']);
        $this->assertSame('float', $casts['previous_min_price']);
        $this->assertSame('float', $casts['previous_max_price']);
        $this->assertSame('integer', $casts['previous_minimum_order_qty']);
        $this->assertSame('datetime', $casts['price_dropped_at']);
        $this->assertSame('datetime', $casts['moq_reduced_at']);
    }
}
