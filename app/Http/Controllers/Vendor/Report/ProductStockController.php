<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\StockProductReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Report\ProductStock\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\ProductStockReportService;
use App\Services\New\VendorService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductStockController extends Controller
{
    public function __construct(
        private readonly ProductStockReportService $productStockReportService,
        private readonly CategoryService $categoryService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $categoryId = $request->categoryId();
        $stockLimit = $this->stockLimit();

        return view('vendor-views.report.product-stock', [
            'categoryId' => $categoryId,
            'sort' => $request->sort(),
            'search' => $request['search'],
            'stockLimit' => $stockLimit,
            'categories' => $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY),
            'products' => $this->productStockReportService->getProductStockList(
                stockLimit: $stockLimit,
                sellerId: $this->vendorId(),
                categoryId: $categoryId,
                searchValue: $request['search'],
                sortBy: $request->sort(),
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
            )->withQueryString(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $categoryId = $request->categoryId();

        return Excel::download(new StockProductReportExport([
            'search' => $request['search'],
            'sort' => $request->sort(),
            'storeName' => $this->vendorService->find(id: $this->vendorId(), relations: ['shop'])?->shop?->name,
            'categoryName' => $categoryId === null ? null : $this->categoryService->find(id: $categoryId, relations: [])?->default_name,
            'products' => $this->productStockReportService->getProductStockList(
                stockLimit: $this->stockLimit(),
                sellerId: $this->vendorId(),
                categoryId: $categoryId,
                searchValue: $request['search'],
                sortBy: $request->sort(),
                dataLimit: 'all',
            ),
        ]), Report::PRODUCT_STOCK_REPORT);
    }

    private function stockLimit(): int
    {
        $vendorStockLimit = (int) $this->vendorService->find(id: $this->vendorId(), relations: [])?->stock_limit;

        return $vendorStockLimit > 0 ? $vendorStockLimit : (int) $this->businessSettingService->getValueByType(type: 'stock_limit', default: 0);
    }

    private function vendorId(): int
    {
        return (int) auth('seller')->id();
    }
}
