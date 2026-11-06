<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Wallet\LoyaltyExchangeRequest;
use App\Http\Requests\Customer\Wallet\LoyaltyPointListRequest;
use App\Mail\AddFundToWallet;
use App\Models\WalletTransaction;
use App\Services\New\CustomerService;
use App\Services\New\LoyaltyPointService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\WalletTransactionService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LoyaltyController extends Controller
{
    private const LIST_LIMIT = 10;

    private const RANGE_SEPARATOR = ' - ';

    private const RANGE_DATE_FORMAT = 'd/m/Y';

    private const TRANSACTION_TYPE_ALL = 'all';

    private const SELECTABLE_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_ALL,
        LoyaltyPointTransactionService::TRANSACTION_TYPE_ORDER_PLACE,
        LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET,
        LoyaltyPointTransactionService::TRANSACTION_TYPE_REFUND_ORDER,
    ];

    private const REWARD_POINTS_NOT_CONVERTED = 0;

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly LoyaltyPointService $loyaltyPointService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    public function index(LoyaltyPointListRequest $request): View|RedirectResponse
    {
        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');

        if ($loyaltyPointStatus != 1) {
            ToastMagic::warning(translate('Access denied'));

            return redirect()->route('home');
        }

        $createdBetween = $this->getCreatedBetween(transactionRange: $request['transaction_range']);

        if ($createdBetween === null) {
            ToastMagic::error(translate('Invalid date range format'));

            return redirect()->route('loyalty', Arr::except($request->query(), 'transaction_range'));
        }

        $customerId = auth('customer')->id();
        $transactionTypes = $this->getSelectedTransactionTypes(types: $request['types'] ?? []);

        $loyaltyPointList = $this->loyaltyPointService->getList(
            dataLimit: self::LIST_LIMIT,
            filters: [
                'user_id' => $customerId,
                LoyaltyPointService::FILTER_ENTRY_TYPE => $request['filter_by'],
                LoyaltyPointService::FILTER_CREATED_BETWEEN => $createdBetween,
            ] + $this->getTransactionTypeFilter(transactionTypes: $transactionTypes),
        )->appends($request->query());

        return view(themeViewName(key: 'user_loyalty'), [
            'totalLoyaltyPoint' => $this->customerService->find(id: $customerId)->loyalty_point,
            'loyaltyPointMinimumPoint' => getWebConfig(name: 'loyalty_point_minimum_point'),
            'loyaltyPointExchangeRate' => getWebConfig(name: 'loyalty_point_exchange_rate'),
            'loyaltyPointList' => $loyaltyPointList,
            'loyaltyPointStatus' => $loyaltyPointStatus,
            'walletStatus' => getWebConfig(name: 'wallet_status'),
            'transactionTypes' => $transactionTypes,
            'filterCount' => count($transactionTypes) + (int) ! empty($request['transaction_range']) + (int) in_array($request['filter_by'], [LoyaltyPointService::ENTRY_CREDIT, LoyaltyPointService::ENTRY_DEBIT], true),
            'filterBy' => $request['filter_by'] ?? '',
            'transactionRange' => $request['transaction_range'] ?? '',
            'orderReferenceTypes' => $this->loyaltyPointTransactionService->getOrderReferenceTypes(),
            'moduleLabels' => $this->getModuleLabels(),
            'transactionTypeLabels' => $this->loyaltyPointTransactionService->getTransactionTypes(),
        ]);
    }

    public function exchange(LoyaltyExchangeRequest $request): RedirectResponse
    {
        if (getWebConfig(name: 'wallet_status') != 1 || getWebConfig(name: 'loyalty_point_status') != 1) {
            ToastMagic::warning(translate('Transferring loyalty points to currency is not possible at this moment!'));

            return redirect()->route('home');
        }

        $customer = $this->customerService->find(id: auth('customer')->id());

        if ($customer === null) {
            ToastMagic::info(translate('Login first for next steps'));

            return redirect()->route('customer.auth.login');
        }

        $point = (int) $request['point'];
        $blockingReason = $this->loyaltyPointService->getBalanceFirstBlockingReason(customer: $customer, point: $point);

        if ($blockingReason !== null) {
            ToastMagic::warning($this->getBlockingReasonMessage(reason: $blockingReason));

            return back();
        }

        $walletTransaction = $this->convertPointsToWallet(customer: $customer, point: $point);

        $this->sendWalletCreditMail(email: $customer->email, walletTransaction: $walletTransaction);

        ToastMagic::success(translate('Point to wallet transferred successfully'));

        return back();
    }

    public function getCurrencyAmount(Request $request): JsonResponse
    {
        return response()->json(loyaltyPointToLocalCurrency(amount: (float) $request['amount'], type: 'web'));
    }

    public function convertRewardPoints(): int
    {
        return self::REWARD_POINTS_NOT_CONVERTED;
    }

    private function convertPointsToWallet(Model $customer, int $point): WalletTransaction
    {
        return DB::transaction(function () use ($customer, $point): WalletTransaction {
            $walletTransaction = $this->walletTransactionService->createWalletEntry(
                customerId: $customer->id,
                currentBalance: (float) $customer->wallet_balance,
                amount: $point,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_LOYALTY_POINT,
                reference: LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET,
                loyaltyPointExchangeRate: (float) getWebConfig(name: 'loyalty_point_exchange_rate'),
            );

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

    private function sendWalletCreditMail(?string $email, WalletTransaction $walletTransaction): void
    {
        try {
            Mail::to($email)->send(new AddFundToWallet($walletTransaction));
        } catch (Exception) {
        }
    }

    private function getBlockingReasonMessage(string $reason): string
    {
        return $reason === LoyaltyPointService::REASON_INSUFFICIENT_POINT
            ? translate('Conversion is limited to current points only')
            : translate('Oops! You need more points to convert to your wallet balance');
    }

    /** @return array<string, string> */
    private function getModuleLabels(): array
    {
        return [
            BusinessModule::RETAIL => translate('Retail'),
            BusinessModule::WHOLESALE => translate('Wholesale'),
            BusinessModule::AUCTION => translate('Auction'),
        ];
    }

    /** @return array<int, string> */
    private function getSelectedTransactionTypes(array $types): array
    {
        return array_values(array_filter(
            self::SELECTABLE_TRANSACTION_TYPES,
            static fn (string $type): bool => in_array($type, $types),
        ));
    }

    /** @return array<string, array<int, string>> */
    private function getTransactionTypeFilter(array $transactionTypes): array
    {
        if (empty($transactionTypes) || in_array(self::TRANSACTION_TYPE_ALL, $transactionTypes)) {
            return [];
        }

        return ['transaction_type' => $transactionTypes];
    }

    /** @return array<int, string>|null */
    private function getCreatedBetween(?string $transactionRange): ?array
    {
        if (empty($transactionRange)) {
            return [];
        }

        $dates = explode(self::RANGE_SEPARATOR, $transactionRange);

        if (count($dates) !== 2) {
            return null;
        }

        $startDate = $this->parseRangeDate(date: $dates[0]);
        $endDate = $this->parseRangeDate(date: $dates[1]);

        if ($startDate === null || $endDate === null) {
            return null;
        }

        return [$startDate.' 00:00:00', $endDate.' 23:59:59'];
    }

    private function parseRangeDate(string $date): ?string
    {
        if (! checkDateFormatInMDY($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat(self::RANGE_DATE_FORMAT, $date)->format('Y-m-d');
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
