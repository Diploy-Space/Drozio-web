<?php

namespace App\Enums;

/**
 * The product-list rails `ProductService::getList()` serves, one key per way a caller asks for a page
 * of products — so the single, perspective-agnostic list method (reused by API, Web and the admin /
 * vendor panels) branches on a named constant rather than a magic string.
 *
 * Storefront rails are active-scoped and read the customer identity; the management rails are
 * filter-scoped (added_by / user_id / status …) and serve the admin & vendor product screens.
 */
enum ProductListType
{
    // Storefront rails (active scope, customer identity)
    const LATEST = 'latest';

    const NEW_ARRIVAL = 'new_arrival';

    const FEATURED = 'featured';

    const TOP_RATED = 'top_rated';

    const BEST_SELLING = 'best_selling';

    const MOST_SEARCHING = 'most_searching';

    const DISCOUNTED = 'discounted';

    const CLEARANCE_SALE = 'clearance_sale';

    const FEATURED_DEAL = 'featured_deal';

    const SHOP_AGAIN = 'shop_again';

    // Management rails (filter-scoped: admin / vendor product screens)
    const MANAGED = 'managed';

    const STOCK_OUT = 'stock_out';

    const TOP_SELLING = 'top_selling';

    const MOST_POPULAR = 'most_popular';
}
