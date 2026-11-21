<?php

// Reuses existing shops, products, categories and customer shipping addresses.
return [
    'enabled_shop_ids' => array_values(array_filter(array_map('intval',
        explode(',', (string) env('QUICK_ENABLED_SHOP_IDS', '')),
    ))),
    'default_radius_km' => (float) env('QUICK_DEFAULT_RADIUS_KM', 5),
    'max_candidate_stores' => max(1, (int) env('QUICK_MAX_CANDIDATE_STORES', 100)),
    'max_product_ids' => max(1, (int) env('QUICK_MAX_PRODUCT_IDS', 10000)),
    'geocoding' => [
        'driver' => env('QUICK_GEOCODING_DRIVER', 'photon'),
        'endpoint' => env('QUICK_GEOCODING_ENDPOINT', 'https://photon.komoot.io/api/'),
        'country_code' => env('QUICK_GEOCODING_COUNTRY_CODE', 'BD'),
        'address_suffix' => env('QUICK_GEOCODING_ADDRESS_SUFFIX', 'Bangladesh'),
        'user_agent' => env('QUICK_GEOCODING_USER_AGENT', 'DROJIO-Quick/1.0'),
    ],
];
