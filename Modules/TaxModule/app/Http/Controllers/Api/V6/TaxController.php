<?php

namespace Modules\TaxModule\app\Http\Controllers\Api\V6;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Services\New\OrderTaxService;
use App\Services\New\OrderTransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Modules\TaxModule\app\Http\Requests\Vendor\Tax\ListRequest as TaxListRequest;
use Modules\TaxModule\app\Http\Requests\Vendor\TaxReport\ListRequest;
use Modules\TaxModule\app\Http\Resources\V6\SellerTaxReportResource;
use Modules\TaxModule\app\Http\Resources\V6\TaxRateResource;
use Modules\TaxModule\app\Services\New\TaxService;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class TaxController extends BaseApiController
{
    use VatTaxManagement;

    private const DEFAULT_REPORT_WINDOW_DAYS = 6;

    public function __construct(
        private readonly OrderTransactionService $orderTransactionService,
        private readonly OrderTaxService $orderTaxService,
        private readonly TaxService $taxService,
    ) {}

    #[ApiDoc(
        summary: 'Retail vat and tax setup configuration',
        tag: 'Vat & Tax (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getSetup(): JsonResponse
    {
        $taxData = $this->getTaxSystemType(getTaxVatList: false, businessModule: BusinessModule::RETAIL);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'is_product_wise' => $taxData['productWiseTax'] && ! $taxData['is_included'],
                'is_category_wise' => $taxData['categoryWiseTax'],
                'is_included' => (bool) $taxData['is_included'],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Active retail vat and tax rates',
        tag: 'Vat & Tax (Seller)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        hints: [
            'limit' => 'Rows per page, or all for every rate in one response',
            'offset' => 'Page number, starting at 1',
            'search' => 'Matches the tax name, id or rate',
        ],
        status: [401, 422],
        guard: 'seller',
    )]
    public function getList(TaxListRequest $request): JsonResponse
    {
        $args = ['search' => $request->query('search')];

        $taxes = $this->taxService->getList(
            searchValue: $args['search'],
            dataLimit: $request->dataLimit(),
            page: $request->offset(),
            filters: ['business_module' => BusinessModule::RETAIL, 'is_active' => 1],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => TaxRateResource::collection($taxes),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($taxes),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Delivered and disbursed retail order tax report for the authenticated seller',
        description: 'start_date/end_date default to the trailing 7 days when omitted.',
        tag: 'Vat & Tax (Seller)',
        query: [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 422],
        guard: 'seller',
    )]
    public function getReport(ListRequest $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $from = $this->reportFromDate($request['start_date'] ?? null);
        $to = $this->reportToDate($request['end_date'] ?? null);
        $dataLimit = max(1, (int) ($request['limit'] ?? getWebConfig(name: 'pagination_limit')));
        $page = max(1, (int) ($request['offset'] ?? 1));

        $orderIds = $this->orderTransactionService->getSellerTaxReportOrderIds(sellerId: $sellerId, businessModule: BusinessModule::RETAIL, from: $from, to: $to);

        $taxGroups = $this->orderTaxService->getTaxBreakdown(
            orderTaxes: $this->orderTaxService->getList(dataLimit: 'all', filters: ['order_id' => $orderIds]),
        );

        $transactions = $this->orderTransactionService->getSellerTaxReportList(
            sellerId: $sellerId,
            businessModule: BusinessModule::RETAIL,
            from: $from,
            to: $to,
            dataLimit: $dataLimit,
            page: $page,
        );

        $transactions->getCollection()->each(function ($transaction): void {
            $transaction->tax_groups = $this->orderTaxService->getTaxBreakdown(orderTaxes: $transaction->orderTaxes);
        });

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'summary' => $this->orderTransactionService->getSellerTaxReportSummary(sellerId: $sellerId, businessModule: BusinessModule::RETAIL, from: $from, to: $to),
                'tax_groups' => $taxGroups,
                'data' => SellerTaxReportResource::collection($transactions->getCollection())->resolve(),
                'params' => $this->paramsFormatter($request->only(['start_date', 'end_date', 'limit', 'offset'])),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }

    private function reportFromDate(?string $startDate): Carbon
    {
        return $startDate !== null
            ? Carbon::parse($startDate)->startOfDay()
            : now()->subDays(self::DEFAULT_REPORT_WINDOW_DAYS)->startOfDay();
    }

    private function reportToDate(?string $endDate): Carbon
    {
        return $endDate !== null ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
    }
}
