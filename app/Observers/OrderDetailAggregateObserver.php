<?php

namespace App\Observers;

use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

/**
 * Keeps products.order_count ("units sold" = delivered order lines) in sync as
 * order_details reach or leave the 'delivered' state. Increments only on the
 * transition INTO delivered and decrements on the transition OUT, so repeated
 * saves never double-count. The nightly reconcile command is the drift safety net.
 */
class OrderDetailAggregateObserver
{
    public function created(OrderDetail $detail): void
    {
        if ($detail->delivery_status === 'delivered') {
            $this->adjust($detail->product_id, 1);
        }
    }

    public function updated(OrderDetail $detail): void
    {
        if (! $detail->isDirty('delivery_status')) {
            return;
        }
        $was = $detail->getOriginal('delivery_status');
        $now = $detail->delivery_status;

        if ($now === 'delivered' && $was !== 'delivered') {
            $this->adjust($detail->product_id, 1);
        } elseif ($was === 'delivered' && $now !== 'delivered') {
            $this->adjust($detail->product_id, -1);
        }
    }

    public function deleted(OrderDetail $detail): void
    {
        if ($detail->delivery_status === 'delivered') {
            $this->adjust($detail->product_id, -1);
        }
    }

    private function adjust(int|string|null $productId, int $by): void
    {
        $productId = (int) $productId;
        if ($productId === 0) {
            return;
        }
        if ($by > 0) {
            DB::table('products')->where('id', $productId)->increment('order_count', $by);
        } else {
            // Guard against going negative if events were missed.
            DB::table('products')->where('id', $productId)->where('order_count', '>', 0)->decrement('order_count', abs($by));
        }
    }
}
