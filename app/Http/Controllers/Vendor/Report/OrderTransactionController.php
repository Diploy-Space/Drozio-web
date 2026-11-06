<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\OrderTransactionReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\OrderTransaction\FilterRequest;
use App\Http\Requests\Shared\Report\OrderTransaction\OrderPdfRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\OrderTransactionReportService;
use App\Services\New\VendorService;
use App\Utils\Helpers;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderTransactionController extends Controller
{
    private const COMPANY_SETTING_TYPES = ['company_name', 'company_email', 'company_phone'];

    private const OPTION_ALL = 'all';

    public function __construct(
        private readonly OrderTransactionReportService $orderTransactionReportService,
        private readonly VendorService $vendorService,
        private readonly CustomerService $customerService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $filters = $this->vendorFilters(request: $request);

        return view('vendor-views.transaction.order-list', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'status' => $filters[OrderTransactionReportService::FILTER_STATUS] ?? self::OPTION_ALL,
            'customerId' => $filters[OrderTransactionReportService::FILTER_CUSTOMER_ID] ?? self::OPTION_ALL,
            'search' => $request['search'],
            'customers' => $this->customerService->getList(dataLimit: 'all', filters: [CustomerService::FILTER_EXCLUDE_IDS => [0]]),
            'transactionRows' => $this->orderTransactionReportService->getTransactionList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                filters: $filters,
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'productCounts' => $this->orderTransactionReportService->getVendorProductCounts(sellerId: $this->vendorId()),
            'paymentStatistics' => $this->orderTransactionReportService->getPaymentStatistics(dateType: $dateType, from: $request['from'], to: $request['to'], filters: $filters),
            'orderAmountChart' => $this->orderTransactionReportService->getVendorOrderAmountChart(dateType: $dateType, from: $request['from'], to: $request['to'], filters: $filters),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $filters = $this->vendorFilters(request: $request);
        $transactionRows = $this->orderTransactionReportService->getVendorTransactionExportList(
            dateType: $dateType,
            from: $request['from'],
            to: $request['to'],
            filters: $filters,
            searchValue: $request['search'],
        );

        return Excel::download(new OrderTransactionReportExport([
            'data-from' => 'vendor',
            'search' => $request['search'],
            'status' => $filters[OrderTransactionReportService::FILTER_STATUS] ?? null,
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'vendor' => $this->vendorService->find(id: $this->vendorId(), relations: ['shop']),
            'customer' => isset($filters[OrderTransactionReportService::FILTER_CUSTOMER_ID])
                ? ($this->customerService->find(id: $filters[OrderTransactionReportService::FILTER_CUSTOMER_ID]) ?? self::OPTION_ALL)
                : self::OPTION_ALL,
            'transactions' => $transactionRows->values()->keyBy(fn (array $row, int $index): int => $index + 1),
        ]), Report::ORDER_TRANSACTION_REPORT_LIST);
    }

    public function downloadSummaryPdf(FilterRequest $request): void
    {
        $dateType = $request->dateType();
        $filters = $this->vendorFilters(request: $request);
        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('vendor-views.transaction.order_transaction_summary_report_pdf', [
            'data' => $this->orderTransactionReportService->getVendorSummaryPdfTotals(dateType: $dateType, from: $request['from'], to: $request['to'], filters: $filters, searchValue: $request['search']),
            'company_phone' => $companySettings['company_phone'] ?? null,
            'company_name' => $companySettings['company_name'] ?? null,
            'company_email' => $companySettings['company_email'] ?? null,
            'status' => $filters[OrderTransactionReportService::FILTER_STATUS] ?? self::OPTION_ALL,
            'duration' => $dateType === OrderTransactionReportService::DATE_TYPE_CUSTOM ? 'From '.$request['from'].' To '.$request['to'] : str_replace('_', ' ', $dateType),
            'seller_info' => $this->vendorService->find(id: $this->vendorId(), relations: ['shop'])?->shop?->name ?? '',
            'customer_info' => $this->customerInfo(customerId: $filters[OrderTransactionReportService::FILTER_CUSTOMER_ID] ?? null),
        ]), 'order_transaction_summary_report_', $dateType);
    }

    public function downloadOrderPdf(OrderPdfRequest $request): void
    {
        $transaction = $this->orderTransactionReportService->findOrderTransaction(orderId: (int) $request['order_id'], sellerId: $this->vendorId());

        abort_if($transaction === null, 404);

        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('vendor-views.transaction.order_wise_pdf', [
            'transaction' => $transaction,
            'company_phone' => $companySettings['company_phone'] ?? null,
            'company_name' => $companySettings['company_name'] ?? null,
            'company_email' => $companySettings['company_email'] ?? null,
        ]), 'order_transaction_', $request['order_id']);
    }

    private function vendorFilters(FilterRequest $request): array
    {
        return [
            ...$request->reportFilters(),
            OrderTransactionReportService::FILTER_SELLER_ID => $this->vendorId(),
        ];
    }

    private function vendorId(): int
    {
        return (int) auth('seller')->id();
    }

    private function customerInfo(?int $customerId): string
    {
        if ($customerId === null) {
            return self::OPTION_ALL;
        }

        $customer = $this->customerService->find(id: $customerId);

        return $customer ? $customer->f_name.' '.$customer->l_name : '';
    }
}
