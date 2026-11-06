<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Backfill products.rating_avg / rating_count / order_count from source
 * (reviews, delivered order_details). Chunked, resumable, with progress + a
 * post-run verification. Safe to run repeatedly; does not change response shape.
 *
 *   php artisan products:backfill-aggregates            # resume from last cursor
 *   php artisan products:backfill-aggregates --fresh    # restart from the beginning
 *   php artisan products:backfill-aggregates --chunk=1000
 */
class BackfillProductAggregates extends Command
{
    protected $signature = 'products:backfill-aggregates {--fresh : Ignore the saved cursor and restart} {--chunk=500 : Rows per batch}';

    protected $description = 'Backfill denormalized product aggregate columns (rating_avg, rating_count, order_count) from source, resumably';

    private const CURSOR_KEY = 'products:backfill-aggregates:cursor';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));

        if ($this->option('fresh')) {
            Cache::forget(self::CURSOR_KEY);
            $this->info('Cursor reset — starting from the beginning.');
        }

        $cursor = (int) Cache::get(self::CURSOR_KEY, 0);
        $total = Product::where('id', '>', $cursor)->count();

        if ($total === 0) {
            $this->info('Nothing to backfill (cursor at ' . $cursor . ').');
            return $this->verify();
        }

        $this->info("Backfilling {$total} products from id > {$cursor} in chunks of {$chunk}…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        while (true) {
            $ids = Product::where('id', '>', $cursor)->orderBy('id')->limit($chunk)->pluck('id')->all();
            if (empty($ids)) {
                break;
            }

            $this->backfillChunk($ids);

            $cursor = end($ids);
            Cache::forever(self::CURSOR_KEY, $cursor);   // survive interruption → resume here
            $processed += count($ids);
            $bar->advance(count($ids));
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfilled {$processed} products. Cursor at {$cursor}.");

        return $this->verify();
    }

    /**
     * One indexed UPDATE…JOIN per chunk — no N+1, no per-row model hydration.
     * rating_* from all reviews (matches withAvg/withCount); order_count from
     * DELIVERED order_details ("units sold").
     */
    private function backfillChunk(array $ids): void
    {
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
    }

    /** Sample-verify the columns match source, so the caller knows it is safe to switch. */
    private function verify(): int
    {
        $sample = Product::inRandomOrder()->limit(25)->pluck('id')->all();
        $mismatch = 0;
        foreach ($sample as $id) {
            $src = DB::selectOne(
                'SELECT
                    (SELECT COALESCE(AVG(rating),0) FROM reviews WHERE product_id=?) rating_avg,
                    (SELECT COUNT(*) FROM reviews WHERE product_id=?) rating_count,
                    (SELECT COUNT(*) FROM order_details WHERE delivery_status="delivered" AND product_id=?) order_count',
                [$id, $id, $id]
            );
            $row = DB::table('products')->where('id', $id)->first(['rating_avg', 'rating_count', 'order_count']);
            if (round((float) $row->rating_avg, 2) !== round((float) $src->rating_avg, 2)
                || (int) $row->rating_count !== (int) $src->rating_count
                || (int) $row->order_count !== (int) $src->order_count) {
                $mismatch++;
            }
        }

        if ($mismatch === 0) {
            $this->info('Verification passed on ' . count($sample) . ' sampled products.');
            $this->line('Safe to switch: set PRODUCT_SORT_SOURCE=denormalized');
            return self::SUCCESS;
        }

        $this->error("Verification found {$mismatch}/" . count($sample) . ' mismatches — do NOT switch yet; investigate / re-run --fresh.');
        return self::FAILURE;
    }
}
