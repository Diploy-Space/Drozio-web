<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Authoritative safety net: recompute products.rating_avg / rating_count /
 * order_count from source for ALL products, correcting any drift from missed
 * observer events (bulk imports, raw writes, cascade deletes). Intended to run
 * nightly (scheduled in routes/console.php). Chunked; does not change response shape.
 *
 *   php artisan products:reconcile-aggregates
 */
class ReconcileProductAggregates extends Command
{
    protected $signature = 'products:reconcile-aggregates {--chunk=500 : Rows per batch}';

    protected $description = 'Recompute all denormalized product aggregate columns from source (drift correction)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $updated = 0;

        Product::orderBy('id')->chunkById($chunk, function ($products) use (&$updated) {
            $ids = $products->pluck('id')->all();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            DB::statement(
                "UPDATE products p
                 LEFT JOIN (
                     SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS cnt
                     FROM reviews WHERE product_id IN ({$placeholders}) GROUP BY product_id
                 ) r ON r.product_id = p.id
                 LEFT JOIN (
                     SELECT product_id, COUNT(*) AS order_cnt
                     FROM order_details
                     WHERE delivery_status = 'delivered' AND product_id IN ({$placeholders}) GROUP BY product_id
                 ) o ON o.product_id = p.id
                 SET p.rating_avg = COALESCE(r.avg_rating, 0),
                     p.rating_count = COALESCE(r.cnt, 0),
                     p.order_count = COALESCE(o.order_cnt, 0)
                 WHERE p.id IN ({$placeholders})",
                array_merge($ids, $ids, $ids)
            );
            $updated += count($ids);
        });

        $this->info("Reconciled {$updated} products.");
        return self::SUCCESS;
    }
}
