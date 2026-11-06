<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\VendorReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\VendorSale\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\VendorSaleReportService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorSaleController extends Controller
{
    public function __construct(
        private readonly VendorSaleReportService $vendorSaleReportService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $sellerId = $request->sellerId();

        return view('admin-views.report.seller-product-sale', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'sellerId' => $sellerId,
            'search' => $request['search'],
            'vendors' => $this->vendorService->getList(
                dataLimit: 'all',
                relations: ['shop'],
                filters: ['status' => VendorService::STATUS_APPROVED],
                sortBy: VendorService::SORT_OLDEST,
            ),
            'vendorSaleRows' => $this->vendorSaleReportService->getVendorSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $sellerId,
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'vendorSaleSummary' => $this->vendorSaleReportService->getVendorSaleSummary(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
            'orderAmountChart' => $this->vendorSaleReportService->getOrderAmountChart(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $sellerId = $request->sellerId();

        return Excel::download(new VendorReportExport([
            'search' => $request['search'],
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'shopName' => $sellerId === null ? null : $this->vendorService->find(id: $sellerId, relations: ['shop'])?->shop?->name,
            'vendorSaleRows' => $this->vendorSaleReportService->getVendorSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $sellerId,
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
        ]), Report::VENDOR_REPORT);
    }
}
