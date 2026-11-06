<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

/**
 * Keeps products.rating_avg / rating_count in sync on review writes. Recomputes
 * from source (AVG cannot be safely incremented) via a plain DB update — no model
 * events, so no observer recursion. The nightly reconcile command corrects any
 * drift from writes that bypass Eloquent events.
 */
class ReviewAggregateObserver
{
    public function created(Review $review): void
    {
        $this->recompute($review->product_id);
    }

    public function updated(Review $review): void
    {
        $this->recompute($review->product_id);
        if ($review->isDirty('product_id')) {
            $this->recompute($review->getOriginal('product_id'));
        }
    }

    public function deleted(Review $review): void
    {
        $this->recompute($review->product_id);
    }

    private function recompute(int|string|null $productId): void
    {
        $productId = (int) $productId;
        if ($productId === 0) {
            return;
        }
        DB::table('products')->where('id', $productId)->update([
            'rating_avg' => DB::raw("(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = {$productId})"),
            'rating_count' => DB::raw("(SELECT COUNT(*) FROM reviews WHERE product_id = {$productId})"),
        ]);
    }
}
