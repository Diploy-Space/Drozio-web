<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\AddFundBonusCategories;
use App\Models\WalletTransaction;
use App\Services\BaseService;
use App\Utils\Convert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletTransactionService extends BaseService
{
    public const FILTER_CREATED_BETWEEN = 'created_between';

    public const TRANSACTION_TYPE_ADD_FUND_BY_ADMIN = 'add_fund_by_admin';

    public const FILTER_ENTRY_TYPE = 'entry_type';

    public const ENTRY_CREDIT = 'credit';

    public const ENTRY_DEBIT = 'debit';

    public const ENTRY_ALL = 'all';

    public const ENTRY_TYPES = [self::ENTRY_ALL, self::ENTRY_CREDIT, self::ENTRY_DEBIT];

    public const REFERENCE_TYPES = [
        'added_via_payment_method' => 'add_funds_to_wallet',
        'earned_by_referral' => 'earned_by_referral',
    ];

    public const FILTER_CUSTOMER_TRANSACTION_TYPES = 'customer_transaction_types';

    public const TRANSACTION_TYPE_ADD_FUND = 'add_fund';

    public const TRANSACTION_TYPE_ORDER_REFUND = 'order_refund';

    public const TRANSACTION_TYPE_LOYALTY_POINT = 'loyalty_point';

    public const TRANSACTION_TYPE_ORDER_PLACE = 'order_place';

    public const TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER = 'due_payment_for_order';

    public const TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN = 'return_order_amount_by_admin';

    public const TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_SELLER = 'return_order_amount_by_seller';

    public const TRANSACTION_TYPE_AUCTION_ENTRY_FEE = 'auction_entry_fee';

    public const TRANSACTION_TYPE_AUCTION_CLAIM_PAYMENT = 'auction_claim_payment';

    public const TRANSACTION_TYPE_AUCTION_COMMISSION_PAYMENT = 'auction_commission_payment';

    public const CUSTOMER_FILTER_AUCTION = 'auction';

    public const AUCTION_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_AUCTION_ENTRY_FEE,
        self::TRANSACTION_TYPE_AUCTION_CLAIM_PAYMENT,
        self::TRANSACTION_TYPE_AUCTION_COMMISSION_PAYMENT,
    ];

    public const DEBIT_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_ORDER_PLACE,
        self::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER,
        ...self::AUCTION_TRANSACTION_TYPES,
    ];

    private const CUSTOMER_CREDIT_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
        self::TRANSACTION_TYPE_ADD_FUND,
        self::TRANSACTION_TYPE_ORDER_REFUND,
        self::TRANSACTION_TYPE_LOYALTY_POINT,
        self::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER,
        self::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN,
    ];

    private const CUSTOMER_FILTERABLE_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_ORDER_REFUND,
        self::TRANSACTION_TYPE_ORDER_PLACE,
        self::TRANSACTION_TYPE_LOYALTY_POINT,
        self::TRANSACTION_TYPE_ADD_FUND,
        self::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
        self::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER,
        self::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN,
        self::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_SELLER,
        ...self::AUCTION_TRANSACTION_TYPES,
    ];

    private const TYPE_ADDED_VIA_PAYMENT_METHOD = 'added_via_payment_method';

    private const TYPE_EARNED_BY_REFERRAL = 'earned_by_referral';

    public const CUSTOMER_TRANSACTION_TYPE_FILTERS = [
        ...self::CUSTOMER_FILTERABLE_TRANSACTION_TYPES,
        self::TYPE_ADDED_VIA_PAYMENT_METHOD,
        self::TYPE_EARNED_BY_REFERRAL,
        self::CUSTOMER_FILTER_AUCTION,
    ];

    public function __construct(
        WalletTransaction $walletTransaction,
        private readonly AddFundBonusCategories $addFundBonusCategories,
    ) {
        parent::__construct($walletTransaction);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->listQuery($searchValue, $filters)
            ->with($relations)
            ->withCount($withCount)
            ->latest();

        unset($filters[self::FILTER_CREATED_BETWEEN]);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->applyFilters($this->query(), $params)->update($data);
    }

    public function delete(mixed $id): bool
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        return $this->listQuery(null, $filters)->count();
    }

    public function getTransactionTypes(): array
    {
        return [
            self::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN => translate('Add fund by admin'),
            self::TRANSACTION_TYPE_ADD_FUND => translate('Add fund'),
            self::TRANSACTION_TYPE_ORDER_REFUND => translate('Order refund'),
            self::TRANSACTION_TYPE_LOYALTY_POINT => translate('Loyalty point'),
            self::TRANSACTION_TYPE_ORDER_PLACE => translate('Order place'),
            self::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER => translate('Due payment for order'),
            self::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN => translate('Return order amount by admin'),
            self::TRANSACTION_TYPE_AUCTION_ENTRY_FEE => translate('Auction entry fee'),
            self::TRANSACTION_TYPE_AUCTION_CLAIM_PAYMENT => translate('Auction claim payment'),
            self::TRANSACTION_TYPE_AUCTION_COMMISSION_PAYMENT => translate('Auction commission payment'),
        ];
    }

    public function createWalletEntry(
        int|string $customerId,
        float $currentBalance,
        float $amount,
        string $transactionType,
        ?string $reference = null,
        ?string $paymentMethod = null,
        ?float $loyaltyPointExchangeRate = null,
        float|int $addFundBonus = 0,
        ?string $businessModule = null,
    ): ?Model {
        if ($transactionType === self::TRANSACTION_TYPE_LOYALTY_POINT) {
            $creditAmount = $this->redeemedPointValue(point: $amount, pointsPerUsd: $loyaltyPointExchangeRate);

            if ($creditAmount === null) {
                return null;
            }

            $debitAmount = 0.0;
        } else {
            $credit = 0.0;
            $debit = 0.0;

            if (in_array($transactionType, self::DEBIT_TRANSACTION_TYPES, true)) {
                $debit = $amount;
            } elseif (in_array($transactionType, self::CUSTOMER_CREDIT_TRANSACTION_TYPES, true)) {
                $credit = $amount;
            }

            $creditAmount = Convert::usd($credit);
            $debitAmount = Convert::usd($debit);
        }

        return $this->create([
            'user_id' => $customerId,
            'transaction_id' => Str::uuid(),
            'transaction_type' => $transactionType,
            'business_module' => $businessModule,
            'reference' => $reference,
            'payment_method' => $paymentMethod,
            'admin_bonus' => $transactionType === self::TRANSACTION_TYPE_ADD_FUND ? $addFundBonus : 0,
            'credit' => $creditAmount,
            'debit' => $debitAmount,
            'balance' => $currentBalance + $creditAmount - $debitAmount,
        ]);
    }

    public function getBusinessModule(Model $transaction): string
    {
        if ($transaction['business_module'] !== null) {
            return $transaction['business_module'];
        }

        return in_array($transaction['transaction_type'], self::AUCTION_TRANSACTION_TYPES, true)
            ? BusinessModule::AUCTION
            : BusinessModule::RETAIL;
    }

    private function redeemedPointValue(float $point, ?float $pointsPerUsd): ?float
    {
        if ($pointsPerUsd === null || $pointsPerUsd <= 0) {
            return null;
        }

        return $point / $pointsPerUsd;
    }

    public function addFundToWalletBonus(float $amount): string|float
    {
        $bonuses = $this->addFundBonusCategories->where('is_active', 1)
            ->whereDate('start_date_time', '<=', now())
            ->whereDate('end_date_time', '>=', now())
            ->where('min_add_money_amount', '<=', $amount)
            ->get();

        $applicableBonuses = $bonuses->where('min_add_money_amount', $bonuses->max('min_add_money_amount'));

        foreach ($applicableBonuses as $key => $item) {
            $item->applied_bonus_amount = $item->bonus_type == 'percentage' ? ($amount * $item->bonus_amount) / 100 : $item->bonus_amount;

            if ($item->bonus_type == 'percentage' && $item->applied_bonus_amount > $item->max_bonus_amount) {
                $item->applied_bonus_amount = $item->max_bonus_amount;
            }
        }

        return $bonuses->max('applied_bonus_amount') ?? 0;
    }

    public function getTransactionSummary(array $filters = []): array
    {
        $totals = $this->listQuery(null, $filters)
            ->selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->first();

        return [
            'credit' => (float) ($totals?->total_credit ?? 0),
            'debit' => (float) ($totals?->total_debit ?? 0),
        ];
    }

    private function applyCustomerTransactionTypes(Builder $query, array $requested): void
    {
        $referralReference = self::REFERENCE_TYPES[self::TYPE_EARNED_BY_REFERRAL];

        if (in_array(self::CUSTOMER_FILTER_AUCTION, $requested, true)) {
            $requested = [...$requested, ...self::AUCTION_TRANSACTION_TYPES];
        }

        $types = array_values(array_intersect(self::CUSTOMER_FILTERABLE_TRANSACTION_TYPES, $requested));
        $includesPaymentMethodFunds = in_array(self::TYPE_ADDED_VIA_PAYMENT_METHOD, $requested, true);
        $includesReferralEarnings = in_array(self::TYPE_EARNED_BY_REFERRAL, $requested, true);

        if ($types === [] && ! $includesPaymentMethodFunds && ! $includesReferralEarnings) {
            return;
        }

        $query->where(fn (Builder $inner) => $inner
            ->when($types !== [], fn (Builder $builder) => $builder->orWhere(fn (Builder $selected) => $selected
                ->whereIn('transaction_type', $types)
                ->where(fn (Builder $withoutReferralEarnings) => $withoutReferralEarnings
                    ->whereNull('reference')
                    ->orWhere('reference', '!=', $referralReference))))
            ->when($includesPaymentMethodFunds, fn (Builder $builder) => $builder
                ->orWhere('reference', self::REFERENCE_TYPES[self::TYPE_ADDED_VIA_PAYMENT_METHOD]))
            ->when($includesReferralEarnings, fn (Builder $builder) => $builder
                ->orWhere('reference', $referralReference)));
    }

    private function listQuery(?string $searchValue, array $filters): Builder
    {
        $createdBetween = $filters[self::FILTER_CREATED_BETWEEN] ?? [];
        $entryType = $filters[self::FILTER_ENTRY_TYPE] ?? null;
        $customerTransactionTypes = $filters[self::FILTER_CUSTOMER_TRANSACTION_TYPES] ?? [];
        unset(
            $filters[self::FILTER_CREATED_BETWEEN],
            $filters[self::FILTER_ENTRY_TYPE],
            $filters[self::FILTER_CUSTOMER_TRANSACTION_TYPES],
        );

        $query = $this->query()
            ->when($entryType === self::ENTRY_DEBIT, fn (Builder $builder) => $builder->where('debit', '!=', 0))
            ->when($entryType === self::ENTRY_CREDIT, fn (Builder $builder) => $builder->where('debit', '=', 0))
            ->when($customerTransactionTypes, fn (Builder $builder) => $this->applyCustomerTransactionTypes($builder, (array) $customerTransactionTypes))
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('transaction_id', 'like', "%{$searchValue}%")
                    ->orWhere('reference', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function (Builder $user) use ($searchValue): void {
                        $user->where('f_name', 'like', "%{$searchValue}%")
                            ->orWhere('l_name', 'like', "%{$searchValue}%")
                            ->orWhereRaw("CONCAT(f_name, ' ', l_name) like ?", ["%{$searchValue}%"])
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            }))
            ->when($createdBetween[0] ?? null, fn (Builder $builder) => $builder->where('created_at', '>=', $createdBetween[0]))
            ->when($createdBetween[1] ?? null, fn (Builder $builder) => $builder->where('created_at', '<=', $createdBetween[1]));

        return $this->applyFilters($query, $filters);
    }
}
