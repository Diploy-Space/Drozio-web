<?php

namespace App\Http\Controllers\Vendor\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\Report\Vat\FilterRequest;
use App\Services\New\ShopService;
use App\Services\New\VatReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\VendorTaxExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VatController extends Controller
{
    private const PER_PAGE = 10;

    private const ROW_RELATIONS = ['order', 'orderTaxes'];

    public function __construct(
        private readonly VatReportService $vatReportService,
        private readonly ShopService $shopService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $filters = [VatReportService::FILTER_SHOP_ID => $this->vendorShop()?->id];

        return view('vendor-views.report.vat-report', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $request['search'],
            'summary' => $this->vatReportService->getSummary(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $request['search']),
            'taxBreakdown' => $this->vatReportService->getTaxBreakdown(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $request['search']),
            'orderTransactions' => $this->vatReportService->getTransactionList(
                startDate: $startDate,
                endDate: $endDate,
                filters: $filters,
                searchValue: $request['search'],
                dataLimit: self::PER_PAGE,
                page: $request['page'],
                relations: self::ROW_RELATIONS,
            )->withQueryString(),
            'taxTypeLabels' => $this->taxTypeLabels(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $shop = $this->vendorShop();
        $filters = [VatReportService::FILTER_SHOP_ID => $shop?->id];
        $summary = $this->vatReportService->getSummary(startDate: $startDate, endDate: $endDate, filters: $filters, searchValue: $request['search']);

        return Excel::download(new VendorTaxExport([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shop' => $shop,
            'totalTax' => $summary['total_tax'],
            'totalOrders' => $summary['total_orders'],
            'totalOrderAmount' => $summary['total_order_amount'],
            'orderTransactions' => $this->vatReportService->getTransactionList(
                startDate: $startDate,
                endDate: $endDate,
                filters: $filters,
                searchValue: $request['search'],
                dataLimit: 'all',
                relations: self::ROW_RELATIONS,
            ),
            'search' => $request['search'],
        ]), $shop?->name.'s TaxExport.'.$request->exportExtension());
    }

    private function vendorShop(): ?Model
    {
        return $this->shopService->findBy(params: ['seller_id' => auth('seller')->id()]);
    }

    /** @return array<string, string> */
    private function taxTypeLabels(): array
    {
        return [
            'order_wise' => translate('Order wise'),
            'product_wise' => translate('Product wise'),
            'category_wise' => translate('Category wise'),
        ];
    }
}
