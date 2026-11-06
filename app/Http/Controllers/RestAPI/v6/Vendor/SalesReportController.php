<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Report\Order\FilterRequest;
use App\Http\Resources\V6\Vendor\SalesReportResource;
use App\Services\New\OrderReportService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class SalesReportController extends BaseApiController
{
    public function __construct(private readonly OrderReportService $orderReportService) {}

    #[ApiDoc(
        summary: 'Retail order report of the authenticated seller: order list, status counts, amounts, payments and chart',
        tag: 'Report (Seller)',
        query: [
            'limit' => 'nullable|integer|min:1|max:100|default:10',
            'offset' => 'nullable|integer|min:1|default:1',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ],
        hints: [
            'limit' => 'Orders per page, at most 100.',
            'offset' => 'Page number, starting at 1.',
            'date_type' => 'this_year (the default), this_month, this_week, today or custom_date; custom_date needs from and to.',
            'from' => 'Start date of the custom_date range.',
            'to' => 'End date of the custom_date range, on or after from.',
            'search' => 'Matches the order id.',
        ],
        ignoreQuery: ['seller_id', 'page'],
        status: [200, 401, 422],
        guard: 'seller',
    )]
    public function getReport(FilterRequest $request): JsonResponse
    {
        $sellerId = (int) $request->attributes->get('seller')->id;
        $dateType = $request->dateType();
        $listArgs = $this->listArgs(request: $request, maxLimit: self::LIST_MAX_LIMIT);

        $orders = $this->orderReportService->getOrderList(
            dateType: $dateType,
            from: $request['from'],
            to: $request['to'],
            sellerId: $sellerId,
            searchValue: $request['search'],
            dataLimit: $listArgs['limit'],
            page: $listArgs['offset'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => SalesReportResource::collection($orders),
                'summary' => [
                    'order_status_counts' => $this->orderReportService->getOrderStatusCounts(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
                    'order_amount_summary' => $this->orderReportService->getOrderAmountSummary(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
                    'payment_statistics' => $this->orderReportService->getPaymentStatistics(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
                    'order_amount_chart' => $this->orderReportService->getOrderAmountChart(dateType: $dateType, from: $request['from'], to: $request['to'], sellerId: $sellerId),
                ],
                'params' => $this->paramsFormatter([
                    'date_type' => $dateType,
                    'from' => $request['from'],
                    'to' => $request['to'],
                    'search' => $request['search'],
                ]),
                'pagination' => $this->paginateFormatter($orders),
            ],
        );
    }
}
