<?php

return [
    [
        'name' => 'Admin Tax Report',
        'url' => route(reportModuleRoute('get-tax-report')),
        'path' => 'get-tax-report',
        'sub_routes' => [
            [
                'name' => 'Tax Report Details',
                'url' => route(reportModuleRoute('get-tax-details')),
                'path' => 'get-tax-details',
            ],
        ],
    ],
    [
        'name' => 'Vendor Vat Report',
        'url' => route(reportModuleRoute('vendor-wise-taxes')),
        'path' => 'vendor-wise-taxes',
        'sub_routes' => [
            [
                'name' => 'Vendor Tax Details',
                'url' => route(reportModuleRoute('vendor-tax')),
                'path' => 'vendor-tax-report',
            ],
        ],
    ],
];
