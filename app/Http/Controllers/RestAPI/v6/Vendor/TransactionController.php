<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Report\OrderTransaction\FilterRequest;
use App\Http\Resources\V6\Vendor\TransactionReportResource;
use App\Services\New\OrderTransactionReportService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class TransactionController extends BaseApiController
{
    public function __construct(private readonly OrderTransactionReportService $orderTransactionReportService) {}

    #[ApiDoc(
        summary: 'Retail order transaction report of the authenticated seller: transaction list, product counts, payments and chart',
        tag: 'Report (Seller)',
        query: [
            'limit' => 'nullable|integer|min:1|max:100|default:10',
            'offset' => 'nullable|integer|min:1|default:1',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ],
        hints: [
            'limit' => 'Transactions per page, at most 100.',
            'offset' => 'Page number, starting at 1.',
            'date_type' => 'this_year (the default), this_month, this_week, today or custom_date; custom_date needs from and to.',
            'from' => 'Start date of the custom_date range.',
            'to' => 'End date of the custom_date range, on or after from.',
            'status' => 'all (the default), disburse or hold.',
            'customer_id' => 'Only transactions of this customer; all or empty for every customer.',
            'search' => 'Matches the order id or transaction id.',
        ],
        ignoreQuery: ['seller_id', 'page'],
        status: [200, 401, 422],
        guard: 'seller',
    )]
    public function getList(FilterRequest $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $dateType = $request->dateType();
        $listArgs = $this->listArgs(request: $request, maxLimit: self::LIST_MAX_LIMIT);
        $filters = [
            ...$request->reportFilters(),
            OrderTransactionReportService::FILTER_SELLER_ID => $sellerId,
        ];

        $transactions = $this->orderTransactionReportService->getTransactionList(
            dateType: $dateType,
            from: $request['from'],
            to: $request['to'],
            filters: $filters,
            searchValue: $request['search'],
            dataLimit: $listArgs['limit'],
            page: $listArgs['offset'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => TransactionReportResource::collection($transactions),
                'summary' => [
                    'product_counts' => $this->orderTransactionReportService->getVendorProductCounts(sellerId: $sellerId),
                    'payment_statistics' => $this->orderTransactionReportService->getPaymentStatistics(dateType: $dateType, from: $request['from'], to: $request['to'], filters: $filters),
                    'order_amount_chart' => $this->orderTransactionReportService->getVendorOrderAmountChart(dateType: $dateType, from: $request['from'], to: $request['to'], filters: $filters),
                ],
                'params' => $this->paramsFormatter([
                    'date_type' => $dateType,
                    'from' => $request['from'],
                    'to' => $request['to'],
                    'status' => $filters[OrderTransactionReportService::FILTER_STATUS] ?? null,
                    'customer_id' => $filters[OrderTransactionReportService::FILTER_CUSTOMER_ID] ?? null,
                    'search' => $request['search'],
                ]),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }
}
