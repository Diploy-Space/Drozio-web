<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\VendorEarningReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\Earning\FilterRequest;
use App\Services\New\EarningReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorEarningController extends Controller
{
    public function __construct(
        private readonly EarningReportService $earningReportService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $vendorEarning = $this->earningReportService->getVendorEarningList(dateType: $dateType, from: $request['from'], to: $request['to']);

        return view('admin-views.report.seller-earning', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'vendorEarningRows' => $vendorEarning['rows'],
            'vendorEarningSummary' => $vendorEarning['summary'],
            'earningChart' => $this->earningReportService->getVendorEarningChart(dateType: $dateType, from: $request['from'], to: $request['to']),
            'vendorWallet' => $this->earningReportService->getVendorWalletSummary(),
            'vendorSalesTotal' => $this->earningReportService->getVendorSalesTotal(dateType: $dateType, from: $request['from'], to: $request['to']),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $vendorEarning = $this->earningReportService->getVendorEarningList(dateType: $dateType, from: $request['from'], to: $request['to']);

        return Excel::download(new VendorEarningReportExport([
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'vendorEarnTable' => $vendorEarning['rows'],
        ]), Report::VENDOR_EARNING_REPORT);
    }
}
