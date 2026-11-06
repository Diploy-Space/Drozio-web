<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Enums\BusinessModule;
use App\Enums\WebConfigKey;
use App\Events\CustomerRegistrationEvent;
use App\Events\CustomerStatusUpdateEvent;
use App\Exports\CustomerListExport;
use App\Exports\CustomerOrderListExport;
use App\Exports\SubscriberListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Customer\AddRequest;
use App\Http\Requests\Admin\Customer\ProfileUpdateRequest;
use App\Http\Requests\Admin\Customer\SettingsUpdateRequest;
use App\Services\BusinessModule\BusinessModule as BusinessModuleDefinition;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Services\New\SubscriptionService;
use App\Utils\Convert;
use App\Utils\CustomerOrderListSections;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Auction\app\Services\New\AccountDeletionGuardService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends BaseController
{
    private const COMPANY_NAME_KEY = 'company_name';

    private const REFERRAL_DISCOUNT_SETTINGS_KEY = 'ref_earning_customer';

    private const REFERRAL_VALIDITY_DAY = 'day';

    private const REFERRAL_VALIDITY_WEEK = 'week';

    private const REFERRAL_VALIDITY_MONTH = 'month';

    private const ALL_CUSTOMER_OPTION_ID = 'all';

    private const WALKING_CUSTOMER_ID = 0;

    private const CUSTOMER_LIST_DATE_FORMAT = 'd M Y';

    private const SUBSCRIPTION_DATE_FORMAT = 'm/d/Y';

    /** @var array<int, string> */
    private const SETTING_KEYS = [
        'wallet_status',
        'add_funds_to_wallet',
        'minimum_add_fund_amount',
        'maximum_add_fund_amount',
        'ai_shopping_assistant_status',
        'loyalty_point_status',
        'loyalty_point_exchange_rate',
        'loyalty_point_minimum_point',
        'loyalty_point_for_each_order',
        'loyalty_point_item_purchase_point',
        'ref_earning_status',
        'ref_earning_exchange_rate',
        self::REFERRAL_DISCOUNT_SETTINGS_KEY,
    ];

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly OrderService $orderService,
        private readonly SubscriptionService $subscriptionService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly PasswordResetService $passwordResetService,
        private readonly ShippingAddressService $shippingAddressService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|RedirectResponse
    {
        $joiningRange = $this->dateRange(value: $request['customer_joining_date'], format: self::CUSTOMER_LIST_DATE_FORMAT);
        $orderRange = $this->dateRange(value: $request['order_date'], format: self::CUSTOMER_LIST_DATE_FORMAT);

        if ($joiningRange === false || $orderRange === false) {
            ToastMagic::error(translate('Invalid date range format'));

            return back();
        }

        $orderCountModules = $this->orderCountModules();

        $customers = $this->customerService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->paginationLimit(),
            withCount: $this->orderCountsByBusinessModule(modules: array_keys($orderCountModules)),
            filters: $this->customerListFilters($request, $joiningRange, $orderRange),
            sortBy: $request['sort_by'],
        )->appends($request->all());

        $overviewBusinessModule = $this->selectedOrderBusinessModule($request);
        $overviewFilters = array_filter([
            CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true,
            CustomerService::FILTER_ORDERED_IN_BUSINESS_MODULE => $overviewBusinessModule,
        ], fn (mixed $value): bool => $value !== null);

        $statusCounts = $this->customerService->getStatusCounts(filters: $overviewFilters);

        return view('admin-views.customer.list', [
            'customers' => $customers,
            'totalCustomers' => array_sum($statusCounts),
            'activeCustomers' => $statusCounts['active'],
            'blockedCustomers' => $statusCounts['inactive'],
            'newCustomersThisMonth' => $this->customerService->count(filters: [
                ...$overviewFilters,
                CustomerService::FILTER_JOINED_BETWEEN => [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ],
            ]),
            'overviewQuery' => $overviewBusinessModule === null ? [] : ['order_business_module' => $overviewBusinessModule],
            'hasActiveFilter' => $this->hasCustomerListFilter($request),
            'orderBusinessModules' => $this->orderBusinessModuleOptions(),
            'orderCountModules' => $orderCountModules,
        ]);
    }

    /** @return array<string, string> */
    private function orderCountModules(): array
    {
        return array_intersect_key([
            BusinessModule::RETAIL => translate('Retail'),
            BusinessModule::WHOLESALE => translate('Wholesale'),
        ], businessModuleManager()->availableInPanel());
    }

    /** @return array<string, callable> */
    private function orderCountsByBusinessModule(array $modules): array
    {
        $counts = [];

        foreach ($modules as $module) {
            $counts['orders as '.$module.'_orders_count'] = fn ($query) => $query->businessModule($module);
        }

        return $counts;
    }

    /** @return array<string, string> */
    private function orderBusinessModuleOptions(): array
    {
        return array_map(
            static fn (BusinessModuleDefinition $module): string => $module->label,
            businessModuleManager()->availableInPanel(),
        );
    }

    private function selectedOrderBusinessModule(Request $request): ?string
    {
        $businessModule = (string) $request['order_business_module'];

        return array_key_exists($businessModule, $this->orderBusinessModuleOptions()) ? $businessModule : null;
    }

    private function hasCustomerListFilter(Request $request): bool
    {
        return (bool) array_filter([
            $request['order_date'],
            $request['customer_joining_date'],
            $request['is_active'],
            $request['sort_by'],
            $request['choose_first'],
            $request['order_business_module'],
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $joiningRange = $this->dateRange(value: $request['customer_joining_date'], format: self::CUSTOMER_LIST_DATE_FORMAT) ?: [];
        $orderRange = $this->dateRange(value: $request['order_date'], format: self::CUSTOMER_LIST_DATE_FORMAT) ?: [];

        $customers = $this->customerService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['orders'],
            filters: $this->customerListFilters($request, $joiningRange, $orderRange),
            sortBy: $request['sort_by'],
        );

        return Excel::download(new CustomerListExport([
            'customers' => $customers,
            'status' => $request['is_active'] ?? '',
            'sortBy' => $request['sort_by'] ?? '',
            'chooseFirst' => $request['choose_first'] ?? '',
            'searchValue' => $request['searchValue'],
            'orderStartDate' => $orderRange[0] ?? '',
            'orderEndDate' => $orderRange[1] ?? '',
            'joiningStartDate' => $joiningRange[0] ?? '',
            'joiningEndDate' => $joiningRange[1] ?? '',
        ]), 'Customers.xlsx');
    }

    public function getView(Request $request, string $id): View|RedirectResponse
    {
        $customer = $this->customerService->find(id: $id, relations: ['addresses']);

        if ($customer === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $sortKey = $this->resolveOrderSortKey(sortKey: $request['sort']);

        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->paginationLimit(),
            page: $request['page'],
            relations: ['seller.shop', 'details'],
            filters: $this->customerOrderFilters(customerId: $id),
            sortBy: $sortKey,
        )->appends($request->except('page'));

        return view('admin-views.customer.customer-view', [
            'customer' => $customer,
            'customerModuleTabs' => customerDetailModuleTabs(customerId: $customer->id, activeModule: BusinessModule::RETAIL),
            'orders' => $orders,
            'itemPreviews' => $this->orderService->getItemPreviews(orders: $orders),
            'orderStatCards' => $this->orderStatCards(summary: $this->orderService->getStatusSummaryForCustomer(customerId: (int) $customer->id)),
            'orderStatusLabels' => CustomerOrderListSections::getStatusLabels(),
            'orderStatusBadges' => $this->orderStatusBadges(),
            'sortKey' => $sortKey,
            'sortLinks' => tableSortLinks(
                columns: $this->orderSortColumns(),
                activeSort: $sortKey,
                url: route('admin.people.customer.view', ['user_id' => $customer->id]),
                query: $request->query(),
            ),
            'searchValue' => $request['searchValue'],
            'exportUrl' => route('admin.people.customer.order-list-export', [
                'user_id' => $customer->id,
                'searchValue' => $request['searchValue'],
                'sort' => $sortKey,
            ]),
        ]);
    }

    public function exportOrderList(Request $request, string $id): BinaryFileResponse
    {
        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['seller.shop'],
            filters: $this->customerOrderFilters(customerId: $id),
            sortBy: $this->resolveOrderSortKey(sortKey: $request['sort']),
        );

        return Excel::download(new CustomerOrderListExport([
            'customer' => $this->customerService->find(id: $id),
            'searchValue' => $request['searchValue'],
            'orders' => $orders,
        ]), 'Customer-Order-List.xlsx');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $customer = $this->customerService->find(id: $request['id']);

        if ($customer === null) {
            return response()->json(['message' => translate('Not found')], 404);
        }

        $isActive = (bool) $request->get('is_active', 0);

        $this->customerService->updateStatus(id: $customer->id, status: $isActive);
        $this->customerService->deleteAuthAccessTokens(id: $customer->id);

        event(new CustomerStatusUpdateEvent(email: $customer['email'], data: [
            'userName' => $customer['f_name'],
            'userType' => 'customer',
            'templateName' => $isActive ? 'account-unblock' : 'account-block',
            'subject' => $isActive ? translate('Account Unblocked').' !' : translate('Account Blocked').' !',
            'title' => $isActive ? translate('Account Unblocked').' !' : translate('Account Blocked').' !',
        ]));

        return response()->json(['message' => translate('Update successfully')]);
    }

    public function deleteCustomer(string $id): RedirectResponse
    {
        $customer = $this->customerService->find(id: $id);

        if ($customer === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $auctionBlocker = $this->auctionDeletionBlocker(customerId: (int) $customer->id);

        if ($auctionBlocker !== null) {
            ToastMagic::error(translate($auctionBlocker));

            return back();
        }

        $this->customerService->delete(id: $customer->id);
        ToastMagic::success(translate('Customer deleted successfully'));

        return back();
    }

    public function add(AddRequest $request): JsonResponse
    {
        $this->passwordResetService->create(data: [
            'identity' => $request['phone'],
            'user_type' => PasswordResetService::USER_TYPE_CUSTOMER,
        ]);

        $customer = $this->customerService->create(data: $request->all());
        $this->shippingAddressService->create(data: $request->all() + ['customer_id' => $customer->id]);

        $companyName = $this->businessSettingService->getValueByType(type: self::COMPANY_NAME_KEY);

        event(new CustomerRegistrationEvent(email: $request['email'], data: [
            'userName' => $request['f_name'],
            'userType' => 'customer',
            'templateName' => 'registration-from-pos',
            'subject' => translate('Customer Registration Successfully Completed'),
            'title' => translate('Welcome to').' '.$companyName.'!',
            'resetPassword' => route('customer.auth.recover-password'),
            'message' => translate('Thank you for joining').' '.$companyName.'.'.translate('To become a registered customer, reset your password with this phone.').' '.($request['phone']).'.'.translate('Then explore the website and app as a registered customer.').'.',
        ]));

        return response()->json([
            'success' => true,
            'message' => translate(':item added successfully', ['item' => translate('Customer')]),
            'customerId' => $customer->id,
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->customerService->update(
            id: $request['id'],
            data: $request->all(),
            image: $request->file('image'),
        );
        ToastMagic::success(translate('Update successfully'));

        return back();
    }

    public function getCustomerList(Request $request): JsonResponse
    {
        $customers = $this->customerNameOptions($request);
        array_unshift($customers, ['id' => self::ALL_CUSTOMER_OPTION_ID, 'text' => 'All Customer']);

        return response()->json($customers);
    }

    public function getCustomerListWithoutAllCustomerName(Request $request): JsonResponse
    {
        return response()->json($this->customerNameOptions($request));
    }

    public function getSubscriberListView(Request $request): View|RedirectResponse
    {
        $subscriptionRange = $this->dateRange(value: $request['subscription_date'], format: self::SUBSCRIPTION_DATE_FORMAT);

        if ($subscriptionRange === false) {
            ToastMagic::error(translate('Invalid date range format'));

            return back();
        }

        $subscriberList = $this->subscriptionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: $this->paginationLimit(),
            filters: $this->subscriberListFilters($request, $subscriptionRange),
            sortBy: $request['sort_by'],
        )->appends($request->all());

        return view('admin-views.customer.subscriber-list', [
            'subscriberList' => $subscriberList,
            'totalSubscribers' => $this->subscriptionService->count(),
        ]);
    }

    public function exportSubscribersList(Request $request): BinaryFileResponse
    {
        $subscriptionRange = $this->dateRange(value: $request['subscription_date'], format: self::SUBSCRIPTION_DATE_FORMAT) ?: [];

        $subscriptionList = $this->subscriptionService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            filters: $this->subscriberListFilters($request, $subscriptionRange),
            sortBy: $request['sort_by'],
        );

        return Excel::download(new SubscriberListExport([
            'subscription' => $subscriptionList,
            'sortBy' => $request['sort_by'] ?? '',
            'chooseFirst' => $request['choose_first'] ?? '',
            'search' => $request['searchValue'],
            'startDate' => $subscriptionRange[0] ?? '',
            'endDate' => $subscriptionRange[1] ?? '',
        ]), 'Subscriber-list.xlsx');
    }

    public function getCustomerSettingsView(): View
    {
        return view('admin-views.customer.customer-settings', [
            ...$this->getCustomerSetup(),
            'isAiShoppingAssistantAvailable' => function_exists('getCheckAddonPublishedStatus')
                && getCheckAddonPublishedStatus(moduleName: 'AI'),
            'isDemoMode' => config('app.mode') === 'demo',
        ]);
    }

    public function updateCustomerSettings(SettingsUpdateRequest $request): RedirectResponse
    {
        if (config('app.mode') === 'demo') {
            ToastMagic::info(translate('Update option is disable for demo'));

            return back();
        }

        $this->updateCustomerSetup(data: $request->all())
            ? ToastMagic::success(translate(':item updated successfully', ['item' => translate('Customer settings')]))
            : ToastMagic::error(translate('Minimum amount cannot be greater than maximum amount'));

        return back();
    }

    /** @return array<string, mixed> */
    private function getCustomerSetup(): array
    {
        $settings = $this->storedCustomerSettings();

        return [
            'walletStatus' => (bool) $settings['wallet_status'],
            'addFundsToWallet' => (bool) $settings['add_funds_to_wallet'],
            'minimumAddFundAmount' => Convert::default($settings['minimum_add_fund_amount'] ?? 0),
            'maximumAddFundAmount' => Convert::default($settings['maximum_add_fund_amount'] ?? 0),
            'aiShoppingAssistantStatus' => (bool) $settings['ai_shopping_assistant_status'],
            'loyaltyPointStatus' => (bool) $settings['loyalty_point_status'],
            'loyaltyPointExchangeRate' => $settings['loyalty_point_exchange_rate'] ?? 0,
            'loyaltyPointMinimumPoint' => $settings['loyalty_point_minimum_point'] ?? 0,
            'loyaltyPointForEachOrder' => (bool) $settings['loyalty_point_for_each_order'],
            'loyaltyPointItemPurchasePoint' => $settings['loyalty_point_item_purchase_point'] ?? 1,
            'refEarningStatus' => (bool) $settings['ref_earning_status'],
            'refEarningExchangeRate' => Convert::default($settings['ref_earning_exchange_rate'] ?? 0),
            'referralDiscount' => json_decode((string) $settings[self::REFERRAL_DISCOUNT_SETTINGS_KEY], true) ?? [],
            'referralValidityTypes' => $this->getReferralValidityTypes(),
        ];
    }

    /** @return array<string, string> */
    private function getReferralValidityTypes(): array
    {
        return [
            self::REFERRAL_VALIDITY_DAY => translate('Day'),
            self::REFERRAL_VALIDITY_WEEK => translate('Week'),
            self::REFERRAL_VALIDITY_MONTH => translate('Month'),
        ];
    }

    /** @param array<string, mixed> $data */
    private function updateCustomerSetup(array $data): bool
    {
        $settings = $this->customerSetupSettings(data: $data);

        if ($settings === false) {
            return false;
        }

        DB::transaction(function () use ($settings): void {
            foreach ($settings as $type => $value) {
                $this->businessSettingService->updateOrInsert(params: ['type' => $type], data: ['value' => $value]);
            }
        });

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|false
     */
    private function customerSetupSettings(array $data): array|false
    {
        $hasAddFundRange = isset($data['minimum_add_fund_amount'], $data['maximum_add_fund_amount']);

        if ($hasAddFundRange && $data['maximum_add_fund_amount'] <= $data['minimum_add_fund_amount']) {
            return false;
        }

        $stored = $this->storedCustomerSettings();

        $settings = [
            'wallet_status' => $data['customer_wallet'] ?? 0,
            'add_funds_to_wallet' => $data['add_funds_to_wallet'] ?? 0,
            'ai_shopping_assistant_status' => $data['ai_shopping_assistant_status'] ?? 0,
            'loyalty_point_status' => $data['customer_loyalty_point'] ?? 0,
            'loyalty_point_for_each_order' => $data['loyalty_point_for_each_order'] ?? 0,
            'loyalty_point_exchange_rate' => $data['loyalty_point_exchange_rate'] ?? $stored['loyalty_point_exchange_rate'],
            'loyalty_point_item_purchase_point' => $data['item_purchase_point'] ?? $stored['loyalty_point_item_purchase_point'],
            'loyalty_point_minimum_point' => $data['minimum_transfer_point'] ?? $stored['loyalty_point_minimum_point'],
            'ref_earning_status' => $data['ref_earning_status'] ?? 0,
            'ref_earning_exchange_rate' => currencyConverter(amount: $data['ref_earning_exchange_rate'] ?? $stored['ref_earning_exchange_rate']),
            self::REFERRAL_DISCOUNT_SETTINGS_KEY => json_encode([
                'ref_earning_discount_status' => $data['ref_earning_discount_status'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? '',
                'validity' => $data['validity'] ?? 0,
                'validity_type' => $data['validity_type'] ?? '',
            ]),
        ];

        if ($hasAddFundRange) {
            $settings['minimum_add_fund_amount'] = currencyConverter(amount: $data['minimum_add_fund_amount']);
            $settings['maximum_add_fund_amount'] = currencyConverter(amount: $data['maximum_add_fund_amount']);
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    private function storedCustomerSettings(): array
    {
        $values = $this->businessSettingService
            ->getList(dataLimit: 'all', filters: ['type' => self::SETTING_KEYS])
            ->pluck('value', 'type')
            ->all();

        return $values + array_fill_keys(self::SETTING_KEYS, null);
    }

    /** @return array<int, array<string, mixed>> */
    private function customerNameOptions(Request $request): array
    {
        $customers = $this->customerService->getList(
            searchValue: $request['searchValue'],
            page: $request['page'] ? (int) $request['page'] : null,
            filters: [
                CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => (bool) ($request['avoid_walking_customer'] ?? false),
            ],
        );

        return collect($customers->all())
            ->map(fn (Model $customer): array => [
                'id' => $customer->id,
                'text' => $this->customerOptionLabel($customer),
            ])
            ->all();
    }

    private function customerOptionLabel(Model $customer): string
    {
        $name = $customer->f_name.' '.($customer->l_name ?? '');

        return (int) $customer->id === self::WALKING_CUSTOMER_ID ? $name : $name.' ('.$customer->phone.')';
    }

    /** @return array<string, mixed> */
    private function customerOrderFilters(string $customerId): array
    {
        return ['customer_id' => $customerId, 'is_guest' => 0];
    }

    private function resolveOrderSortKey(mixed $sortKey): string
    {
        return in_array($sortKey, OrderService::COLUMN_SORTS, true) ? $sortKey : OrderService::SORT_NEWEST;
    }

    /** @return array<string, array{label: string, ascending: string, descending: string}> */
    private function orderSortColumns(): array
    {
        return [
            'order_id' => ['label' => translate('Order info'), 'ascending' => OrderService::SORT_ORDER_ID_ASC, 'descending' => OrderService::SORT_ORDER_ID_DESC],
            'order_date' => ['label' => translate('Order date'), 'ascending' => OrderService::SORT_ORDER_DATE_ASC, 'descending' => OrderService::SORT_ORDER_DATE_DESC],
            'store_name' => ['label' => translate('Store'), 'ascending' => OrderService::SORT_STORE_NAME_ASC, 'descending' => OrderService::SORT_STORE_NAME_DESC],
            'order_amount' => ['label' => translate('Total amount'), 'ascending' => OrderService::SORT_ORDER_AMOUNT_ASC, 'descending' => OrderService::SORT_ORDER_AMOUNT_DESC],
            'order_status' => ['label' => translate('Order status'), 'ascending' => OrderService::SORT_ORDER_STATUS_ASC, 'descending' => OrderService::SORT_ORDER_STATUS_DESC],
        ];
    }

    /** @return array<int, array{label: string, icon: string, count: int}> */
    private function orderStatCards(array $summary): array
    {
        return [
            ['label' => translate('Total Orders'), 'icon' => 'total-order.png', 'count' => $summary['total_order']],
            ['label' => translate('Ongoing'), 'icon' => 'ongoing.png', 'count' => $summary['ongoing']],
            ['label' => translate('Delivered'), 'icon' => 'completed.png', 'count' => $summary['completed']],
            ['label' => translate('Canceled'), 'icon' => 'canceled.png', 'count' => $summary['canceled']],
            ['label' => translate('Returned'), 'icon' => 'returned.png', 'count' => $summary['returned']],
        ];
    }

    /** @return array<string, string> */
    private function orderStatusBadges(): array
    {
        $badges = [];

        foreach (array_keys(CustomerOrderListSections::getStatusLabels()) as $orderStatus) {
            $badges[$orderStatus] = 'badge-soft-'.CustomerOrderListSections::getStatusTone(orderStatus: $orderStatus);
        }

        return $badges;
    }

    /**
     * @param  array<int, Carbon>  $joiningRange
     * @param  array<int, Carbon>  $orderRange
     * @return array<string, mixed>
     */
    private function customerListFilters(Request $request, array $joiningRange, array $orderRange): array
    {
        $filters = [
            CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true,
            CustomerService::FILTER_JOINED_BETWEEN => $joiningRange,
            CustomerService::FILTER_ORDERED_BETWEEN => $orderRange,
        ];

        if (in_array($request['is_active'], ['0', '1'], true)) {
            $filters['is_active'] = (int) $request['is_active'];
        }

        if ($request['choose_first']) {
            $filters[CustomerService::FILTER_LIMIT] = (int) $request['choose_first'];
        }

        if ($orderBusinessModule = $this->selectedOrderBusinessModule($request)) {
            $filters[CustomerService::FILTER_ORDERED_IN_BUSINESS_MODULE] = $orderBusinessModule;
        }

        return $filters;
    }

    /**
     * @param  array<int, Carbon>  $subscriptionRange
     * @return array<string, mixed>
     */
    private function subscriberListFilters(Request $request, array $subscriptionRange): array
    {
        $filters = [SubscriptionService::FILTER_SUBSCRIBED_BETWEEN => $subscriptionRange];

        if ($request['choose_first']) {
            $filters[SubscriptionService::FILTER_LIMIT] = (int) $request['choose_first'];
        }

        return $filters;
    }

    /** @return array<int, Carbon>|false */
    private function dateRange(?string $value, string $format): array|false
    {
        if (empty($value)) {
            return [];
        }

        $dates = explode(' - ', $value);

        if (count($dates) !== 2 || ! Carbon::canBeCreatedFromFormat($dates[0], $format) || ! Carbon::canBeCreatedFromFormat($dates[1], $format)) {
            return false;
        }

        return [
            Carbon::createFromFormat($format, $dates[0])->startOfDay(),
            Carbon::createFromFormat($format, $dates[1])->endOfDay(),
        ];
    }

    private function paginationLimit(): int|string
    {
        return $this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT;
    }

    private function auctionDeletionBlocker(int $customerId): ?string
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return null;
        }

        return app(AccountDeletionGuardService::class)->blockingReason($customerId);
    }
}
