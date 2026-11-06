<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\ExpenseTransactionReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\ExpenseTransaction\FilterRequest;
use App\Http\Requests\Shared\Report\ExpenseTransaction\OrderPdfRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ExpenseTransactionReportService;
use App\Utils\Helpers;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseTransactionController extends Controller
{
    private const COMPANY_SETTING_TYPES = ['company_name', 'company_email', 'company_phone'];

    private const PAGE_SIZE = 20;

    public function __construct(
        private readonly ExpenseTransactionReportService $expenseTransactionReportService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();

        return view('admin-views.transaction.expense-list', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'search' => $request['search'],
            'expenseTransactions' => $this->expenseTransactionReportService->getExpenseTransactionList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                searchValue: $request['search'],
                dataLimit: self::PAGE_SIZE,
                page: $request['page'],
            )->withQueryString(),
            'expenseSummary' => $this->expenseTransactionReportService->getExpenseSummary(dateType: $dateType, from: $request['from'], to: $request['to'], searchValue: $request['search']),
            'expenseChart' => $this->expenseTransactionReportService->getExpenseChart(dateType: $dateType, from: $request['from'], to: $request['to'], searchValue: $request['search']),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();

        return Excel::download(new ExpenseTransactionReportExport([
            'search' => $request['search'],
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'transactions' => $this->expenseTransactionReportService->getExpenseTransactionList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
        ]), Report::EXPENSE_TRANSACTION_REPORT_LIST);
    }

    public function downloadSummaryPdf(FilterRequest $request): void
    {
        $dateType = $request->dateType();
        $summary = $this->expenseTransactionReportService->getExpenseSummary(dateType: $dateType, from: $request['from'], to: $request['to'], searchValue: $request['search']);
        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('admin-views.transaction.expense_transaction_summary_report_pdf', [
            'data' => [
                'total_expense' => $summary['total_expense'],
                'free_delivery' => $summary['total_free_delivery'],
                'coupon_discount' => $summary['total_coupon_discount'],
                'referral_Discount' => $summary['total_referral_discount'],
                'free_over_amount_discount' => $summary['total_free_delivery_over_amount'],
                'company_phone' => $companySettings['company_phone'] ?? null,
                'company_name' => $companySettings['company_name'] ?? null,
                'company_email' => $companySettings['company_email'] ?? null,
                'duration' => $dateType === ExpenseTransactionReportService::DATE_TYPE_CUSTOM ? 'From '.$request['from'].' To '.$request['to'] : str_replace('_', ' ', $dateType),
            ],
        ]), 'expense_transaction_summary_report_', $dateType);
    }

    public function downloadOrderPdf(OrderPdfRequest $request): void
    {
        $transaction = $this->expenseTransactionReportService->findExpenseTransaction(orderId: (int) $request['id']);

        abort_if($transaction === null, 404);

        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('admin-views.transaction.order_wise_expense_pdf', [
            'transaction' => $transaction,
            'company_phone' => $companySettings['company_phone'] ?? null,
            'company_name' => $companySettings['company_name'] ?? null,
            'company_email' => $companySettings['company_email'] ?? null,
        ]), 'expense_transaction_', $request['id']);
    }
}
