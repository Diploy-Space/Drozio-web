<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product search driver
    |--------------------------------------------------------------------------
    |
    | Controls how product-name search filters rows:
    |
    |   'like'     — legacy LIKE '%term%' (substring match). Cannot use any index;
    |                full table scan. This is the DEFAULT so behaviour is unchanged
    |                until you opt in, and flipping back is instant.
    |   'fulltext' — MySQL FULLTEXT MATCH(name) AGAINST(term IN NATURAL LANGUAGE MODE).
    |                Uses the `ft_products_name` index (see the
    |                add_fulltext_index_to_products_table migration). Token-based, so
    |                terms shorter than innodb_ft_min_token_size (default 3) and
    |                stopwords match nothing — see docs/SEARCH_FULLTEXT.md.
    |
    | Result ORDER and the JSON response shape are identical under both drivers; only
    | the WHERE match predicate changes. Set SEARCH_DRIVER=fulltext in .env to enable.
    |
    */

    'driver' => env('SEARCH_DRIVER', 'like'),

];
