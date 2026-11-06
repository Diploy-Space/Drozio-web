<?php

namespace Tests\Feature\Contract\Support;

use App\Models\Shop;
use Illuminate\Support\Facades\DB;

/**
 * Minimal catalog fixtures for home/listing contract tests.
 *
 * Inserts active, admin-owned, featured products (each with reviews) directly so
 * they surface across the home page's product sections. Direct inserts avoid
 * model observers/slug side effects; rows roll back via the per-test transaction.
 */
trait SeedsCatalog
{
    private static int $catalogBatch = 0;

    protected ?int $lastCustomerId = null;

    /**
     * An approved seller with a shop and N active seller-owned products (each with
     * reviews). Used to exercise product-detail related/more-from-seller cards that
     * read $product->seller->shop.
     */
    /**
     * The retail half of a seeded product. The twelve retail-only columns live on
     * products_retail since the 16.6 split, so a fixture that writes them onto products
     * fails with an unknown-column error.
     */
    private function seedProductRetail(int $productId, ?int $featured = null): void
    {
        DB::table('products_retail')->insert([
            'product_id' => $productId,
            'purchase_price' => 90,
            'variation' => json_encode([]),
            'color_image' => json_encode([]),
            'featured' => $featured,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function seedSellerCatalog(int $products = 6, int $reviewsPerProduct = 2): array
    {
        $batch = ++self::$catalogBatch;

        $sellerId = DB::table('sellers')->insertGetId([
            'f_name' => 'Contract', 'l_name' => 'Seller',
            'email' => "contract.seller.{$batch}@example.test",
            'password' => bcrypt('password'),
            'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('shops')->insert([
            'seller_id' => $sellerId,
            'name' => "Contract Seller Shop {$batch}",
            'slug' => "contract-seller-shop-{$batch}",
            'address' => 'Addr', 'contact' => '+10000000000', 'banner' => 'b.png',
            'author_type' => 'seller',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $customerId = DB::table('users')->insertGetId([
            'name' => 'Buyer', 'f_name' => 'Buy', 'l_name' => 'Er',
            'email' => "contract.buyer.{$batch}@example.test",
            'password' => bcrypt('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $slugs = [];
        for ($i = 1; $i <= $products; $i++) {
            $slug = "seller-product-b{$batch}-{$i}";
            $productId = DB::table('products')->insertGetId([
                'name' => "Seller Product B{$batch}-{$i}",
                'slug' => $slug,
                'added_by' => 'seller', 'user_id' => $sellerId,
                'product_type' => 'physical', 'brand_id' => null,
                'category_ids' => json_encode([['id' => '1', 'position' => 1]]),
                'unit_price' => 100 + $i, 'current_stock' => 50,
                'choice_options' => json_encode([]),
                'discount' => 0, 'discount_type' => 'flat',
                'tax' => 0, 'tax_type' => 'percent', 'tax_model' => 'exclude',
                'status' => 1, 'request_status' => 1,
                'thumbnail' => 'def.png',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->seedProductRetail(productId: $productId);
            $slugs[] = $slug;
            for ($r = 1; $r <= $reviewsPerProduct; $r++) {
                DB::table('reviews')->insert([
                    'product_id' => $productId, 'customer_id' => $customerId,
                    'rating' => ($r % 5) + 1, 'comment' => 'c', 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        return ['sellerId' => $sellerId, 'customerId' => $customerId, 'slugs' => $slugs];
    }

    /**
     * A customer with N compared + restock-requested products, each having a brand,
     * category and reviews — so the compare/restock views' per-row relation reads
     * (product.reviews / brand / category) are exercised.
     */
    protected function seedCompareAndRestock(int $items = 6): array
    {
        $batch = ++self::$catalogBatch;

        $brandId = DB::table('brands')->insertGetId([
            'name' => "Brand {$batch}", 'slug' => "brand-{$batch}", 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => "Cat {$batch}", 'slug' => "cat-{$batch}", 'parent_id' => 0, 'position' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $customerId = DB::table('users')->insertGetId([
            'name' => 'Cmp', 'f_name' => 'Cmp', 'l_name' => 'Buyer',
            'email' => "contract.cmp.{$batch}@example.test", 'password' => bcrypt('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        for ($i = 1; $i <= $items; $i++) {
            $productId = DB::table('products')->insertGetId([
                'name' => "Cmp Product B{$batch}-{$i}", 'slug' => "cmp-product-b{$batch}-{$i}",
                'added_by' => 'admin', 'user_id' => 1, 'product_type' => 'physical',
                'brand_id' => $brandId, 'category_id' => $categoryId,
                'unit_price' => 100 + $i, 'current_stock' => 50,
                'discount' => 0, 'discount_type' => 'flat',
                'status' => 1, 'request_status' => 1,
                'thumbnail' => 'def.png',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->seedProductRetail(productId: $productId);
            DB::table('reviews')->insert([
                'product_id' => $productId, 'customer_id' => $customerId,
                'rating' => 5, 'comment' => 'c', 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('product_compares')->insert([
                'user_id' => $customerId, 'product_id' => $productId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $restockId = DB::table('restock_products')->insertGetId([
                'product_id' => $productId, 'variant' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('restock_product_customers')->insert([
                'restock_product_id' => $restockId, 'customer_id' => $customerId,
                'variant' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return ['customerId' => $customerId];
    }

    /** A customer order with one order_detail per product id (plus a matching review). */
    protected function seedOrderWithDetails(int $customerId, array $productIds): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'customer_id' => $customerId, 'is_guest' => 0,
            'order_amount' => 100, 'order_status' => 'pending', 'payment_status' => 'unpaid',
            'seller_is' => 'admin', 'order_type' => 'default_type',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($productIds as $pid) {
            DB::table('order_details')->insert([
                'order_id' => $orderId,
                'product_id' => $pid,
                'seller_id' => 0,
                'product_details' => json_encode([
                    'id' => $pid, 'name' => "P{$pid}", 'product_type' => 'physical',
                    'added_by' => 'admin', 'unit_price' => 100, 'tax' => 0,
                    'discount' => 0, 'discount_type' => 'flat', 'thumbnail' => 'def.png',
                    'images' => [], 'color_image' => json_encode([]),
                    'choice_options' => json_encode([]), 'variation' => json_encode([]),
                ]),
                'variation' => json_encode([]), 'qty' => 1, 'price' => 100,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('reviews')->insert([
                'product_id' => $pid, 'customer_id' => $customerId, 'order_id' => $orderId,
                'rating' => 5, 'comment' => 'c', 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $orderId;
    }

    /**
     * N approved sellers, each with one delivered order in the given window.
     *
     * Defaults to now() rather than a fixed past date: the report this feeds sorts by
     * created_at desc and reads the first-encountered seller, so a stale default would
     * eventually sort behind newer orders in the shared baseline dump and silently break.
     */
    protected function seedSellerEarnings(int $sellers, ?string $createdAt = null): void
    {
        $createdAt ??= now()->toDateTimeString();

        for ($i = 1; $i <= $sellers; $i++) {
            $batch = ++self::$catalogBatch;
            $sellerId = DB::table('sellers')->insertGetId([
                'f_name' => "Vend{$batch}", 'l_name' => "Or{$batch}",
                'email' => "contract.vendor.{$batch}@example.test",
                'password' => bcrypt('password'), 'status' => 'approved',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('orders')->insert([
                'customer_id' => 1, 'seller_id' => $sellerId, 'seller_is' => 'seller',
                'order_status' => 'delivered', 'order_type' => 'default_type',
                'order_amount' => 100, 'admin_commission' => 10, 'shipping_cost' => 5,
                'total_tax_amount' => 0, 'refer_and_earn_discount' => 0, 'discount_amount' => 0,
                'extra_discount' => 0, 'is_shipping_free' => 0,
                'shipping_responsibility' => 'sellerwise_shipping',
                'coupon_discount_bearer' => 'inhouse', 'discount_type' => 'coupon_discount',
                'free_delivery_bearer' => 'admin',
                'created_at' => $createdAt, 'updated_at' => $createdAt,
            ]);
        }
    }

    /** An admin-owned shop whose product list resolves to seedCatalog()'s admin products. */
    protected function seedAdminShop(): object
    {
        $shopId = DB::table('shops')->insertGetId([
            'seller_id' => 0,
            'name' => 'Contract Shop',
            'slug' => 'contract-shop-'.(self::$catalogBatch + 1),
            'address' => 'Test Address',
            'contact' => '+10000000000',
            'banner' => 'banner.png',
            'author_type' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Shop::find($shopId);
    }

    protected function seedCatalog(int $products = 5, int $reviewsPerProduct = 3): array
    {
        $batch = ++self::$catalogBatch;

        $customerId = DB::table('users')->insertGetId([
            'name' => 'Contract Customer',
            'f_name' => 'Contract',
            'l_name' => 'Customer',
            'email' => "contract.customer.{$batch}@example.test",
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ids = [];
        for ($i = 1; $i <= $products; $i++) {
            $productId = DB::table('products')->insertGetId([
                'name' => "Contract Product B{$batch}-{$i}",
                'slug' => "contract-product-b{$batch}-{$i}",
                'added_by' => 'admin',
                'user_id' => 1,
                'product_type' => 'physical',
                'brand_id' => null,
                'unit_price' => 100 + $i,
                'current_stock' => 50,
                'status' => 1,
                'request_status' => 1,
                'featured_status' => 1,
                'thumbnail' => 'def.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->seedProductRetail(productId: $productId, featured: 1);
            $ids[] = $productId;

            $this->lastCustomerId = $customerId;
            for ($r = 1; $r <= $reviewsPerProduct; $r++) {
                DB::table('reviews')->insert([
                    'product_id' => $productId,
                    'customer_id' => $customerId,
                    'rating' => ($r % 5) + 1,
                    'comment' => "Review {$r} for product {$i}",
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $ids;
    }
}
