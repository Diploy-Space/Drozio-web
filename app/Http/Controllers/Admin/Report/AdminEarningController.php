<?php

namespace App\Http\Controllers\Admin\Report;

use App\Enums\ExportFileNames\Admin\Report;
use App\Exports\AdminEarningReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Report\Earning\DurationPdfRequest;
use App\Http\Requests\Admin\Report\Earning\FilterRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\EarningReportService;
use App\Utils\Helpers;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminEarningController extends Controller
{
    private const COMPANY_SETTING_TYPES = ['company_name', 'company_email', 'company_phone'];

    public function __construct(
        private readonly EarningReportService $earningReportService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(FilterRequest $request): View
    {
        $dateType = $request->dateType();
        $earningReport = $this->earningReportService->getAdminEarningReport(dateType: $dateType, from: $request['from'], to: $request['to']);

        return view('admin-views.report.admin-earning', [
            'dateType' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'earningRows' => $earningReport['rows'],
            'earningSummary' => $earningReport['summary'],
            'paymentStatistics' => $this->earningReportService->getAdminPaymentStatistics(dateType: $dateType, from: $request['from'], to: $request['to']),
            'inHouseProductCount' => $this->earningReportService->countInHouseProducts(dateType: $dateType, from: $request['from'], to: $request['to']),
            'averageOrderValue' => $this->earningReportService->getAverageOrderValue(dateType: $dateType, from: $request['from'], to: $request['to']),
        ]);
    }

    public function exportList(FilterRequest $request): BinaryFileResponse
    {
        $dateType = $request->dateType();
        $earningReport = $this->earningReportService->getAdminEarningReport(dateType: $dateType, from: $request['from'], to: $request['to']);

        return Excel::download(new AdminEarningReportExport([
            'from' => $request['from'],
            'to' => $request['to'],
            'dateType' => $dateType,
            'inhouseEarn' => $earningReport['rows'],
        ]), Report::ADMIN_EARNING_REPORT);
    }

    public function downloadDurationPdf(DurationPdfRequest $request): void
    {
        $companySettings = $this->businessSettingService->getValuesByTypes(types: self::COMPANY_SETTING_TYPES);

        Helpers::gen_mpdf(view('admin-views.report.admin-earning-duration-wise-pdf', [
            'earning_data' => $request->validated(),
            'company_name' => $companySettings['company_name'] ?? null,
            'company_email' => $companySettings['company_email'] ?? null,
            'company_phone' => $companySettings['company_phone'] ?? null,
        ]), 'admin_earning_', $request['duration']);
    }
}
