<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Wallet\AddFundRequest;
use App\Http\Requests\Customer\Wallet\TransactionListRequest;
use App\Http\Resources\V6\Customer\AddFundBonusResource;
use App\Http\Resources\V6\Customer\WalletTransactionResource;
use App\Services\New\AddFundBonusCategoryService;
use App\Services\New\CustomerService;
use App\Services\New\WalletFundService;
use App\Services\New\WalletTransactionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class WalletController extends BaseApiController
{
    public function __construct(
        private readonly WalletTransactionService $walletTransactionService,
        private readonly AddFundBonusCategoryService $addFundBonusCategoryService,
        private readonly WalletFundService $walletFundService,
        private readonly CustomerService $customerService,
    ) {}

    #[ApiDoc(
        summary: 'Wallet transactions of the authenticated customer',
        tag: 'Wallet',
        query: [
            'limit' => 'nullable|integer|min:1|default:10',
            'offset' => 'nullable|integer|min:1|default:1',
        ],
        hints: [
            'search' => 'Matches transaction id, reference or the customer name and email',
            'entry_type' => 'all lists every transaction, credit lists money in, debit lists money out',
            'transaction_types' => 'JSON array string or transaction_types[] list; added_via_payment_method and earned_by_referral match by reference, auction expands to every auction charge',
            'start_date' => 'Start of the created-at range, used only together with end_date',
            'end_date' => 'End of the created-at range, used only together with start_date',
        ],
        status: [200, 401, 403, 422],
    )]
    public function getTransactions(TransactionListRequest $request): JsonResponse
    {
        if (! $this->isWalletActive()) {
            return $this->responseFormatter(config: config('response.wallet_inactive'));
        }

        $customer = $this->customerService->find(id: auth('api')->id());
        $args = [
            'search' => $request['search'],
            'entry_type' => $request['entry_type'],
            'transaction_types' => $request['transaction_types'] ?? [],
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
        ];

        $transactions = $this->walletTransactionService->getList(
            searchValue: $args['search'],
            dataLimit: $request['limit'] ?? DEFAULT_DATA_LIMIT,
            page: (int) ($request['offset'] ?? 1),
            filters: [
                'user_id' => $customer->id,
                WalletTransactionService::FILTER_ENTRY_TYPE => $args['entry_type'],
                WalletTransactionService::FILTER_CUSTOMER_TRANSACTION_TYPES => $args['transaction_types'],
                WalletTransactionService::FILTER_CREATED_BETWEEN => $this->dateRange($args['start_date'], $args['end_date']),
            ],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => WalletTransactionResource::collection($transactions->through(
                    fn (Model $transaction): Model => $transaction->setAttribute('business_module', $this->walletTransactionService->getBusinessModule(transaction: $transaction)),
                )),
                'summary' => ['total_wallet_balance' => (float) $customer->wallet_balance],
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($transactions),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Add-fund bonus campaigns running right now',
        tag: 'Wallet',
        status: [200, 403],
    )]
    public function getBonuses(): JsonResponse
    {
        if (! $this->isWalletActive()) {
            return $this->responseFormatter(config: config('response.wallet_inactive'));
        }

        $bonuses = $this->addFundBonusCategoryService->getList(
            dataLimit: 'all',
            filters: [AddFundBonusCategoryService::FILTER_RUNNING_AT => now()],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => AddFundBonusResource::collection($bonuses)],
        );
    }

    #[ApiDoc(
        summary: 'Start a wallet top-up and get the gateway redirect link',
        tag: 'Wallet',
        status: [200, 401, 403, 422],
    )]
    public function addFund(AddFundRequest $request): JsonResponse
    {
        if (! $this->isAddFundActive()) {
            return $this->responseFormatter(config: config('response.wallet_fund_inactive'));
        }

        $amount = (float) $request['amount'];
        $blocked = $this->walletFundService->getBlockingReason($amount, $request['current_currency_code']);

        if ($blocked !== null) {
            return $this->responseFormatter(
                config: config('response.'.WalletFundService::REASON_RESPONSE_KEYS[$blocked]),
                content: $this->walletFundService->getAmountRange(),
            );
        }

        return $this->responseFormatter(
            config: config('response.wallet_fund_payment_link'),
            content: ['redirect_link' => $this->walletFundService->generatePaymentLink(
                customer: $this->customerService->find(id: auth('api')->id()),
                amount: $amount,
                paymentMethod: (string) $request['payment_method'],
                paymentPlatform: (string) $request['payment_platform'],
                currentCurrencyCode: $request['current_currency_code'],
                externalRedirectLink: $request['external_redirect_link'],
            )],
        );
    }

    private function dateRange(?string $startDate, ?string $endDate): array
    {
        return $startDate && $endDate
            ? [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]
            : [null, null];
    }

    private function isWalletActive(): bool
    {
        return (int) getWebConfig(name: 'wallet_status') === 1;
    }

    private function isAddFundActive(): bool
    {
        return $this->isWalletActive() && (int) getWebConfig(name: 'add_funds_to_wallet') === 1;
    }
}
