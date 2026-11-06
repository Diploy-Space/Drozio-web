<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product listing sort source
    |--------------------------------------------------------------------------
    |
    | Where rating / review-count / order-count sorts read their value from:
    |
    |   'computed'     — the legacy withCount()/withAvg() subquery aliases
    |                    (reviews_avg_rating, reviews_count, order_details_count).
    |                    Correct, but every listing does "Using temporary; Using
    |                    filesort" over a per-row aggregate. This is the DEFAULT.
    |   'denormalized' — the indexed products.rating_avg / rating_count / order_count
    |                    columns. No subquery, no filesort — the sort uses an index.
    |
    | Switch to 'denormalized' via PRODUCT_SORT_SOURCE=denormalized ONLY AFTER
    | `php artisan products:backfill-aggregates` has populated and verified the
    | columns. Flip back instantly to revert. Response shape is identical either way
    | (the columns are hidden; only the ORDER BY target changes).
    |
    */

    'sort_source' => env('PRODUCT_SORT_SOURCE', 'computed'),

];
