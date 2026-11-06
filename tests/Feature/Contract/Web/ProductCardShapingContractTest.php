<?php

namespace Tests\Feature\Contract\Web;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Correctness guard for the prepared product-card attributes.
 *
 * The card partials will read $product->overall_rating / total_reviews /
 * discount_value / discount_label / discounted_unit_price_label instead of calling
 * getOverallRating()/getProductPriceByType() inline. This asserts each accessor
 * returns EXACTLY what the corresponding helper call returns, so the cards render
 * identically in both themes.
 */
class ProductCardShapingContractTest extends ContractTestCase
{
    #[Test]
    public function card_accessors_equal_the_helper_calls(): void
    {
        $customerId = DB::table('users')->insertGetId([
            'name' => 'c', 'f_name' => 'c', 'l_name' => 'c', 'email' => 'card@e.test',
            'password' => 'x', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Card Product', 'slug' => 'card-product', 'added_by' => 'admin', 'user_id' => 1,
            'product_type' => 'physical', 'brand_id' => null, 'unit_price' => 100,
            'current_stock' => 10, 'discount' => 10, 'discount_type' => 'percent',
            'tax' => 0, 'tax_type' => 'percent', 'tax_model' => 'exclude', 'status' => 1, 'request_status' => 1,
            'thumbnail' => 'd.png', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('products_retail')->insert([
            'product_id' => $productId, 'purchase_price' => 90,
            'variation' => json_encode([]), 'color_image' => json_encode([]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([5, 4, 3] as $r) {
            DB::table('reviews')->insert([
                'product_id' => $productId, 'customer_id' => $customerId, 'rating' => $r,
                'comment' => 'c', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $product = Product::with('reviews')->find($productId);

        $this->assertSame(getOverallRating($product->reviews), $product->overall_rating);
        $this->assertSame($product->reviews->count(), $product->total_reviews);
        $this->assertSame(getProductPriceByType(product: $product, type: 'discount', result: 'value'), $product->discount_value);
        $this->assertSame(getProductPriceByType(product: $product, type: 'discount', result: 'string'), $product->discount_label);
        $this->assertSame(getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string'), $product->discounted_unit_price_label);

        // Non-trivial values so we know the discount path actually ran.
        $this->assertGreaterThan(0, $product->discount_value);
        $this->assertSame(3, $product->total_reviews);
        fwrite(STDERR, "\n[CARD-SHAPING] rating=".json_encode($product->overall_rating).", discount_value={$product->discount_value}\n");
    }
}
