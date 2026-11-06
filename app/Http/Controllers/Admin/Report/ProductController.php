<?php

namespace App\Http\Controllers\Admin\Report;

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
        $sellerId = $request->sellerId();

        return view('admin-views.report.all-product', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'sellerId' => $sellerId ?? ProductReportService::SELLER_ALL,
            'search' => $request['search'],
            'vendors' => $this->vendorService->getList(
                dataLimit: 'all',
                relations: ['shop'],
                filters: ['status' => VendorService::STATUS_APPROVED],
                sortBy: VendorService::SORT_OLDEST,
            ),
            'products' => $this->productReportService->getProductSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $sellerId,
                searchValue: $request['search'],
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
            'productStatusCounts' => $this->productReportService->getProductStatusCounts(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
            'productSaleSummary' => $this->productReportService->getProductSaleSummary(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
            'productCountChart' => $this->productReportService->getProductCountChart(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $sellerId = $request->sellerId();

        return Excel::download(new AllProductReportExport([
            'search' => $request['search'],
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'storeName' => $this->storeName(sellerId: $sellerId),
            'products' => $this->productReportService->getProductSaleList(
                dateType: $dateType,
                from: $request['from'],
                to: $request['to'],
                sellerId: $sellerId,
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
        ]), Report::PRODUCT_REPORT_LIST);
    }

    private function storeName(int|string|null $sellerId): ?string
    {
        return match (true) {
            $sellerId === null => null,
            $sellerId === ProductReportService::SELLER_IN_HOUSE => translate('In-house'),
            default => $this->vendorService->find(id: $sellerId, relations: ['shop'])?->shop?->name,
        };
    }
}
