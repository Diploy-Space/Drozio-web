<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Dashboard\StatisticsRequest;
use App\Http\Requests\Vendor\Seller\SetupGuideAppUpdateRequest;
use App\Services\New\OrderService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DashboardController extends BaseApiController
{
    private const ALL_ORDERS_STATISTICS_TYPE = 'all';

    public function __construct(
        private readonly OrderTransactionService $orderTransactionService,
        private readonly OrderService $orderService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Disbursed earning and admin commission chart of the authenticated seller',
        description: 'type is yearEarn (12 months, the default), MonthEarn (each day of this month) or WeekEarn (Sunday to Saturday of this week).',
        tag: 'Dashboard (Seller)',
        query: [
            'type' => 'nullable|string',
        ],
        status: [401, 422],
        guard: 'seller',
    )]
    public function getEarningStatistics(StatisticsRequest $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => [
                    'seller_earn' => $this->orderTransactionService->getSellerDisbursedStatistics(
                        sellerId: $sellerId,
                        amountColumn: OrderTransactionService::AMOUNT_COLUMN_SELLER_AMOUNT,
                        statisticsType: $request['type'],
                    ),
                    'commission_earn' => $this->orderTransactionService->getSellerDisbursedStatistics(
                        sellerId: $sellerId,
                        amountColumn: OrderTransactionService::AMOUNT_COLUMN_ADMIN_COMMISSION,
                        statisticsType: $request['type'],
                    ),
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Order count per status for the authenticated seller',
        description: 'statistics_type is all (the default), today, this_week, this_month or this_year; any other value counts every order.',
        tag: 'Dashboard (Seller)',
        query: [
            'statistics_type' => 'nullable|string',
        ],
        status: [401, 422],
        guard: 'seller',
    )]
    public function getOrderStatistics(StatisticsRequest $request): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $this->orderService->getStatusCountsForSeller(
                    sellerId: $this->sellerId($request),
                    dateType: $request['statistics_type'] ?? self::ALL_ORDERS_STATISTICS_TYPE,
                ),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Disbursed earning per month of the current year for the authenticated seller',
        description: 'data is twelve comma-terminated amounts, January first, as the seller app reads them.',
        tag: 'Dashboard (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getMonthlyEarnings(Request $request): JsonResponse
    {
        return $this->monthlyTotalsResponse(
            sellerId: $this->sellerId($request),
            amountColumn: OrderTransactionService::AMOUNT_COLUMN_SELLER_AMOUNT,
        );
    }

    #[ApiDoc(
        summary: 'Admin commission per month of the current year for the authenticated seller',
        description: 'data is twelve comma-terminated amounts, January first, as the seller app reads them.',
        tag: 'Dashboard (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getMonthlyCommissions(Request $request): JsonResponse
    {
        return $this->monthlyTotalsResponse(
            sellerId: $this->sellerId($request),
            amountColumn: OrderTransactionService::AMOUNT_COLUMN_ADMIN_COMMISSION,
        );
    }

    #[ApiDoc(
        summary: 'Mark one seller app setup guide step',
        description: 'value is stored as an integer under key; the other steps are kept.',
        tag: 'Dashboard (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function updateSetupGuide(SetupGuideAppUpdateRequest $request): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $shop = $this->shopService->findBy(params: ['seller_id' => $sellerId]);

        if ($shop === null) {
            return $this->shopNotFound();
        }

        $setupGuideSteps = $this->decodeSetupGuideSteps(setupGuide: $shop->setup_guide_app);
        $setupGuideSteps[$request['key']] = (int) ($request['value'] ?? 0);

        $shop = $this->shopService->updateBy(
            params: ['seller_id' => $sellerId],
            data: ['setup_guide_app' => $setupGuideSteps],
        );

        return $this->responseFormatter(
            config: config('response.seller_setup_guide_updated'),
            content: ['data' => ['setup_guide_app' => $shop?->setup_guide_app]],
        );
    }

    private function monthlyTotalsResponse(int $sellerId, string $amountColumn): JsonResponse
    {
        $monthlyTotals = $this->orderTransactionService->getSellerMonthlyDisbursedTotals(
            sellerId: $sellerId,
            amountColumn: $amountColumn,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => implode(',', $monthlyTotals).','],
        );
    }

    private function decodeSetupGuideSteps(mixed $setupGuide): array
    {
        $steps = is_array($setupGuide) ? $setupGuide : json_decode((string) $setupGuide, true);

        return is_array($steps) ? $steps : [];
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }

    private function shopNotFound(): JsonResponse
    {
        return $this->authError(
            config: config('response.seller_shop_not_found'),
            errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
        );
    }
}
