<?php

return [
    [
        'name' => 'Create Vat/Tax',
        'url' => route('admin.settings.vat-tax.tax-list-retail'),
        'path' => 'admin/settings/vat-tax/retail',
        'active_patterns' => [
            'admin/settings/vat-tax/retail',
            'admin/settings/vat-tax/wholesale',
            'admin/settings/vat-tax/auction',
        ],
    ],
    [
        'name' => 'Setup Vat/Tax',
        'url' => route('admin.settings.vat-tax.system-vat-tax-retail'),
        'path' => 'admin/settings/vat-tax/system-vat-tax',
    ],
];
