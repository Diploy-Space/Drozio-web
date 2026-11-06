<?php

return [
    [
        'name' => 'VAT Report',
        'url' => route(reportModuleRoute('get-vat-report')),
        'path' => 'get-vat-report',
        'sub_routes' => [
            [
                'name' => 'Vat Report Details',
                'url' => route(reportModuleRoute('get-vat-report-export')),
                'path' => 'get-vat-report-export',
            ],
        ],
    ],
];
