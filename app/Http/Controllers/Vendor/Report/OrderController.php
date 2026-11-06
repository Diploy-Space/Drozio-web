<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\OrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\Order\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\OrderReportService;
use App\Services\New\VendorService;
use App\Utils\Helpers;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    private const COMPANY_SETTING_TYPES = ['company_name', 'company_email', 'company_phone', 'company_web_logo'];

    private const PDF_TYPE_SELLER = 'seller';

    public function __construct(
        private readonly OrderReportService $orderReportService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $vendorId = $this->vendorId();

        return view('vendor-views.report.order-report', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'search' => $request['search'],
            'orders' => $this->orderReportService->getOrderList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $vendorId,
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'orderStatusCounts' => $this->orderReportService->getOrderStatusCounts(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'orderAmountSummary' => $this->orderReportService->getOrderAmountSummary(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'paymentStatistics' => $this->orderReportService->getPaymentStatistics(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'orderAmountChart' => $this->orderReportService->getOrderAmountChart(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'orderStatusLabels' => $this->orderStatusLabels(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $vendorId = $this->vendorId();

        return Excel::download(new OrderReportExport([
            'orders' => $this->orderReportService->getOrderList(
                dateType: $request->dateType(),
                from: $request['from'],
                to: $request['to'],
                sellerId: $vendorId,
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
            'search' => $request['search'],
            'vendor' => $this->vendorService->find(id: $vendorId, relations: ['shop']),
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $request->dateType(),
        ]), Report::ORDER_REPORT_LIST);
    }

    public function downloadSummaryPdf(FilterRequest $request): void
    {
        $dateType = $request->dateType();
        $vendor = $this->vendorService->find(id: $this->vendorId(), relations: []);
        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);
        $totals = $this->orderReportService->getSummaryPdfTotals(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $this->vendorId(), searchValue: $request['search']);

        Helpers::gen_mpdf(view('admin-views.transaction.total_orders_report_pdf', [
            'data' => [
                'total_orders' => $totals['total_orders'],
                'search' => $request['search'],
                'seller' => $vendor?->f_name.' '.$vendor?->l_name,
                'type' => self::PDF_TYPE_SELLER,
                'from' => $request['from'],
                'to' => $request['to'],
                'company_name' => $companySettings['company_name'] ?? null,
                'company_email' => $companySettings['company_email'] ?? null,
                'company_phone' => $companySettings['company_phone'] ?? null,
                'company_web_logo' => $companySettings['company_web_logo'] ?? null,
                'date_type' => $dateType,
                'total_order_amount' => $totals['total_order_amount'],
                'total_product_discount' => $totals['total_product_discount'],
                'total_coupon_discount' => $totals['total_coupon_discount'],
                'total_referral_discount' => $totals['total_referral_discount'],
                'tota_order_due_amount' => $totals['total_order_due_amount'],
                'total_order_return_amount' => $totals['total_order_return_amount'],
                'total_tax' => $totals['total_product_tax'],
                'total_order_commission' => $totals['total_order_commission'],
                'total_delivery_charge' => $totals['total_delivery_charge'],
                'total_deliveryman_incentive' => $totals['total_deliveryman_incentive'],
            ],
        ]), 'order_transaction_summary_report_', $dateType);
    }

    private function vendorId(): int
    {
        return (int) auth('seller')->id();
    }

    private function orderStatusLabels(): array
    {
        return [
            OrderReportService::STATUS_PENDING => translate('Pending'),
            OrderReportService::STATUS_CONFIRMED => translate('Confirmed'),
            OrderReportService::STATUS_PROCESSING => translate('Packaging'),
            OrderReportService::STATUS_OUT_FOR_DELIVERY => translate('Out for delivery'),
            OrderReportService::STATUS_DELIVERED => translate('Delivered'),
            OrderReportService::STATUS_FAILED => translate('Failed to deliver'),
            OrderReportService::STATUS_CANCELED => translate('Canceled'),
            OrderReportService::STATUS_RETURNED => translate('Returned'),
        ];
    }
}
