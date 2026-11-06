<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\AllProductReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\Product\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\ProductReportService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductReportService $productReportService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $vendorId = $this->vendorId();

        return view('vendor-views.report.all-product', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'search' => $request['search'],
            'products' => $this->productReportService->getProductSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $vendorId,
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'productStatusCounts' => $this->productReportService->getProductStatusCounts(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'productSaleSummary' => $this->productReportService->getProductSaleSummary(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
            'productCountChart' => $this->productReportService->getProductCountChart(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $vendorId),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $vendorId = $this->vendorId();

        return Excel::download(new AllProductReportExport([
            'search' => $request['search'],
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'storeName' => $this->vendorService->find(id: $vendorId, relations: ['shop'])?->shop?->name,
            'products' => $this->productReportService->getProductSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $vendorId,
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
        ]), Report::PRODUCT_REPORT_LIST);
    }

    private function vendorId(): int
    {
        return (int) auth('seller')->id();
    }
}
