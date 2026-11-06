<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\ExpenseTransactionReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\ExpenseTransaction\FilterRequest;
use App\Http\Requests\Shared\Report\ExpenseTransaction\OrderPdfRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ExpenseTransactionReportService;
use App\Services\New\VendorService;
use App\Utils\Helpers;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseTransactionController extends Controller
{
    private const COMPANY_SETTING_TYPES = ['company_name', 'company_email', 'company_phone'];

    public function __construct(
        private readonly ExpenseTransactionReportService $expenseTransactionReportService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();

        return view('vendor-views.transaction.expense-list', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'search' => $request['search'],
            'expenseTransactions' => $this->expenseTransactionReportService->getVendorExpenseTransactionList(
                sellerId: $this->vendorId(),
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'expenseSummary' => $this->expenseTransactionReportService->getVendorExpenseSummary(sellerId: $this->vendorId(), dateType: $dateType, from: $request['from'], to: $request['to']),
            'expenseChart' => $this->expenseTransactionReportService->getVendorExpenseChart(sellerId: $this->vendorId(), dateType: $dateType, from: $request['from'], to: $request['to']),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();

        return Excel::download(new ExpenseTransactionReportExport([
            'data-from' => 'vendor',
            'vendor' => $this->vendorService->find(id: $this->vendorId(), relations: ['shop']),
            'search' => $request['search'],
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'transactions' => $this->expenseTransactionReportService->getVendorExpenseExportList(
                sellerId: $this->vendorId(),
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                searchValue: $request['search'],
            ),
        ]), Report::EXPENSE_TRANSACTION_REPORT_LIST);
    }

    public function downloadSummaryPdf(FilterRequest $request): void
    {
        $dateType = $request->dateType();
        $totals = $this->expenseTransactionReportService->getVendorExpenseSummaryPdfTotals(
            sellerId: $this->vendorId(),
            dateType: $dateType,
            from: $request['from'],
            to: $request['to'],
            searchValue: $request['search'],
        );
        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('vendor-views.transaction.expense_transaction_summary_report_pdf', [
            'data' => [
                'total_expense' => $totals['total_expense'],
                'free_delivery' => $totals['total_free_delivery'],
                'coupon_discount' => $totals['total_coupon_discount'],
                'free_over_amount_discount' => $totals['total_free_delivery_over_amount'],
                'company_phone' => $companySettings['company_phone'] ?? null,
                'company_name' => $companySettings['company_name'] ?? null,
                'company_email' => $companySettings['company_email'] ?? null,
                'duration' => $dateType === ExpenseTransactionReportService::DATE_TYPE_CUSTOM ? 'From '.$request['from'].' To '.$request['to'] : str_replace('_', ' ', $dateType),
                'shop_name' => $this->vendorService->find(id: $this->vendorId(), relations: ['shop'])?->shop?->name ?? '',
            ],
        ]), 'expense_transaction_summary_report_', $dateType);
    }

    public function downloadOrderPdf(OrderPdfRequest $request): void
    {
        $transaction = $this->expenseTransactionReportService->findVendorExpenseTransaction(orderId: (int) $request['id'], sellerId: $this->vendorId());

        abort_if($transaction === null, 404);

        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('vendor-views.transaction.order_wise_expense_pdf', [
            'transaction' => $transaction,
            'company_phone' => $companySettings['company_phone'] ?? null,
            'company_name' => $companySettings['company_name'] ?? null,
            'company_email' => $companySettings['company_email'] ?? null,
        ]), 'order_expense_transaction_', $request['id']);
    }

    private function vendorId(): int
    {
        return (int) auth('seller')->id();
    }
}
