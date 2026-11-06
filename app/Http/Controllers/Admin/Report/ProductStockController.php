<?php

namespace App\Http\Controllers\Admin\Report;

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
        $sellerId = $request->sellerId();
        $categoryId = $request->categoryId();

        return view('admin-views.report.product-stock', [
            'sellerId' => $sellerId ?? ProductStockReportService::SELLER_ALL,
            'categoryId' => $categoryId,
            'sort' => $request->sort(),
            'search' => $request['search'],
            'vendors' => $this->vendorService->getList(
                dataLimit: 'all',
                relations: ['shop'],
                filters: ['status' => VendorService::STATUS_APPROVED],
                sortBy: VendorService::SORT_OLDEST,
            ),
            'categories' => $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY),
            'products' => $this->productStockReportService->getProductStockList(
                stockLimit: $this->stockLimit(),
                sellerId: $sellerId,
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
        $sellerId = $request->sellerId();
        $categoryId = $request->categoryId();

        return Excel::download(new StockProductReportExport([
            'search' => $request['search'],
            'sort' => $request->sort(),
            'storeName' => $this->storeName(sellerId: $sellerId),
            'categoryName' => $categoryId === null ? null : $this->categoryService->find(id: $categoryId, relations: [])?->default_name,
            'products' => $this->productStockReportService->getProductStockList(
                stockLimit: $this->stockLimit(),
                sellerId: $sellerId,
                categoryId: $categoryId,
                searchValue: $request['search'],
                sortBy: $request->sort(),
                dataLimit: 'all',
            ),
        ]), Report::PRODUCT_STOCK_REPORT);
    }

    private function stockLimit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'stock_limit', default: 0);
    }

    private function storeName(int|string|null $sellerId): ?string
    {
        return match (true) {
            $sellerId === null => null,
            $sellerId === ProductStockReportService::SELLER_IN_HOUSE => translate('In-house'),
            default => $this->vendorService->find(id: $sellerId, relations: ['shop'])?->shop?->name,
        };
    }
}
