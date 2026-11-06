<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Enums\BusinessModule;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Wallet\LoyaltyExchangeRequest;
use App\Http\Resources\V6\Customer\LoyaltyPointResource;
use App\Services\New\CustomerService;
use App\Services\New\LoyaltyPointService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\WalletTransactionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class LoyaltyController extends BaseApiController
{
    public function __construct(
        private readonly LoyaltyPointService $loyaltyPointService,
        private readonly CustomerService $customerService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    #[ApiDoc(
        summary: 'Loyalty point transactions of the authenticated customer',
        tag: 'Loyalty',
        query: [
            'limit' => 'nullable|string',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'entry_type' => 'nullable|string',
            'transaction_types' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ],
        status: [200, 401, 403],
    )]
    public function getTransactions(Request $request): JsonResponse
    {
        if (! $this->isLoyaltyActive()) {
            return $this->responseFormatter(config: config('response.loyalty_point_inactive'));
        }

        $customer = $this->customerService->find(id: auth('api')->id());
        $args = [
            'search' => $request->query('search'),
            'entry_type' => $request->query('entry_type'),
            'transaction_types' => $request->query('transaction_types'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];

        $transactions = $this->loyaltyPointService->getList(
            searchValue: $args['search'],
            dataLimit: $request->query('limit', DEFAULT_DATA_LIMIT),
            page: (int) $request->query('offset', 1),
            filters: array_filter([
                'user_id' => $customer->id,
                'transaction_type' => (array) $args['transaction_types'],
                LoyaltyPointService::FILTER_ENTRY_TYPE => $args['entry_type'],
                LoyaltyPointService::FILTER_CREATED_BETWEEN => $this->dateRange($args['start_date'], $args['end_date']),
            ]),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => LoyaltyPointResource::collection($transactions),
                'summary' => ['total_loyalty_point' => (int) $customer->loyalty_point],
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Convert loyalty points into wallet balance',
        description: 'Credits the wallet with the converted points and debits the same points from the loyalty balance.',
        tag: 'Loyalty',
        status: [200, 401, 403, 422],
    )]
    public function convert(LoyaltyExchangeRequest $request): JsonResponse
    {
        if (! $this->isLoyaltyActive() || (int) getWebConfig(name: 'wallet_status') !== 1) {
            return $this->responseFormatter(config: config('response.loyalty_point_conversion_unavailable'));
        }

        $customer = $this->customerService->find(id: auth('api')->id());
        $point = (int) $request['point'];

        $blocked = $this->loyaltyPointService->getBlockingReason($customer, $point);

        if ($blocked !== null) {
            return $this->responseFormatter(
                config: config('response.'.LoyaltyPointService::REASON_RESPONSE_KEYS[$blocked]),
                content: ['minimum_convertible_point' => $this->loyaltyPointService->getMinimumConvertiblePoint()],
            );
        }

        $walletTransaction = $this->convertPointsToWallet(customer: $customer, point: $point);

        if ($walletTransaction === null) {
            return $this->responseFormatter(config: config('response.loyalty_point_conversion_unavailable'));
        }

        return $this->responseFormatter(
            config: config('response.loyalty_point_converted'),
            content: [
                'transaction_id' => $walletTransaction->transaction_id,
                'converted_point' => $point,
                'wallet_balance' => (float) $customer->refresh()->wallet_balance,
                'loyalty_point' => (int) $customer->loyalty_point,
            ],
        );
    }

    /** @return array{0: ?string, 1: ?string} */
    private function dateRange(?string $startDate, ?string $endDate): array
    {
        return $startDate && $endDate
            ? [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]
            : [null, null];
    }

    private function isLoyaltyActive(): bool
    {
        return (int) getWebConfig(name: 'loyalty_point_status') === 1;
    }

    private function convertPointsToWallet(Model $customer, int $point): ?Model
    {
        return DB::transaction(function () use ($customer, $point): ?Model {
            $walletTransaction = $this->walletTransactionService->createWalletEntry(
                customerId: $customer->id,
                currentBalance: (float) $customer->wallet_balance,
                amount: $point,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_LOYALTY_POINT,
                reference: LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET,
                loyaltyPointExchangeRate: (float) getWebConfig(name: 'loyalty_point_exchange_rate'),
            );

            if ($walletTransaction === null) {
                return null;
            }

            $this->customerService->updateWalletBalance(
                id: $customer->id,
                walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
            );

            $pointTransaction = $this->loyaltyPointTransactionService->createPointEntry(
                customerId: $customer->id,
                currentPoint: (float) $customer->loyalty_point,
                amount: $point,
                transactionType: LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET,
                reference: (string) $walletTransaction->transaction_id,
                businessModule: BusinessModule::COMMON,
            );

            if ($pointTransaction !== null) {
                $this->customerService->updateWhere(
                    params: ['id' => $customer->id],
                    data: ['loyalty_point' => $pointTransaction->balance],
                );
            }

            return $walletTransaction;
        });
    }
}
