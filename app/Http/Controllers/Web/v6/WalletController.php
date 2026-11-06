<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Wallet\TransactionListRequest;
use App\Services\New\AddFundBonusCategoryService;
use App\Services\New\CustomerService;
use App\Services\New\SettingService;
use App\Services\New\WalletTransactionService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class WalletController extends Controller
{
    private const LIST_LIMIT = 10;

    private const FLAG_SUCCESS = 'success';

    private const FLAG_FAIL = 'fail';

    private const RANGE_SEPARATOR = ' - ';

    private const RANGE_DATE_FORMAT = 'd/m/Y';

    private const MODULE_BADGE_TONES = [
        BusinessModule::RETAIL => 'primary',
        BusinessModule::WHOLESALE => 'success',
        BusinessModule::AUCTION => 'warning',
    ];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly WalletTransactionService $walletTransactionService,
        private readonly AddFundBonusCategoryService $addFundBonusCategoryService,
        private readonly SettingService $settingService,
    ) {}

    public function account(): View
    {
        return view(themeViewName(key: 'wallet_account'));
    }

    public function index(TransactionListRequest $request): View|RedirectResponse
    {
        if (getWebConfig(name: 'wallet_status') != 1) {
            ToastMagic::warning(translate('Access denied!'));

            return redirect()->route('home');
        }

        $createdBetween = $this->getCreatedBetween(transactionRange: $request['transaction_range']);

        if ($createdBetween === null) {
            ToastMagic::error(translate('Invalid date range format'));
        }

        if ($request['flag'] == self::FLAG_SUCCESS) {
            ToastMagic::success(translate('Add fund to wallet successful'));

            return redirect()->route('wallet');
        }

        if ($request['flag'] == self::FLAG_FAIL) {
            ToastMagic::error(translate('Add fund to wallet failed'));

            return redirect()->route('wallet');
        }

        if ($createdBetween === null) {
            return redirect()->route('wallet', Arr::except($request->query(), 'transaction_range'));
        }

        $walletTransactionList = $this->walletTransactionService->getList(
            dataLimit: self::LIST_LIMIT,
            filters: [
                'user_id' => auth('customer')->id(),
                WalletTransactionService::FILTER_CUSTOMER_TRANSACTION_TYPES => $request->get('types', []),
                WalletTransactionService::FILTER_ENTRY_TYPE => $request['filter_by'],
                WalletTransactionService::FILTER_CREATED_BETWEEN => $createdBetween,
            ],
        )->appends($request->query());

        $paymentGatewayList = $this->getPaymentGatewayList();
        $digitalPaymentStatus = getWebConfig(name: 'digital_payment');
        $addFundsToWallet = getWebConfig(name: 'add_funds_to_wallet');

        return view(themeViewName(key: 'user_wallet'), [
            'addFundsToWalletStatus' => $addFundsToWallet && count($paymentGatewayList) > 0 && ($digitalPaymentStatus['status'] ?? 0),
            'totalWalletBalance' => $this->customerService->find(id: auth('customer')->id())->wallet_balance,
            'walletTransactionList' => $walletTransactionList,
            'transactionModuleBadges' => $this->getTransactionModuleBadges(transactions: $walletTransactionList->getCollection()),
            'paymentGatewayList' => $paymentGatewayList,
            'addFundBonusList' => $this->addFundBonusCategoryService->getList(
                dataLimit: 'all',
                filters: [AddFundBonusCategoryService::FILTER_RUNNING_AT => date('Y-m-d')],
                sortBy: AddFundBonusCategoryService::SORT_OLDEST_ID,
            ),
            'transactionTypes' => $request->get('types', []),
            'auctionTransactionFilterStatus' => (bool) getCheckAddonPublishedStatus(moduleName: 'Auction'),
            'filterCount' => count($request['types'] ?? []) + (int) ! empty($request['transaction_range']) + (int) in_array($request['filter_by'], [WalletTransactionService::ENTRY_CREDIT, WalletTransactionService::ENTRY_DEBIT], true),
            'filterBy' => $request['filter_by'] ?? '',
            'transactionRange' => $request['transaction_range'] ?? '',
        ]);
    }

    private function getTransactionModuleBadges(Collection $transactions): array
    {
        $moduleLabels = [
            BusinessModule::RETAIL => translate('Retail'),
            BusinessModule::WHOLESALE => translate('Wholesale'),
            BusinessModule::AUCTION => translate('Auction'),
        ];

        $badges = [];

        foreach ($transactions as $transaction) {
            $businessModule = $this->walletTransactionService->getBusinessModule(transaction: $transaction);

            if (isset($moduleLabels[$businessModule])) {
                $badges[$transaction->id] = ['label' => $moduleLabels[$businessModule], 'tone' => self::MODULE_BADGE_TONES[$businessModule]];
            }
        }

        return $badges;
    }

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

    private function getPaymentGatewayList(): Collection
    {
        return $this->settingService->getList(dataLimit: 'all', filters: array_filter([
            'settings_type' => SettingService::PAYMENT_CONFIG_TYPE,
            'is_active' => 1,
            'key_name' => config('get_payment_publish_status') == 1 ? null : GlobalConstant::DEFAULT_PAYMENT_GATEWAYS,
        ], static fn ($value) => $value !== null));
    }
}
