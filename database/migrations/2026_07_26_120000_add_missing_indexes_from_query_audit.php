<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive indexes derived from docs/QUERY_AUDIT.md.
 *
 * Scope: only tables the audit found under-indexed (PRIMARY-key-only on hot
 * paths). Structural indexes on products/orders/order_details/shops/sellers were
 * already added by the 2026_02_25_* / 2026_05_05_* migrations and are untouched.
 *
 * Rules applied:
 *  - Additive only (no drops/renames/column changes in up()).
 *  - Every unindexed foreign key on these tables gets an index.
 *  - Composite order matches actual query column order (equality → range/sort);
 *    the reasoning is in each comment.
 *  - Low-cardinality columns (status, is_guest, home_status, parent_id,
 *    seen_notification) are never indexed alone — only as composite members.
 *  - `currencies` is intentionally skipped (7 rows: a scan beats an index).
 *
 * Runs AFTER installation/backup/database.sql is imported (the tables live in the
 * dump, not in migrations). No API response shape, model, scope or helper changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // carts — cart widget/list, checkout, shipping calc (every page). QUERY_AUDIT #2.
        Schema::table('carts', function (Blueprint $table) {
            // CartManager groups/filters by cart_group_id (~108 refs); high cardinality.
            $table->index('cart_group_id', 'idx_carts_cart_group_id');
            // Guest + logged-in cart retrieval: where(customer_id, is_guest).
            // customer_id first (high-selectivity equality), is_guest second.
            $table->index(['customer_id', 'is_guest'], 'idx_carts_customer_id_is_guest');
            // FK lookups when a line is updated/removed by product or seller.
            $table->index('product_id', 'idx_carts_product_id');
            $table->index('seller_id', 'idx_carts_seller_id');
        });

        // reviews — product detail + home/product-card rating aggregation. QUERY_AUDIT #3.
        Schema::table('reviews', function (Blueprint $table) {
            // whereIn(product_id)->where(status): product_id first (IN, high
            // selectivity) then status; also serves product_id-only counts.
            $table->index(['product_id', 'status'], 'idx_reviews_product_id_status');
            $table->index('customer_id', 'idx_reviews_customer_id');
            $table->index('order_id', 'idx_reviews_order_id');
            $table->index('delivery_man_id', 'idx_reviews_delivery_man_id');
        });

        // review_replies — seller replies on product detail / review lists. QUERY_AUDIT §5 #13.
        Schema::table('review_replies', function (Blueprint $table) {
            $table->index('review_id', 'idx_review_replies_review_id');
            $table->index('added_by_id', 'idx_review_replies_added_by_id');
        });

        // categories — nav menu / category tree (nearly every request). QUERY_AUDIT #6.
        Schema::table('categories', function (Blueprint $table) {
            // Category::hasMany(parent_id)->orderBy(priority): parent_id equality
            // selects children, priority serves the ORDER BY from the index (no filesort).
            $table->index(['parent_id', 'priority'], 'idx_categories_parent_id_priority');
            // Home category blocks: where(home_status)->orderBy/where(position).
            $table->index(['home_status', 'position'], 'idx_categories_home_status_position');
        });

        // wishlists — "my wishlist" + add/exists check. QUERY_AUDIT #11.
        Schema::table('wishlists', function (Blueprint $table) {
            // where(customer_id)->where(product_id): user scope first, exact-row probe second.
            $table->index(['customer_id', 'product_id'], 'idx_wishlists_customer_id_product_id');
            // Reverse: how many wishlisted a product (product-card counts).
            $table->index('product_id', 'idx_wishlists_product_id');
        });

        // order_transactions — admin/vendor/seller-API earnings dashboards. QUERY_AUDIT #18.
        Schema::table('order_transactions', function (Blueprint $table) {
            // where(seller_is)->where(seller_id)->where(status) + GROUP BY YEAR/MONTH(created_at).
            // seller_id (equality) → status (equality) → created_at (range/group);
            // low-cardinality seller_is omitted (cheap residual filter).
            $table->index(['seller_id', 'status', 'created_at'], 'idx_order_transactions_seller_id_status_created_at');
            $table->index('order_id', 'idx_order_transactions_order_id');
            $table->index('shop_id', 'idx_order_transactions_shop_id');
            $table->index('customer_id', 'idx_order_transactions_customer_id');
        });

        // order_status_histories — order detail status timeline. QUERY_AUDIT §5 #16.
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_status_histories_order_id');
            $table->index('user_id', 'idx_order_status_histories_user_id');
        });

        // flash_deal_products — home flash-deal block + flash deal page. QUERY_AUDIT #19.
        Schema::table('flash_deal_products', function (Blueprint $table) {
            // where(flash_deal_id) then join products.id: flash_deal_id (equality) then product_id (join key).
            $table->index(['flash_deal_id', 'product_id'], 'idx_flash_deal_products_flash_deal_id_product_id');
            // Reverse: is a product in any flash deal (product-card badge/price).
            $table->index('product_id', 'idx_flash_deal_products_product_id');
        });

        // colors — Helpers::set_data_format colour resolution per product. QUERY_AUDIT §5 #19.
        Schema::table('colors', function (Blueprint $table) {
            // whereIn(code); 139 rows, code high-cardinality.
            $table->index('code', 'idx_colors_code');
        });

        // chattings — admin dashboard notification poll + chat threads. QUERY_AUDIT #20.
        Schema::table('chattings', function (Blueprint $table) {
            // where(admin_id)->where(seen_notification): recipient scope then flag.
            $table->index(['admin_id', 'seen_notification'], 'idx_chattings_admin_id_seen_notification');
            $table->index('user_id', 'idx_chattings_user_id');
            $table->index('seller_id', 'idx_chattings_seller_id');
            $table->index('delivery_man_id', 'idx_chattings_delivery_man_id');
            $table->index('shop_id', 'idx_chattings_shop_id');
        });

        // product_tag — tag pivot (product detail tags + tag landing pages). QUERY_AUDIT §5 #25.
        Schema::table('product_tag', function (Blueprint $table) {
            // product_id (usual filter) then tag_id (join key).
            $table->index(['product_id', 'tag_id'], 'idx_product_tag_product_id_tag_id');
            $table->index('tag_id', 'idx_product_tag_tag_id');
        });

        // shop_followers — shop page followers + follow button. QUERY_AUDIT §5.
        Schema::table('shop_followers', function (Blueprint $table) {
            // where(shop_id)->where(user_id): shop scope then follower probe.
            $table->index(['shop_id', 'user_id'], 'idx_shop_followers_shop_id_user_id');
            // Reverse: shops a user follows (user profile).
            $table->index('user_id', 'idx_shop_followers_user_id');
        });

        // product_stocks — variant stock on product detail + cart validation. QUERY_AUDIT §5.
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->index('product_id', 'idx_product_stocks_product_id');
        });

        // deal_of_the_days — "deal of the day" home block. QUERY_AUDIT §5.
        Schema::table('deal_of_the_days', function (Blueprint $table) {
            $table->index('product_id', 'idx_deal_of_the_days_product_id');
        });

        // restock_product_customers — restock subscribers + "my restock requests". QUERY_AUDIT §5.
        Schema::table('restock_product_customers', function (Blueprint $table) {
            $table->index('restock_product_id', 'idx_restock_product_customers_restock_product_id');
            $table->index('customer_id', 'idx_restock_product_customers_customer_id');
        });

        // wallet_transactions — customer wallet history / add-funds ledger. QUERY_AUDIT §5.
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index('user_id', 'idx_wallet_transactions_user_id');
        });

        // users — registration referral-code check + referral tree. QUERY_AUDIT §5 #23.
        Schema::table('users', function (Blueprint $table) {
            // Helpers::generate_referer_code where(referral_code); high cardinality.
            $table->index('referral_code', 'idx_users_referral_code');
            $table->index('referred_by', 'idx_users_referred_by');
        });

        // sellers — seller-app (v3) auth on EVERY request: Helpers::get_seller_by_token
        // where(auth_token). QUERY_AUDIT §5 #21. auth_token is TEXT, so a key-length
        // prefix is required (raw DDL — the schema builder can't express a prefix);
        // 191 chars is selective and within the utf8mb4 key-length limit.
        DB::statement('ALTER TABLE `sellers` ADD INDEX `idx_sellers_auth_token` (`auth_token`(191))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `sellers` DROP INDEX `idx_sellers_auth_token`');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_referral_code');
            $table->dropIndex('idx_users_referred_by');
        });
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_wallet_transactions_user_id');
        });
        Schema::table('restock_product_customers', function (Blueprint $table) {
            $table->dropIndex('idx_restock_product_customers_restock_product_id');
            $table->dropIndex('idx_restock_product_customers_customer_id');
        });
        Schema::table('deal_of_the_days', function (Blueprint $table) {
            $table->dropIndex('idx_deal_of_the_days_product_id');
        });
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndex('idx_product_stocks_product_id');
        });
        Schema::table('shop_followers', function (Blueprint $table) {
            $table->dropIndex('idx_shop_followers_shop_id_user_id');
            $table->dropIndex('idx_shop_followers_user_id');
        });
        Schema::table('product_tag', function (Blueprint $table) {
            $table->dropIndex('idx_product_tag_product_id_tag_id');
            $table->dropIndex('idx_product_tag_tag_id');
        });
        Schema::table('chattings', function (Blueprint $table) {
            $table->dropIndex('idx_chattings_admin_id_seen_notification');
            $table->dropIndex('idx_chattings_user_id');
            $table->dropIndex('idx_chattings_seller_id');
            $table->dropIndex('idx_chattings_delivery_man_id');
            $table->dropIndex('idx_chattings_shop_id');
        });
        Schema::table('colors', function (Blueprint $table) {
            $table->dropIndex('idx_colors_code');
        });
        Schema::table('flash_deal_products', function (Blueprint $table) {
            $table->dropIndex('idx_flash_deal_products_flash_deal_id_product_id');
            $table->dropIndex('idx_flash_deal_products_product_id');
        });
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropIndex('idx_order_status_histories_order_id');
            $table->dropIndex('idx_order_status_histories_user_id');
        });
        Schema::table('order_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_order_transactions_seller_id_status_created_at');
            $table->dropIndex('idx_order_transactions_order_id');
            $table->dropIndex('idx_order_transactions_shop_id');
            $table->dropIndex('idx_order_transactions_customer_id');
        });
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropIndex('idx_wishlists_customer_id_product_id');
            $table->dropIndex('idx_wishlists_product_id');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_parent_id_priority');
            $table->dropIndex('idx_categories_home_status_position');
        });
        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropIndex('idx_review_replies_review_id');
            $table->dropIndex('idx_review_replies_added_by_id');
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_product_id_status');
            $table->dropIndex('idx_reviews_customer_id');
            $table->dropIndex('idx_reviews_order_id');
            $table->dropIndex('idx_reviews_delivery_man_id');
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_cart_group_id');
            $table->dropIndex('idx_carts_customer_id_is_guest');
            $table->dropIndex('idx_carts_product_id');
            $table->dropIndex('idx_carts_seller_id');
        });
    }
};
