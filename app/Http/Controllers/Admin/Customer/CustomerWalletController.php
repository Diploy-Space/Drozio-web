<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Enums\WebConfigKey;
use App\Events\AddFundToWalletEvent;
use App\Exports\CustomerTransactionsExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Wallet\AddFundRequest;
use App\Http\Requests\Admin\Wallet\BonusAddRequest;
use App\Http\Requests\Admin\Wallet\BonusDeleteRequest;
use App\Http\Requests\Admin\Wallet\BonusStatusUpdateRequest;
use App\Http\Requests\Admin\Wallet\BonusUpdateRequest;
use App\Services\New\AddFundBonusCategoryService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\CustomerWalletService;
use App\Services\New\WalletTransactionService;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerWalletController extends BaseController
{
    private const ALL = 'all';

    private const WALLET_STATUS_KEY = 'wallet_status';

    private const DATE_RANGE_FORMAT = 'd M Y';

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly WalletTransactionService $walletTransactionService,
        private readonly AddFundBonusCategoryService $addFundBonusCategoryService,
        private readonly CustomerWalletService $customerWalletService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $filters = $this->getTransactionFilters(
            data: $request->all(),
            dateRange: $this->getDateRange(value: $request['date']),
        );

        $summary = $this->walletTransactionService->getTransactionSummary(filters: $filters);
        $transactions = $this->walletTransactionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value,
            relations: ['user'],
            filters: $filters,
        );

        $customers = $this->getCustomerOptions(searchValue: $request['searchValue']);
        $sidebarCustomers = $customers;
        array_unshift($customers, ['id' => self::ALL, 'text' => translate('All Customer')]);

        return view('admin-views.customer.wallet.report', [
            'credit' => $summary['credit'],
            'debit' => $summary['debit'],
            'balance' => $summary['credit'] - $summary['debit'],
            'transactions' => $transactions,
            'customerStatus' => $this->businessSettingService->findBy(params: ['type' => self::WALLET_STATUS_KEY])?->value,
            'customer' => $this->getSelectedCustomer(customerId: $request['customer_id']),
            'customers' => $customers,
            'sidebarCustomers' => $sidebarCustomers,
            'transactionTypes' => $this->walletTransactionService->getTransactionTypes(),
            'transactionTypeBadges' => $this->getTransactionTypeBadges(),
            'hasActiveFilter' => $this->hasActiveFilter(data: $request->all()),
        ]);
    }

    private function getTransactionTypeBadges(): array
    {
        return [
            WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN => 'success',
            WalletTransactionService::TRANSACTION_TYPE_ADD_FUND => 'success',
            WalletTransactionService::TRANSACTION_TYPE_ORDER_REFUND => 'warning',
            WalletTransactionService::TRANSACTION_TYPE_LOYALTY_POINT => 'primary',
            WalletTransactionService::TRANSACTION_TYPE_ORDER_PLACE => 'info',
            WalletTransactionService::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER => 'info',
            WalletTransactionService::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN => 'warning',
            WalletTransactionService::TRANSACTION_TYPE_AUCTION_ENTRY_FEE => 'info',
            WalletTransactionService::TRANSACTION_TYPE_AUCTION_CLAIM_PAYMENT => 'info',
            WalletTransactionService::TRANSACTION_TYPE_AUCTION_COMMISSION_PAYMENT => 'info',
        ];
    }

    private function hasActiveFilter(array $data): bool
    {
        return ! empty($data['date'])
            || ! empty($data['transaction_type'])
            || (! empty($data['customer_id']) && $data['customer_id'] !== self::ALL);
    }

    public function addFund(AddFundRequest $request): JsonResponse
    {
        $customer = $this->customerService->find(id: $request['customer_id']);
        $walletTransaction = $this->storeFundTransaction(customer: $customer, data: $request->all());

        if ($walletTransaction === null) {
            return response()->json(['errors' => [
                ['error_code' => 'transaction', 'message' => translate('Failed to create transaction')],
            ]], 200);
        }

        $this->customerWalletService->sendFundAddedNotification(customer: $customer, amount: (float) $request['amount']);

        event(new AddFundToWalletEvent(email: $customer['email'], data: [
            'walletTransaction' => $walletTransaction,
            'userName' => $customer['f_name'],
            'userType' => 'customer',
            'templateName' => 'add-fund-to-wallet',
            'subject' => translate('Add Fund To Wallet'),
            'title' => translate('Add Fund To Wallet'),
        ]));

        return response()->json(['message' => translate('Transaction successful')], 200);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $dateRange = $this->getDateRange(value: $request['date']);

        $filters = $this->getTransactionFilters(data: $request->all(), dateRange: $dateRange);

        $summary = $this->walletTransactionService->getTransactionSummary(filters: $filters);

        return Excel::download(new CustomerTransactionsExport(
            data: [
                'transactions' => $this->walletTransactionService->getList(
                    searchValue: $request['searchValue'],
                    dataLimit: self::ALL,
                    relations: ['user'],
                    filters: $filters,
                ),
                'credit' => $summary['credit'],
                'debit' => $summary['debit'],
                'balance' => $summary['credit'] - $summary['debit'],
                'transaction_type' => $request['transaction_type'],
                'transaction_types' => $this->walletTransactionService->getTransactionTypes(),
                'to' => $dateRange[1] ?? null,
                'from' => $dateRange[0] ?? null,
                'customer' => $this->getExportCustomer(customerId: $request['customer_id']),
            ],
            view: 'file-exports.wallet-transactions',
        ), 'Wallet-Transactions-Report.xlsx');
    }

    public function getBonusSetupView(Request $request): View
    {
        $expiredBonusIds = $this->getExpiredBonusIds();

        $this->deactivateBonuses(ids: $expiredBonusIds);

        $data = $this->addFundBonusCategoryService->getList(
            searchValue: $request['search'],
            dataLimit: $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value,
            sortBy: AddFundBonusCategoryService::SORT_NEWEST_ID,
        );

        return view('admin-views.customer.wallet.wallet-bonus-setup', compact('data', 'expiredBonusIds'));
    }

    public function addBonusSetup(BonusAddRequest $request): JsonResponse
    {
        $this->addFundBonusCategoryService->create(data: array_merge(
            $this->getBonusData(data: $request->all()),
            ['end_date_time' => $request['end_date_time'] ?? now()->addDays(7)],
        ));

        return response()->json(['message' => translate('Wallet bonus added successfully')]);
    }

    public function updateStatus(BonusStatusUpdateRequest $request): JsonResponse|RedirectResponse
    {
        $this->addFundBonusCategoryService->updateStatus(id: $request['id'], status: (bool) $request->get('status', 0));

        return response()->json(['message' => translate('Wallet bonus status updated successfully')]);
    }

    public function deleteBonus(BonusDeleteRequest $request): JsonResponse|RedirectResponse
    {
        $this->addFundBonusCategoryService->delete(id: $request['id']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 1,
                'message' => translate('Bonus removed successfully'),
            ]);
        }

        ToastMagic::success(translate('Bonus removed successfully'));

        return back();
    }

    public function update(BonusUpdateRequest $request): JsonResponse
    {
        $this->addFundBonusCategoryService->update(id: $request['id'], data: $this->getBonusData(data: $request->all()));

        return response()->json(['message' => translate('Wallet bonus updated successfully')]);
    }

    /** @param array<string, mixed> $data
     * @throws \Throwable
     */
    private function storeFundTransaction(?Model $customer, array $data): ?Model
    {
        if ($customer === null || $this->businessSettingService->findBy(params: [
            'type' => self::WALLET_STATUS_KEY,
        ])?->value != 1) {
            return null;
        }

        return DB::transaction(function () use ($customer, $data): Model {
            $creditAmount = currencyConverter(amount: (float) $data['amount']);

            $this->customerService->updateWhere(
                params: ['id' => $customer->id],
                data: ['wallet_balance' => $customer->wallet_balance + $creditAmount],
            );

            return $this->walletTransactionService->create(data: [
                'user_id' => $customer->id,
                'transaction_id' => Str::uuid(),
                'reference' => $data['reference'] ?? WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
                'transaction_type' => WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
                'credit' => $creditAmount,
                'debit' => 0,
                'balance' => $customer->wallet_balance + $creditAmount,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $dateRange
     * @return array<string, mixed>
     */
    private function getTransactionFilters(array $data, array $dateRange): array
    {
        $filters = [WalletTransactionService::FILTER_CREATED_BETWEEN => $dateRange];

        if (! empty($data['transaction_type']) && $data['transaction_type'] !== self::ALL) {
            $filters['transaction_type'] = $data['transaction_type'];
        }

        if (! empty($data['customer_id']) && $data['customer_id'] !== self::ALL) {
            $filters['user_id'] = $data['customer_id'];
        }

        return $filters;
    }

    /** @return array<int, ?string> */
    private function getDateRange(?string $value): array
    {
        $dates = explode(' - ', (string) $value);

        if (count($dates) !== 2) {
            return [];
        }

        return [
            Carbon::createFromFormat(self::DATE_RANGE_FORMAT, $dates[0])->startOfDay()->format(self::DATE_TIME_FORMAT),
            Carbon::createFromFormat(self::DATE_RANGE_FORMAT, $dates[1])->endOfDay()->format(self::DATE_TIME_FORMAT),
        ];
    }

    /** @return array<int, array{id: mixed, text: string}> */
    private function getCustomerOptions(?string $searchValue): array
    {
        return $this->customerService->getNameOptions(searchValue: $searchValue, dataLimit: self::ALL)
            ->filter(fn ($customer) => $customer['id'] != 0)
            ->values()
            ->toArray();
    }

    private function getExportCustomer(mixed $customerId): Model|string
    {
        $customer = $this->getSelectedCustomer(customerId: $customerId);

        return $customer instanceof Model ? $customer : 'all_customers';
    }

    private function getSelectedCustomer(mixed $customerId): Model|string
    {
        if (empty($customerId) || $customerId === self::ALL) {
            return self::ALL;
        }

        return $this->customerService->find(id: $customerId) ?? self::ALL;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function getBonusData(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'bonus_type' => $data['bonus_type'],
            'bonus_amount' => $data['bonus_type'] == AddFundBonusCategoryService::BONUS_TYPE_FIXED
                ? currencyConverter($data['bonus_amount'])
                : $data['bonus_amount'],
            'min_add_money_amount' => currencyConverter($data['min_add_money_amount']),
            'max_bonus_amount' => currencyConverter($data['max_bonus_amount']),
            'start_date_time' => $data['start_date_time'],
            'end_date_time' => $data['end_date_time'] ?? null,
        ];
    }

    /** @return array<int, int> */
    private function getExpiredBonusIds(): array
    {
        return $this->addFundBonusCategoryService->getList(
            dataLimit: self::ALL,
            filters: [AddFundBonusCategoryService::FILTER_ENDED_BEFORE => now()->startOfDay()],
        )->pluck('id')->toArray();
    }

    /** @param array<int, int> $ids */
    private function deactivateBonuses(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->addFundBonusCategoryService->updateWhere(
            params: ['id' => $ids, 'is_active' => 1],
            data: ['is_active' => 0],
        );
    }
}
