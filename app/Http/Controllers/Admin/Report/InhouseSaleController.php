<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\InhouseProductSaleReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\InhouseSale\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\CategoryService;
use App\Services\New\InhouseSaleReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InhouseSaleController extends Controller
{
    public function __construct(
        private readonly InhouseSaleReportService $inhouseSaleReportService,
        private readonly CategoryService $categoryService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $filters = [InhouseSaleReportService::FILTER_CATEGORY_ID => $request->categoryId()];

        return view('admin-views.report.inhouse-product-sale', [
            'categories' => $this->categoryService->getList(dataLimit: 'all', position: CategoryService::POSITION_CATEGORY),
            'selectedCategoryId' => $request->categoryId(),
            'products' => $this->inhouseSaleReportService->getList(
                dataLimit: $this->businessSettingService->getValueByType(type: 'pagination_limit', default: DEFAULT_DATA_LIMIT),
                page: $request['page'],
                filters: $filters,
            ),
            'saleSummary' => $this->inhouseSaleReportService->getSaleSummary(filters: $filters),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $categoryId = $request->categoryId();

        return Excel::download(new InhouseProductSaleReportExport([
            'categoryName' => $categoryId === null ? null : $this->categoryService->find(id: $categoryId, relations: [])?->name,
            'products' => $this->inhouseSaleReportService->getList(
                dataLimit: 'all',
                filters: [InhouseSaleReportService::FILTER_CATEGORY_ID => $categoryId],
            ),
        ]), Report::INHOUSE_PRODUCT_SALE_REPORT);
    }
}
