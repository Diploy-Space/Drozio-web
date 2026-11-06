<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\Tax\DetailsRequest;
use App\Http\Requests\Admin\Report\Tax\FilterRequest;
use App\Services\New\TaxReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\AdminTaxReportDetailsExport;
use Modules\TaxModule\app\Exports\AdminTaxReportExport;
use Modules\TaxModule\app\Services\New\TaxService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminTaxController extends Controller
{
    private const DETAILS_PER_PAGE = 10;

    private const REPORT_EXPORT_FILE_NAME = 'AdminTaxExport';

    private const DETAILS_EXPORT_FILE_NAME = 'TaxExport';

    public function __construct(
        private readonly TaxReportService $taxReportService,
        private readonly TaxService $taxService,
    ) {}

    public function index(FilterRequest $request): View
    {
        [$startDate, $endDate] = $request->dateRange();
        $reportList = $this->getReportList(request: $request);

        return view('admin-views.report.admin-tax', [
            'dateRangeType' => $request['date_range_type'],
            'calculateTaxOn' => $request['calculate_tax_on'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportList' => $reportList,
            'totalBase' => collect($reportList)->sum('amount'),
            'totalTax' => collect($reportList)->sum('total_tax_amount'),
            'taxOptions' => $this->getModuleTaxes(),
            'sourceLabels' => $this->getSourceLabels(),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        [$startDate, $endDate] = $request->dateRange();
        $reportList = $this->getReportList(request: $request);

        return Excel::download(new AdminTaxReportExport([
            'taxData' => $reportList,
            'search' => $request['search'],
            'from' => $startDate->toIso8601String(),
            'to' => $endDate->toIso8601String(),
            'total_tax_amount' => collect($reportList)->sum('total_tax_amount'),
            'total_amount' => collect($reportList)->sum('amount'),
        ]), self::REPORT_EXPORT_FILE_NAME.'.'.$request->exportExtension());
    }

    public function details(DetailsRequest $request): View
    {
        [$startDate, $endDate] = $request->dateRange();

        return view('admin-views.report.admin-tax-details', [
            ...$this->getDetailsSummary(request: $request),
            'sourceLabels' => $this->getSourceLabels(),
            'transactions' => $this->taxReportService->getAdminTaxSourceTransactions(
                source: $request['source'],
                from: $startDate,
                to: $endDate,
                searchValue: $request['search'],
                dataLimit: self::DETAILS_PER_PAGE,
                page: $request['page'],
            )->withQueryString(),
        ]);
    }

    public function exportDetails(DetailsRequest $request): BinaryFileResponse
    {
        [$startDate, $endDate] = $request->dateRange();

        return Excel::download(new AdminTaxReportDetailsExport([
            ...$this->getDetailsSummary(request: $request),
            'transactions' => $this->taxReportService->getAdminTaxSourceTransactions(
                source: $request['source'],
                from: $startDate,
                to: $endDate,
                searchValue: $request['search'],
                dataLimit: 'all',
            ),
        ]), $request['source'].' '.self::DETAILS_EXPORT_FILE_NAME.'.'.$request->exportExtension());
    }

    private function getDetailsSummary(DetailsRequest $request): array
    {
        [$startDate, $endDate] = $request->dateRange();
        $sourceTaxRates = $this->getSourceTaxRates(request: $request);
        $sourceReport = collect($this->getReportList(request: $request, sourceTaxRates: $sourceTaxRates))->firstWhere('type', $request['source']);

        abort_if($sourceReport === null, 404);

        $totals = $this->taxReportService->getAdminTaxSourceTotals(source: $request['source'], from: $startDate, to: $endDate, searchValue: $request['search']);

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'taxSource' => $sourceReport['type'],
            'totalTaxAmount' => $sourceReport['total_tax_amount'],
            'totalAmount' => $sourceReport['amount'],
            'totalOrderAmount' => $totals['order_amount'],
            'totalOrderCount' => $totals['order_count'],
            'taxRates' => $sourceTaxRates[$request['source']],
        ];
    }

    private function getReportList(FilterRequest $request, ?array $sourceTaxRates = null): array
    {
        if (! $request->canGenerateReport()) {
            return [];
        }

        [$startDate, $endDate] = $request->dateRange();

        return $this->taxReportService->getAdminTaxSummary(
            from: $startDate,
            to: $endDate,
            sourceTaxRates: $sourceTaxRates ?? $this->getSourceTaxRates(request: $request),
            searchValue: $request['search'],
        );
    }

    private function getSourceTaxRates(FilterRequest $request): array
    {
        return $this->taxReportService->groupTaxRatesBySource(
            taxes: $this->getModuleTaxes(ids: $request->selectedTaxIds()),
            calculateTaxOn: $request->calculateTaxOn(),
            taxRateIds: $request->taxRateIds(),
            orderCommissionTaxIds: $request->orderCommissionTaxIds(),
            deliveryChargeTaxIds: $request->deliveryChargeTaxIds(),
        );
    }

    private function getModuleTaxes(?array $ids = null): Collection
    {
        return $this->taxService->getList(
            dataLimit: 'all',
            filters: ['business_module' => BusinessModule::RETAIL, ...($ids === null ? [] : ['id' => $ids])],
            sortBy: TaxService::SORT_OLDEST_ID,
        );
    }

    /** @return array<string, string> */
    private function getSourceLabels(): array
    {
        return [
            TaxReportService::SOURCE_ADMIN_COMMISSION => translate('Admin Commission'),
            TaxReportService::SOURCE_DELIVERY_CHARGE => translate('Delivery Charge'),
        ];
    }
}
