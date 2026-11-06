<?php

namespace App\Enums\ExportFileNames\Admin;

enum Report
{
    const ADMIN_EARNING_REPORT = 'admin-earning-report.xlsx';
    const VENDOR_REPORT = 'vendor-report.xlsx';
    const VENDOR_EARNING_REPORT = 'vendor-earning-report.xlsx';
    const INHOUSE_PRODUCT_SALE_REPORT = 'inhouse-product-sale-report.xlsx';
    const ORDER_REPORT_LIST = 'Order-Report-List.xlsx';
    const ORDER_TRANSACTION_REPORT_LIST = 'Order-Transaction-Report-List.xlsx';
    const EXPENSE_TRANSACTION_REPORT_LIST = 'Expense-Transaction-Report-List.xlsx';
    const REFUND_TRANSACTION_REPORT_LIST = 'Refund-Transaction-Report-List.xlsx';
    const PRODUCT_REPORT_LIST = 'Product-Report-List.xlsx';
    const PRODUCT_STOCK_REPORT = 'Product-stock-report.xlsx';
    const PRODUCT_WISHLISTED_REPORT = 'Product-wishlisted-report.xlsx';
}
