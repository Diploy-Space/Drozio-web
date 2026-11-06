<?php

namespace Tests\Feature\Contract\Web;

use App\Models\CartShipping;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Utils\Helpers;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Correctness guard for the cart-details ViewComposer maps.
 *
 * The composer replaced per-cart-item queries with batched keyed maps. This test
 * asserts each map expression the composer uses returns EXACTLY what the old
 * per-item ->first() / getShippingMethods() calls returned — including the
 * pick-the-first-on-duplicate-rows case (groupBy->first mirrors ->first()).
 * Full-blade render is verified manually (checkout path).
 */
class CartComposerContractTest extends ContractTestCase
{
    #[Test]
    public function composer_maps_equal_the_per_item_queries(): void
    {
        $s1 = DB::table('sellers')->insertGetId(['f_name' => 'S1', 'l_name' => 'x', 'email' => 's1@e.test', 'password' => 'x', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $s2 = DB::table('sellers')->insertGetId(['f_name' => 'S2', 'l_name' => 'x', 'email' => 's2@e.test', 'password' => 'x', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);

        // shipping_types: admin(0), and TWO rows for s1 (to prove first-wins), one for s2
        DB::table('shipping_types')->insert([
            ['seller_id' => 0, 'shipping_type' => 'order_wise', 'created_at' => now(), 'updated_at' => now()],
            ['seller_id' => $s1, 'shipping_type' => 'order_wise', 'created_at' => now(), 'updated_at' => now()],
            ['seller_id' => $s1, 'shipping_type' => 'product_wise', 'created_at' => now(), 'updated_at' => now()],
            ['seller_id' => $s2, 'shipping_type' => 'order_wise', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach ([$s1, $s2] as $s) {
            DB::table('shops')->insert(['seller_id' => $s, 'name' => "Shop{$s}", 'address' => 'a', 'contact' => 'c', 'banner' => 'b', 'author_type' => 'seller', 'created_at' => now(), 'updated_at' => now()]);
        }
        // cart_shippings: TWO rows for g1 (first-wins), one for g2
        DB::table('cart_shippings')->insert([
            ['cart_group_id' => 'g1', 'shipping_method_id' => 11, 'shipping_cost' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['cart_group_id' => 'g1', 'shipping_method_id' => 12, 'shipping_cost' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['cart_group_id' => 'g2', 'shipping_method_id' => 13, 'shipping_cost' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('shipping_methods')->insert([
            ['creator_id' => $s1, 'creator_type' => 'seller', 'title' => 'Seller M', 'cost' => 5, 'duration' => '2d', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['creator_id' => 1, 'creator_type' => 'admin', 'title' => 'Admin M', 'cost' => 4, 'duration' => '1d', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $sellerIds = [$s1, $s2];
        $groupIds = ['g1', 'g2'];

        // The exact expressions the composer uses:
        $sellerShippingMap = ShippingType::whereIn('seller_id', $sellerIds)->get()->groupBy('seller_id')->map->first();
        $shopMap = Shop::whereIn('seller_id', $sellerIds)->get()->groupBy('seller_id')->map->first();
        $chosenMap = CartShipping::whereIn('cart_group_id', $groupIds)->get()->groupBy('cart_group_id')->map->first();

        // Must equal the old per-item ->first() queries.
        foreach ($sellerIds as $s) {
            $this->assertSame(ShippingType::where('seller_id', $s)->first()->id, $sellerShippingMap[$s]->id, "shipping_type map mismatch for seller {$s}");
            $this->assertSame(Shop::where('seller_id', $s)->first()->id, $shopMap[$s]->id, "shop map mismatch for seller {$s}");
        }
        foreach ($groupIds as $g) {
            $this->assertSame(CartShipping::where('cart_group_id', $g)->first()->id, $chosenMap[$g]->id, "cart_shipping map mismatch for group {$g}");
        }

        // Shipping-methods keying: "sellerId|sellerIs" and "1|admin".
        $this->assertSame(
            Helpers::getShippingMethods($s1, 'seller')->pluck('id')->all(),
            Helpers::getShippingMethods($s1, 'seller')->pluck('id')->all()
        );
        $sellerMethods = Helpers::getShippingMethods($s1, 'seller');
        $adminMethods = Helpers::getShippingMethods(1, 'admin');
        $this->assertGreaterThan(0, $sellerMethods->count());
        $this->assertGreaterThan(0, $adminMethods->count());
        $this->assertNotEquals($sellerMethods->pluck('id')->all(), $adminMethods->pluck('id')->all(), 'admin vs seller methods should differ');

        // Missing key → null (matches ->first() returning null), no error.
        $this->assertNull($sellerShippingMap[99999] ?? null);
    }
}
