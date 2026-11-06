<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\BusinessModule;
use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\RefundTransactionReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\RefundTransaction\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\RefundTransactionService;
use App\Traits\PdfGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundTransactionController extends Controller
{
    use PdfGenerator;

    private const LIST_RELATIONS = ['order.seller.shop', 'orderDetails.product'];

    public function __construct(
        private readonly RefundTransactionService $refundTransactionService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        return view('admin-views.refund-transaction.list', [
            'searchValue' => $request['searchValue'],
            'paymentMethod' => $request->paymentMethod() ?? FilterRequest::PAYMENT_METHOD_ALL,
            'refundTransactions' => $this->getRefundTransactions(
                request: $request,
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
            )->withQueryString(),
            'isInHouseShopVerified' => isInHouseShopVerified(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        return Excel::download(new RefundTransactionReportExport([
            'searchValue' => $request['searchValue'],
            'paymentMethod' => $request['payment_method'],
            'transactions' => $this->getRefundTransactions(request: $request, dataLimit: 'all'),
        ]), Report::REFUND_TRANSACTION_REPORT_LIST);
    }

    public function downloadSummaryPdf(FilterRequest $request): void
    {
        $this->generatePdf(
            view: view('admin-views.refund-transaction.refund_transaction_summary_report_pdf', [
                'data' => $this->refundTransactionService->getPdfData(
                    company: [
                        'phone' => $this->businessSettingService->getValueByType(type: 'company_phone'),
                        'email' => $this->businessSettingService->getValueByType(type: 'company_email'),
                        'name' => $this->businessSettingService->getValueByType(type: 'company_name'),
                        'web_logo' => $this->businessSettingService->getValueByType(type: 'company_web_logo'),
                    ],
                    refundTransactions: $this->getRefundTransactions(request: $request, dataLimit: 'all'),
                ),
            ]),
            filePrefix: 'refund_transaction_summary_report_',
            filePostfix: date('Y'),
        );
    }

    private function getRefundTransactions(FilterRequest $request, int|string $dataLimit): Collection|LengthAwarePaginator
    {
        return $this->refundTransactionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $dataLimit,
            page: $request['page'],
            relations: self::LIST_RELATIONS,
            filters: array_filter([
                RefundTransactionService::FILTER_BUSINESS_MODULE => BusinessModule::RETAIL,
                'payment_method' => $request->paymentMethod(),
            ]),
            sortBy: RefundTransactionService::SORT_NEWEST_ID,
        );
    }
}
