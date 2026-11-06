<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Exports\CustomerTransactionsExport;
use App\Http\Controllers\BaseController;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Traits\BusinessModuleScopeTrait;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerLoyaltyController extends BaseController
{
    use BusinessModuleScopeTrait;

    private const ALL = 'all';

    private const DATE_RANGE_FORMAT = 'd M Y';

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
    ) {}

    protected function businessModuleAllowList(): array
    {
        return [BusinessModule::RETAIL, BusinessModule::WHOLESALE];
    }

    public function index(?Request $request, ?string $type = null): View
    {
        $module = $this->resolveModuleWithCommon($request['business_module']);

        $filters = $this->getTransactionFilters(
            data: $request->all(),
            dateRange: $this->getDateRange(value: $request['date']),
            module: $module,
        );

        $summary = $this->loyaltyPointTransactionService->getTransactionSummary(filters: $filters);
        $transactions = $this->loyaltyPointTransactionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->businessSettingService->findBy(params: ['type' => WebConfigKey::PAGINATION_LIMIT])?->value,
            relations: ['user'],
            filters: $filters,
        );

        $customers = $this->getCustomerOptions(searchValue: $request['searchValue']);
        array_unshift($customers, ['id' => self::ALL, 'text' => translate('All Customer')]);

        return view('admin-views.customer.loyalty.report', [
            'credit' => $summary['credit'],
            'debit' => $summary['debit'],
            'balance' => $summary['credit'] - $summary['debit'],
            'transactions' => $transactions,
            'customer' => $this->getSelectedCustomer(customerId: $request['customer_id']),
            'customers' => $customers,
            'transactionTypes' => $this->loyaltyPointTransactionService->getTransactionTypes(),
            'filterTransactionTypes' => $this->getFilterTransactionTypes(module: $module),
            'transactionTypeBadges' => $this->getTransactionTypeBadges(),
            'orderReferenceTypes' => $this->loyaltyPointTransactionService->getOrderReferenceTypes(),
            'orderDetailsRouteName' => $this->getOrderDetailsRouteName(module: $module),
            'hasActiveFilter' => $this->hasActiveFilter(data: $request->all(), module: $module),
            'businessModuleTabs' => $this->getBusinessModuleTabs(request: $request),
            'activeBusinessModule' => $module,
        ]);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $module = $this->resolveModuleWithCommon($request['business_module']);
        $dateRange = $this->getDateRange(value: $request['date']);

        $filters = $this->getTransactionFilters(data: $request->all(), dateRange: $dateRange, module: $module);

        $summary = $this->loyaltyPointTransactionService->getTransactionSummary(filters: $filters);

        return Excel::download(new CustomerTransactionsExport(
            data: [
                'transactions' => $this->loyaltyPointTransactionService->getList(
                    searchValue: $request['searchValue'],
                    dataLimit: self::ALL,
                    relations: ['user'],
                    filters: $filters,
                ),
                'credit' => $summary['credit'],
                'debit' => $summary['debit'],
                'balance' => $summary['credit'] - $summary['debit'],
                'business_module' => $this->getModuleLabel(module: $module),
                'transaction_type' => $request['transaction_type'],
                'transaction_types' => $this->loyaltyPointTransactionService->getTransactionTypes(),
                'order_reference_types' => $this->loyaltyPointTransactionService->getOrderReferenceTypes(),
                'to' => $dateRange[1] ?? null,
                'from' => $dateRange[0] ?? null,
                'customer' => $this->getExportCustomer(customerId: $request['customer_id']),
            ],
            view: 'file-exports.loyalty-transactions',
        ), 'Loyalty-Transactions-Report.xlsx');
    }

    /** @return array<string, array{label: string, url: string, count: ?int}> */
    private function getBusinessModuleTabs(?Request $request): array
    {
        $dateRange = $this->getDateRange(value: $request['date']);
        $tabs = [];

        foreach (array_keys($this->getAvailableModulesWithCommon()) as $moduleKey) {
            $tabs[$moduleKey] = [
                'label' => $this->getModuleLabel(module: $moduleKey),
                'url' => route('admin.people.customer.loyalty.report', [
                    'business_module' => $moduleKey,
                    ...$request->except(['page', 'business_module']),
                ]),
                'count' => $this->loyaltyPointTransactionService->count(
                    filters: $this->getTransactionFilters(data: $request->all(), dateRange: $dateRange, module: $moduleKey),
                ),
            ];
        }

        return $tabs;
    }

    private function getModuleLabel(string $module): string
    {
        return match ($module) {
            BusinessModule::RETAIL => translate('Retail'),
            BusinessModule::WHOLESALE => translate('Wholesale'),
            default => translate('Common'),
        };
    }

    private function getOrderDetailsRouteName(string $module): string
    {
        return $module === BusinessModule::WHOLESALE
            ? 'admin.wholesale.orders.details'
            : 'admin.retail.orders.details';
    }

    private function getTransactionTypeBadges(): array
    {
        return [
            LoyaltyPointTransactionService::TRANSACTION_TYPE_ORDER_PLACE => 'info',
            LoyaltyPointTransactionService::TRANSACTION_TYPE_POINT_TO_WALLET => 'warning',
            LoyaltyPointTransactionService::TRANSACTION_TYPE_REFUND_ORDER => 'danger',
        ];
    }

    private function getFilterTransactionTypes(string $module): array
    {
        if ($module === BusinessModule::COMMON) {
            return [];
        }

        return array_intersect_key(
            $this->loyaltyPointTransactionService->getTransactionTypes(),
            array_flip($this->loyaltyPointTransactionService->getOrderReferenceTypes()),
        );
    }

    private function isFilterableTransactionType(?string $transactionType, string $module): bool
    {
        return ! empty($transactionType) && array_key_exists($transactionType, $this->getFilterTransactionTypes(module: $module));
    }

    private function hasActiveFilter(array $data, string $module): bool
    {
        return ! empty($data['date'])
            || $this->isFilterableTransactionType(transactionType: $data['transaction_type'] ?? null, module: $module)
            || (! empty($data['customer_id']) && $data['customer_id'] !== self::ALL);
    }

    private function getTransactionFilters(array $data, array $dateRange, string $module): array
    {
        $filters = [
            LoyaltyPointTransactionService::FILTER_CREATED_BETWEEN => $dateRange,
            'business_module' => $module,
        ];

        if ($this->isFilterableTransactionType(transactionType: $data['transaction_type'] ?? null, module: $module)) {
            $filters['transaction_type'] = $data['transaction_type'];
        }

        if (! empty($data['customer_id']) && $data['customer_id'] !== self::ALL) {
            $filters['user_id'] = $data['customer_id'];
        }

        return $filters;
    }

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
}
