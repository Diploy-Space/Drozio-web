<?php

namespace App\Http\Controllers\Admin\Order;

use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Events\OrderEditReturnPaymentEvent;
use App\Exports\OrderExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Order\ReturnAmountRequest;
use App\Http\Requests\Shared\Order\AddressUpdateRequest;
use App\Http\Requests\Shared\Order\DeliveryManAssignRequest;
use App\Http\Requests\Shared\Order\DeliveryScheduleUpdateRequest;
use App\Http\Requests\Shared\Order\DigitalFileUploadAfterSellRequest;
use App\Http\Requests\Shared\Order\DueAmountMarkAsPaidRequest;
use App\Http\Requests\Shared\Order\DueAmountSwitchToCODRequest;
use App\Http\Requests\Shared\Order\PaymentStatusUpdateRequest;
use App\Http\Requests\Shared\Order\StatusUpdateRequest;
use App\Services\New\AdminWalletService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderEditService;
use App\Services\New\OrderService;
use App\Services\New\OrderSettlementService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\ShippingAddressService;
use App\Services\New\VendorService;
use App\Services\New\WalletTransactionService;
use App\Traits\OrderEditSessionTrait;
use App\Traits\OrderEditSummaryTrait;
use App\Traits\PdfGenerator;
use App\Utils\OrderManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as PdfView;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends BaseController
{
    private const PLACEHOLDER_ORDER_GROUP = 'def-order-group';

    private const FILTER_OPTION_ALL = 'all';

    private const IN_HOUSE_SELLER_FILTER = '0';

    private const IN_HOUSE_SELLER_ID = 1;

    private const ACTOR_TYPE = 'admin';

    private const ACTOR_ID = 0;

    private const LOYALTY_POINT_TRANSACTION_TYPE = 'order_place';

    private const DELIVERYMAN_CHARGE_TRANSACTION_TYPE = 'deliveryman_charge';

    private const NOTIFICATION_DELIVERY_MAN_CHARGE = 'delivery_man_charge';

    private const NOTIFICATION_DELIVERY_MAN_ASSIGNED_BY_ADMIN = 'delivery_man_assign_by_admin_message';

    private const NOTIFICATION_EDIT_RETURN_AMOUNT = 'order_edit_return_amount_message';

    private const WALLET_REFERENCE_ADD_WALLET_AMOUNT = 'add_wallet_amount';

    private const DUE_PAYMENT_NOTE_SWITCHED_TO_CASH_ON_DELIVERY = 'Switched to COD by admin';

    private const DUE_PAYMENT_NOTE_MARKED_AS_PAID = 'Marked as paid by admin';

    use OrderEditSessionTrait;
    use OrderEditSummaryTrait;
    use PdfGenerator;

    public function __construct(
        private readonly OrderSettlementService $orderSettlementService,
        private readonly CustomerService $customerService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly DeliveryManService $deliveryManService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ProductRetailService $retailProductService,
        private readonly OrderEditService $orderEditService,
        private readonly OrderService $orderService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly AdminWalletService $adminWalletService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    public function index(?Request $request, ?string $type = 'all'): View|JsonResponse
    {
        $status = $type;
        $searchValue = $request['searchValue'];
        $filter = $request['filter'];
        $from = $request['from'];
        $to = $request['to'];
        $dateType = $request['date_type'];
        $paymentPaidStatus = $request['payment_status'] ?? [];
        $orderStatus = $request['order_current_status'] ?? [];
        $orderTypes = $request['order_types'] ?? [];

        $this->orderService->updateWhere(params: ['checked' => 0], data: ['checked' => 1]);

        $listFilters = $this->buildListFilters(request: $request, status: $status);

        $statusCounts = $this->orderService->getStatusCounts(
            filters: array_diff_key($listFilters, [OrderService::FILTER_SOURCE => null]),
        );

        $allOrdersInfo = [
            'pending_order' => $statusCounts['pending'] ?? 0,
            'confirmed_order' => $statusCounts['confirmed'] ?? 0,
            'processing_order' => $statusCounts['processing'] ?? 0,
            'out_for_delivery_order' => $statusCounts['out_for_delivery'] ?? 0,
            'delivered_order' => $statusCounts['delivered'] ?? 0,
            'canceled_order' => $statusCounts['canceled'] ?? 0,
            'returned_order' => $statusCounts['returned'] ?? 0,
            'failed_order' => $statusCounts['failed'] ?? 0,
        ];

        $isAjaxListRequest = $request->ajax() && ! $request->hasHeader('X-PJAX');

        $orders = $this->orderService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: [
                'customer',
                'seller.shop',
                'latestEditHistory' => fn ($query) => $query->select([
                    'id', 'order_id', 'order_due_payment_method', 'order_due_amount',
                    'order_due_payment_status', 'order_return_amount', 'order_return_payment_status',
                ]),
                ...($isAjaxListRequest ? [] : ['details']),
            ],
            filters: $listFilters,
            sortBy: OrderService::SORT_NEWEST,
        );

        if ($isAjaxListRequest) {
            return response()->json(['orders' => $orders]);
        }

        $sellers = $this->vendorService->getList(
            dataLimit: 'all',
            relations: ['shop'],
            filters: [VendorService::FILTER_STATUS_EXCEPT => VendorService::STATUS_PENDING],
        );

        $customers = $this->customerService->getNameOptions(searchValue: $searchValue, dataLimit: 'all')->toArray();
        array_unshift($customers, ['id' => 'all', 'text' => translate('All Customer')]);

        $vendorId = $request['seller_id'];
        $customerId = $request['customer_id'];
        $orderTotalPriceSummaries = $this->orderService->getTotalPriceSummaries(orders: $orders);
        $itemPreviews = $this->orderService->getItemPreviews(orders: $orders);
        $inHouseShopName = getInHouseShopConfig(key: 'name');

        $exportQuery = [
            'delivery_man_id' => $request['delivery_man_id'],
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'filter' => $filter,
            'searchValue' => $searchValue,
            'seller_id' => $vendorId,
            'customer_id' => $customerId,
            'date_type' => $dateType,
            'payment_status' => $request['payment_status'],
            'order_current_status' => $request['order_current_status'],
            'order_types' => $request['order_types'],
            'order_amount_settlement' => $request['order_amount_settlement'],
        ];

        $filters = [
            'delivery_man_id' => $request['delivery_man_id'],
            'has_delivery_man_id' => $request->has('delivery_man_id'),
            'seller_id' => $vendorId,
            'customer_id' => $customerId ?: self::FILTER_OPTION_ALL,
            'selected_customer_id' => $customerId ?? self::FILTER_OPTION_ALL,
            'order_amount_settlement' => $request->input('order_amount_settlement', []),
        ];

        $hasActiveFilters = $this->hasActiveListFilters(request: $request, status: $status);

        return view('admin-views.order.list', compact(
            'orders',
            'searchValue',
            'from',
            'to',
            'status',
            'filter',
            'sellers',
            'vendorId',
            'customerId',
            'dateType',
            'allOrdersInfo',
            'paymentPaidStatus',
            'orderStatus',
            'customers',
            'orderTypes',
            'orderTotalPriceSummaries',
            'itemPreviews',
            'inHouseShopName',
            'exportQuery',
            'filters',
            'hasActiveFilters',
        ));
    }

    private function hasActiveListFilters(Request $request, ?string $status): bool
    {
        $dateType = $request['date_type'];
        $source = $request['filter'];
        $sellerId = $request['seller_id'];
        $customerId = $request['customer_id'];

        $offcanvasSelections = [
            $dateType === self::FILTER_OPTION_ALL ? null : $dateType,
            $request['from'],
            $request['to'],
            $source === self::FILTER_OPTION_ALL ? null : $source,
            $request['order_types'],
            $status === self::FILTER_OPTION_ALL ? $request['order_current_status'] : null,
            $request['payment_status'],
            $request->input('order_amount_settlement', []),
            $sellerId === self::FILTER_OPTION_ALL ? null : $sellerId,
            $customerId === self::FILTER_OPTION_ALL ? null : $customerId,
        ];

        return array_filter(
            $offcanvasSelections,
            static fn ($value) => $value !== null && $value !== '' && $value !== [],
        ) !== [];
    }

    private function buildListFilters(Request $request, ?string $status): array
    {
        $sellerId = $request['seller_id'];
        $sellerIs = match (true) {
            $sellerId == null, $sellerId == self::FILTER_OPTION_ALL => null,
            $sellerId == self::IN_HOUSE_SELLER_FILTER => OrderService::SELLER_IS_ADMIN,
            default => OrderService::SELLER_IS_SELLER,
        };
        $source = $request['filter'] ?? self::FILTER_OPTION_ALL;

        return array_filter([
            'order_status' => $status === self::FILTER_OPTION_ALL ? ($request['order_current_status'] ?? []) : $status,
            'payment_status' => $request['payment_status'] ?? [],
            'order_type' => $request['order_types'] ?? [],
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'] === self::FILTER_OPTION_ALL ? null : $request['customer_id'],
            'seller_id' => match (true) {
                $sellerId == self::IN_HOUSE_SELLER_FILTER => self::IN_HOUSE_SELLER_ID,
                $sellerId === self::FILTER_OPTION_ALL => null,
                default => $sellerId,
            },
            'seller_is' => $sellerIs,
            OrderService::FILTER_SOURCE => $source === self::FILTER_OPTION_ALL ? null : $source,
            OrderService::FILTER_DATE_TYPE => $request['date_type'],
            OrderService::FILTER_DATE_RANGE => $request['from'] && $request['to'] ? [$request['from'], $request['to']] : [],
            OrderService::FILTER_SETTLEMENT => $request->input('order_amount_settlement', []),
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function getExportFilterCriteria(Request $request, string $status): array
    {
        $criteria = [];

        if ($status === self::FILTER_OPTION_ALL) {
            $criteria[translate('Order Status')] = $this->getSelectedFilterLabels(values: $request['order_current_status'], labels: [
                'pending' => translate('Pending'),
                'confirmed' => translate('Confirmed'),
                'processing' => translate('Packaging'),
                'out_for_delivery' => translate('Out For Delivery'),
                'delivered' => translate('Delivered'),
                'canceled' => translate('Canceled'),
                'returned' => translate('Returned'),
                'failed' => translate('Failed to deliver'),
            ]);
        }

        return [
            ...$criteria,
            translate('Payment Status') => $this->getSelectedFilterLabels(values: $request['payment_status'], labels: [
                'paid' => translate('Paid'),
                'unpaid' => translate('Unpaid'),
            ]),
            translate('Order Types') => $this->getSelectedFilterLabels(values: $request['order_types'], labels: [
                'pos' => translate('Only POS Orders'),
                'default_type' => translate('Only Website Orders'),
            ]),
            translate('Edit Order Amount Settlement') => $this->getSelectedFilterLabels(values: $request['order_amount_settlement'], labels: [
                OrderService::SETTLEMENT_RETURN => translate('Has Return Amount'),
                OrderService::SETTLEMENT_DUE => translate('Has due Amount'),
            ]),
        ];
    }

    private function getSelectedFilterLabels(mixed $values, array $labels): string
    {
        $selectedLabels = array_intersect_key($labels, array_flip(array_filter((array) $values, 'is_string')));

        return $selectedLabels === [] ? translate('All') : implode(', ', $selectedLabels);
    }

    public function orderReturnAmountToCustomer(ReturnAmountRequest $request): RedirectResponse
    {
        $order = DB::transaction(fn (): Model|string => $this->returnEditAmount(
            orderId: $request['order_id'],
            paymentMethod: $request['order_return_payment_method'],
            paymentNote: $request['order_return_payment_note'],
        ));

        if (is_string($order)) {
            ToastMagic::error($order);

            return redirect()->back();
        }

        if (! $order['is_guest']) {
            event(new OrderEditReturnPaymentEvent(notification: (object) [
                'key' => self::NOTIFICATION_EDIT_RETURN_AMOUNT,
                'type' => OrderService::NOTIFICATION_TYPE_CUSTOMER,
                'order' => $order,
            ]));
        }

        ToastMagic::success(translate('Amount returned successfully'));

        return redirect()->back();
    }

    private function returnEditAmount(int|string $orderId, string $paymentMethod, string $paymentNote): Model|string
    {
        $order = $this->orderService->lockForEditSettlement(id: $orderId);

        if ($order === null) {
            return translate('Not found');
        }

        $latestEditHistory = $this->orderEditHistoryService->findLatestForOrder(orderId: $order['id']);
        $refusal = $this->getEditSettlementRefusal(reason: $this->orderService->getEditReturnSettlementBlocker(order: $order, latestEditHistory: $latestEditHistory));

        if ($refusal !== null) {
            return $refusal;
        }

        $returnAmount = $order['edit_return_amount'];

        if ($paymentMethod === OrderEditHistoryService::RETURN_PAYMENT_METHOD_WALLET && ! $this->creditCustomerWallet(
            customerId: $order['customer_id'],
            amount: (float) usdToDefaultCurrency(amount: (float) $returnAmount),
            transactionType: WalletTransactionService::TRANSACTION_TYPE_RETURN_ORDER_AMOUNT_BY_ADMIN,
            reference: self::WALLET_REFERENCE_ADD_WALLET_AMOUNT,
            paymentMethod: OrderEditHistoryService::RETURN_PAYMENT_METHOD_WALLET,
        )) {
            return translate('Failed to return amount');
        }

        if ($this->orderService->isPaymentHeldInPendingWallet(order: $order)) {
            $this->adminWalletService->decrementInHousePendingAmount(amount: $returnAmount);
        }

        $this->orderEditHistoryService->markReturnPaymentReturned(
            id: $latestEditHistory['id'],
            returnAmount: $returnAmount,
            paymentMethod: $paymentMethod,
            paymentNote: $paymentNote,
            editor: $this->getEditor(),
        );
        $this->orderService->updateWhere(params: ['id' => $order['id']], data: [
            'order_amount' => $order['order_amount'] - $returnAmount,
            'edit_return_amount' => 0,
        ]);

        return $order;
    }

    public function orderDueAmountSwitchToCOD(DueAmountSwitchToCODRequest $request): RedirectResponse
    {
        $order = $this->orderService->findBy(params: ['id' => $request['order_id']], relations: []);
        $latestEditHistory = $order === null ? null : $this->orderEditHistoryService->findLatestForOrder(orderId: $order['id']);
        $refusal = $order === null
            ? translate('Not found')
            : $this->getEditSettlementRefusal(reason: $this->orderService->getEditDueCashOnDeliverySwitchBlocker(
                order: $order,
                latestEditHistory: $latestEditHistory,
                dueAmount: $request['order_due_amount'],
            ));

        if ($refusal !== null) {
            ToastMagic::error($refusal);

            return redirect()->back();
        }

        $this->orderEditHistoryService->updateDuePaymentMethod(
            id: $latestEditHistory['id'],
            paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_CASH_ON_DELIVERY,
            paymentNote: self::DUE_PAYMENT_NOTE_SWITCHED_TO_CASH_ON_DELIVERY,
            editor: $this->getEditor(),
        );
        ToastMagic::success(translate('Switched to COD successfully'));

        return redirect()->back();
    }

    public function orderDueAmountMarkAsPaid(DueAmountMarkAsPaidRequest $request): RedirectResponse
    {
        $refusal = DB::transaction(function () use ($request): ?string {
            $order = $this->orderService->lockForEditSettlement(id: $request['order_id']);

            if ($order === null) {
                return translate('Not found');
            }

            $latestEditHistory = $this->orderEditHistoryService->findLatestForOrder(orderId: $order['id']);

            if (! $this->orderService->canMarkEditDueAsPaid(order: $order, latestEditHistory: $latestEditHistory)) {
                return translate('Order already paid');
            }

            $this->orderService->settleEditDueAmount(order: $order);
            $this->orderEditHistoryService->markDuePaidByEditor(
                id: $latestEditHistory['id'],
                paymentMethod: $latestEditHistory['order_due_payment_method'],
                paymentNote: self::DUE_PAYMENT_NOTE_MARKED_AS_PAID,
                editor: $this->getEditor(),
            );

            return null;
        });

        if ($refusal !== null) {
            ToastMagic::error($refusal);

            return back();
        }

        ToastMagic::success(translate('Mark as paid successfully'));

        return redirect()->back();
    }

    private function getEditSettlementRefusal(?string $reason): ?string
    {
        return match ($reason) {
            null => null,
            OrderService::REASON_ALREADY_DELIVERED => translate('Order is already delivered.'),
            OrderService::REASON_EDIT_RETURN_NOT_PENDING => translate('There is no pending return amount for this order'),
            OrderService::REASON_EDIT_DUE_AMOUNT_MISMATCH => translate('The entered due amount must match the order due amount'),
            OrderService::REASON_EDIT_HISTORY_ABSENT => translate('No edit history found'),
            OrderService::REASON_EDIT_DUE_NOT_UNPAID => translate('Order already paid'),
            OrderService::REASON_EDIT_DUE_ALREADY_CASH_ON_DELIVERY => translate('The due amount is already set to cash on delivery'),
        };
    }

    /** @return array{edit_by: string, edited_user_id: int|string|null, edited_user_name: ?string} */
    private function getEditor(): array
    {
        return [
            'edit_by' => self::ACTOR_TYPE,
            'edited_user_id' => auth('admin')->id(),
            'edited_user_name' => auth('admin')->user()?->name,
        ];
    }

    public function exportList(Request $request, string $status): BinaryFileResponse
    {
        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['customer', 'seller.shop', 'details'],
            filters: $this->buildListFilters(request: $request, status: $status),
            sortBy: OrderService::SORT_NEWEST,
        );

        $dateType = $request['date_type'] ?? '';
        $dateRange = $this->orderService->getExportDateRange(dateType: $dateType, from: $request['from'], to: $request['to']);

        $seller = [];
        if ($request['seller_id'] != 'all' && $request->has('seller_id') && $request->seller_id != 0) {
            $seller = $this->vendorService->findBy(params: ['id' => $request['seller_id']]);
        }
        $customer = [];
        if ($request['customer_id'] != 'all' && $request->has('customer_id')) {
            $customer = $this->customerService->findBy(params: ['id' => $request['customer_id']]);
        }

        $data = [
            'data-from' => 'admin',
            'orders' => $orders,
            'order_summaries' => $this->orderService->getTotalPriceSummaries(orders: $orders),
            'order_status' => $status,
            'seller' => $seller,
            'store_name' => match (true) {
                $request['seller_id'] === self::IN_HOUSE_SELLER_FILTER => getInHouseShopConfig(key: 'name'),
                (bool) $seller => $seller->shop?->name,
                default => null,
            },
            'customer' => $customer,
            'status_array' => $this->orderService->getStatusTally(orders: $orders),
            'searchValue' => $request['searchValue'],
            'order_type' => $request['filter'] ?? 'all',
            'from' => $dateRange['from'],
            'to' => $dateRange['to'],
            'date_type' => $dateType,
            'filter_criteria' => $this->getExportFilterCriteria(request: $request, status: $status),
            'defaultCurrencyCode' => getCurrencyCode(),
        ];

        return Excel::download(new OrderExport($data), 'Orders.xlsx');
    }

    public function getView(string|int $id): View|RedirectResponse
    {
        $order = $this->orderService->findBy(
            params: ['id' => $id],
            relations: [
                'details' => fn ($query) => $query->with(['product', 'productAllStatus', 'storage']),
                'latestEditHistory',
                'verificationImages',
                'shipping',
                'seller.shop',
                'offlinePayments',
                'deliveryMan',
                'orderEditHistory' => fn ($query) => $query->orderBy('id', 'desc'),
                'orderStatusHistory' => fn ($query) => $query->orderBy('created_at', 'desc'),
            ]
        );

        if (! $order) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.orders.list', ['status' => 'all']);
        }

        if ($order['init_order_amount'] <= 0) {
            $this->orderService->syncInitialOrderAmount(order: $order);
        }

        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');
        $deliveryCountry = $this->deliveryCountryCodeService->getList(dataLimit: 'all');
        $countries = $countryRestrictStatus ? $this->deliveryCountryCodeService->getDeliveryCountryArray(deliveryCountryCodes: $deliveryCountry) : GlobalConstant::COUNTRIES;
        $zipCodes = $zipRestrictStatus ? $this->deliveryZipCodeService->getList(dataLimit: 'all') : 0;
        $companyName = getWebConfig(name: 'company_name');
        $companyWebLogo = getWebConfig(name: 'company_web_logo');

        $physicalProduct = false;
        foreach ($order->details ?? [] as $orderDetail) {
            $orderDetailProduct = json_decode($orderDetail?->product_details, true);
            if (
                (isset($orderDetail?->product?->product_type) && $orderDetail->product->product_type === 'physical') ||
                (isset($orderDetailProduct['product_type']) && $orderDetailProduct['product_type'] === 'physical')
            ) {
                $physicalProduct = true;
                break;
            }
        }
        $linkedOrders = $order['order_group_id'] === self::PLACEHOLDER_ORDER_GROUP
            ? collect()
            : $this->orderService->getList(
                dataLimit: 'all',
                filters: [
                    'order_group_id' => $order['order_group_id'],
                    OrderService::FILTER_ID_EXCEPT => [$order['id']],
                ],
            );

        $totalDelivered = $this->orderService->count(filters: [
            'seller_id' => $order['seller_id'],
            'order_status' => OrderService::STATUS_DELIVERED,
        ]);
        $deliveryMen = $this->deliveryManService->getList(
            dataLimit: 'all',
            filters: ['is_active' => 1, 'seller_id' => $this->orderService->getDeliveryManOwnerId(order: $order)],
        );
        $isOrderOnlyDigital = $this->orderService->isOnlyDigital(order: $order);
        $previousOrder = $this->orderService->getList(
            dataLimit: 1,
            filters: [OrderService::FILTER_ID_BEFORE => $id],
            sortBy: OrderService::SORT_NEWEST,
        )->first();
        $nextOrder = $this->orderService->getList(
            dataLimit: 1,
            filters: [OrderService::FILTER_ID_AFTER => $id],
            sortBy: OrderService::SORT_OLDEST,
        )->first();
        $relations = [
            'brand',
            'category',
            'seller.shop',
        ];
        $allProductsList = $this->retailProductService->getList(
            dataLimit: 10,
            relations: $relations,
            filters: $this->orderEditService->getEditableProductFilters(order: $order) + [ProductRetailService::FILTER_ACTIVE => true],
            sortBy: ProductRetailService::SORT_OLDEST,
        );
        $isOrderEditable = $this->orderEditService->checkIsOrderEditable(order: $order, type: OrderEditService::TYPE_ADMIN);
        Session::forget($this->orderEditService->getOrderEditSessionKey(owner: $this->orderEditSessionOwner(), orderId: $order['id']));

        $orderProductsSession = ['product_list' => $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order)];
        $editOrderSummary = $this->getEditOrderSummary(order: $order, editedProducts: $orderProductsSession['product_list'], editor: [
            'edit_by' => OrderService::EDITOR_ADMIN,
            'edited_user_id' => auth()->guard('admin')->id(),
            'edited_user_name' => auth()->guard('admin')->user()->name,
        ]);
        $editProductQuantities = $this->orderEditService->getProductListQuantityBounds(productList: $orderProductsSession['product_list']);

        $orderEditPaymentHistory = $this->orderEditHistoryService->getList(
            filters: ['order_id' => $order['id']],
            dataLimit: 'all'
        );
        $orderCount = $this->orderService->count(filters: ['customer_id' => $order['customer_id']]);
        $courierPartnerAssigned = deliveryPartnerAssigned(order: $order);

        $viewData = compact(
            'order', 'linkedOrders', 'deliveryMen', 'totalDelivered',
            'companyName', 'companyWebLogo', 'physicalProduct',
            'countryRestrictStatus', 'zipRestrictStatus', 'countries', 'zipCodes',
            'orderCount', 'isOrderOnlyDigital', 'previousOrder', 'nextOrder',
            'allProductsList', 'isOrderEditable', 'orderProductsSession', 'editProductQuantities',
            'editOrderSummary', 'orderEditPaymentHistory', 'courierPartnerAssigned'
        );

        if ($order['order_type'] !== OrderService::ORDER_TYPE_DEFAULT) {
            return view('admin-views.pos.order.order-details', $viewData);
        }

        return view('admin-views.order.order-details', [
            ...$viewData,
            ...$this->getDetailsDisplayData(order: $order, physicalProduct: $physicalProduct, orderEditPaymentHistory: $orderEditPaymentHistory),
        ]);
    }

    private function getDetailsDisplayData(Model $order, bool $physicalProduct, iterable $orderEditPaymentHistory): array
    {
        $shippingAddress = $order['shipping_address_data'] ?? null;
        $isInHouseOrder = $order['seller_is'] === OrderService::SELLER_IS_ADMIN;

        return [
            'shippingAddress' => $shippingAddress,
            'billing' => $this->orderService->getBillingAddressData(order: $order),
            'isBillingSameAsShipping' => $this->orderService->isBillingSameAsShipping(order: $order),
            'orderVerificationStatus' => getWebConfig(name: 'order_verification'),
            'mapApiStatus' => getWebConfig(name: 'map_api_status'),
            'mapApiKey' => getWebConfig(name: 'map_api_key'),
            'inHouseShopName' => $isInHouseOrder ? getInHouseShopConfig(key: 'name') : null,
            'inHouseShopImage' => $isInHouseOrder ? getInHouseShopConfig(key: 'image_full_url') : null,
            'orderTotalPriceSummary' => OrderManager::getOrderTotalPriceSummary(order: $order),
            'detailDisplayRows' => $this->orderDetailService->getDisplayRows(details: $order->details),
            'editSettlement' => $this->orderEditHistoryService->getSettlementSummary(order: $order, histories: $orderEditPaymentHistory),
            'deliveryPartner' => $this->orderService->getDeliveryPartnerState(order: $order, panel: OrderService::DELIVERY_PARTNER_PANEL_ADMIN),
            'disableDeliveryType' => ! $physicalProduct && $shippingAddress,
            'statusHistoryDates' => $this->orderStatusHistoryService->getLatestStatusDates(histories: $order->orderStatusHistory),
            'digitalProductProcessComplete' => $this->orderService->isDigitalFileAfterSellUploaded(order: $order),
        ];
    }

    public function generateInvoice(string|int $id): ?RedirectResponse
    {
        $order = $this->orderService->findBy(params: ['id' => $id], relations: ['seller', 'shipping', 'details.product', 'latestEditHistory']);
        if (! $order) {
            ToastMagic::error(translate('Not found'));

            return redirect()->route('admin.retail.orders.list', ['status' => 'all']);
        }

        if (is_null(getWebConfig(name: 'company_web_logo_png'))) {
            $logo = $this->businessSettingService->findBy(params: ['type' => 'company_web_logo'])?->value ?? '';
            $this->businessSettingService->updateOrInsert(params: ['type' => 'company_web_logo_png'], data: ['value' => is_array($logo) ? json_encode($logo) : $logo]);
        }

        $mpdfView = PdfView::make('admin-views.order.invoice', $this->getInvoiceViewData(order: $order));
        $this->generatePdf(view: $mpdfView, filePrefix: 'order_invoice_', filePostfix: $order['id'], pdfType: 'invoice');

        return null;
    }

    private function getInvoiceViewData(Model $order): array
    {
        $billingAddress = $this->orderService->getBillingAddressData(order: $order);

        return [
            'order' => $order,
            'companyPhone' => getWebConfig(name: 'company_phone'),
            'companyEmail' => getWebConfig(name: 'company_email'),
            'companyName' => getWebConfig(name: 'company_name'),
            'companyWebLogo' => getWebConfig(name: 'company_web_logo'),
            'companyWebLogoPng' => getWebConfig(name: 'company_web_logo_png'),
            'invoiceSettings' => getWebConfig(name: 'invoice_settings'),
            'shopAddress' => getWebConfig(name: 'shop_address'),
            'currencyCode' => getCurrencyCode(type: 'default'),
            'direction' => Session::get('direction'),
            'lang' => getDefaultLanguage(),
            'orderTotalPriceSummary' => OrderManager::getOrderTotalPriceSummary(order: $order),
            'billingAddress' => $billingAddress,
            'billingAddressTypeLabel' => ShippingAddressService::getAddressTypeLabel(addressType: $billingAddress?->address_type ?? null),
            'shippingAddressTypeLabel' => ShippingAddressService::getAddressTypeLabel(addressType: $order['shipping_address_data']?->address_type ?? null),
            'productNames' => $order->details->mapWithKeys(fn ($detail) => [
                $detail->id => ($detail->product ?? json_decode($detail->product_details))->name,
            ])->all(),
        ];
    }

    public function updateStatus(StatusUpdateRequest $request): JsonResponse
    {
        $orderStatus = $request['order_status'];
        $order = $this->orderService->findBy(params: ['id' => $request['id']], relations: ['customer', 'seller.shop', 'deliveryMan', 'latestEditHistory']);
        $refusal = $order === null ? translate('Not found') : $this->getStatusChangeRefusal(order: $order, orderStatus: $orderStatus);

        if ($refusal !== null) {
            return response()->json(['status' => 0, 'message' => $refusal]);
        }

        $notifications = DB::transaction(fn (): array => $this->applyStatusChange(order: $order, orderStatus: $orderStatus));

        foreach ($notifications as [$key, $type]) {
            $this->orderService->dispatchStatusNotification(key: $key, type: $type, order: $order);
        }

        return response()->json([
            'status' => 1,
            'message' => translate(':item changed successfully', ['item' => translate('Status')]),
        ]);
    }

    private function getStatusChangeRefusal(Model $order, string $orderStatus): ?string
    {
        $reason = $this->orderService->getStatusChangeBlocker(order: $order, orderStatus: $orderStatus, guardSet: OrderService::STATUS_CHANGE_GUARDS_ADMIN);

        if ($reason === null && $orderStatus === OrderService::STATUS_DELIVERED && $this->orderTransactionService->isSettled(orderId: $order['id'])) {
            $reason = OrderService::REASON_ALREADY_DELIVERED;
        }

        return match ($reason) {
            null => null,
            OrderService::REASON_ALREADY_DELIVERED => translate('Order is already delivered.'),
            OrderService::REASON_CUSTOMER_ACCOUNT_DELETED => translate('Account has been deleted, you cannot change the status'),
            OrderService::REASON_OFFLINE_PAYMENT_UNCONFIRMED => translate('Please confirm the offline payment information before changing the order status.'),
            OrderService::REASON_EDIT_DUE_PAYMENT_UNCONFIRMED => translate('Confirm the due payment has been paid before changing the status.'),
            OrderService::REASON_EDIT_RETURN_PAYMENT_UNCONFIRMED => translate('Please return the amount first before changing the order status.'),
            OrderService::REASON_EDIT_DUE_UNPAID_BEFORE_DELIVERY => translate('Please mark as paid before delivering this order.'),
            OrderService::REASON_DIGITAL_FILE_PENDING => translate('Please upload the digital product files first'),
        };
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function applyStatusChange(Model $order, string $orderStatus): array
    {
        $isDelivered = $orderStatus === OrderService::STATUS_DELIVERED;
        $referredDelivery = $this->recordReferredCustomerDelivery(order: $order, isDelivered: $isDelivered);

        $this->updateStockForOrderStatus(orderId: $order['id'], orderStatus: $orderStatus);
        $updatedOrder = $this->orderService->update(
            id: $order['id'],
            data: ['order_status' => $orderStatus] + ($isDelivered ? ['payment_status' => OrderService::PAYMENT_STATUS_PAID, 'is_pause' => 0] : []),
        );

        if ($isDelivered) {
            $this->orderDetailService->markDeliveredForOrder(orderId: $order['id']);
            $this->orderDetailService->startRefundWindowForOrder(orderId: $order['id']);
            $this->creditLoyaltyPoint(order: $order);

            if ($referredDelivery['allows_referrer_bonus']) {
                $this->creditReferrerForFirstDeliveredOrder(order: $updatedOrder);
            }

            $this->creditDeliveryManEarning(order: $order);
        }

        $this->orderStatusHistoryService->create(data: [
            'order_id' => $order['id'],
            'user_id' => self::ACTOR_ID,
            'user_type' => self::ACTOR_TYPE,
            'status' => $orderStatus,
        ]);
        $this->orderStatusHistoryService->deleteOutdatedHistory(orderId: $order['id'], orderStatus: $orderStatus);

        if ($isDelivered && $order['seller_id'] != null) {
            $this->orderSettlementService->manageWalletOnOrderStatusChange(order: $order, receivedBy: self::ACTOR_TYPE);
        }

        return $this->getStatusChangeNotifications(order: $order, orderStatus: $orderStatus, notifiesPromoter: $referredDelivery['notifies_promoter']);
    }

    /** @return array{notifies_promoter: bool, allows_referrer_bonus: bool} */
    private function recordReferredCustomerDelivery(Model $order, bool $isDelivered): array
    {
        if (! $isDelivered || $order['is_guest']) {
            return ['notifies_promoter' => false, 'allows_referrer_bonus' => $isDelivered];
        }

        return $this->referralCustomerService->recordReferredCustomerDelivery(customerId: $order['customer_id']);
    }

    private function updateStockForOrderStatus(int|string $orderId, string $orderStatus): void
    {
        foreach ($this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $orderId]) as $orderDetail) {
            $stockChange = $this->retailProductService->updateStockForOrderDetail(orderDetail: $orderDetail, status: $orderStatus);

            if ($stockChange !== null) {
                $this->orderDetailService->update(id: $orderDetail['id'], data: $stockChange);
            }
        }
    }

    private function creditLoyaltyPoint(Model $order): void
    {
        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');
        $loyaltyPointEachOrder = getWebConfig(name: 'loyalty_point_for_each_order') ?? $loyaltyPointStatus;

        if ($order['is_guest'] || $loyaltyPointStatus != 1 || $loyaltyPointEachOrder != 1) {
            return;
        }

        $loyaltyPoint = $this->orderDetailsRewardsService->getUndeliveredLoyaltyPoint(orderId: $order['id']);

        if ($loyaltyPoint <= 0) {
            return;
        }

        $this->recordLoyaltyPointEntry(
            customerId: $order['customer_id'],
            amount: (float) $loyaltyPoint,
            transactionType: self::LOYALTY_POINT_TRANSACTION_TYPE,
            reference: (string) $order['id'],
        );
        $this->orderDetailsRewardsService->markLoyaltyPointDelivered(orderId: $order['id']);
    }

    private function creditReferrerForFirstDeliveredOrder(Model $order): void
    {
        $referrerId = $this->referralCustomerService->getReferrerIdForFirstDeliveredOrder(
            order: $order,
            customerDeliveredPaidOrderCount: $this->orderService->count(filters: [
                'customer_id' => $order['customer_id'],
                'order_status' => OrderService::STATUS_DELIVERED,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            ]),
        );

        if ($referrerId !== null) {
            $this->creditCustomerWallet(
                customerId: $referrerId,
                amount: $this->referralCustomerService->getReferrerEarningAmount(),
                transactionType: WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
                reference: WalletTransactionService::REFERENCE_TYPES['earned_by_referral'],
            );
        }
    }

    private function creditCustomerWallet(int|string $customerId, float $amount, string $transactionType, string $reference, ?string $paymentMethod = null): bool
    {
        $customer = getWebConfig(name: 'wallet_status') == 1 ? $this->customerService->find(id: $customerId) : null;

        if ($customer === null) {
            return false;
        }

        $walletTransaction = $this->walletTransactionService->createWalletEntry(
            customerId: $customer->id,
            currentBalance: (float) $customer->wallet_balance,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
            paymentMethod: $paymentMethod,
        );

        return $walletTransaction !== null && $this->customerService->updateWalletBalance(
            id: $customer->id,
            walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
        );
    }

    private function recordLoyaltyPointEntry(int|string $customerId, float $amount, string $transactionType, string $reference): void
    {
        $customer = $this->customerService->find(id: $customerId);

        if ($customer === null) {
            return;
        }

        $pointTransaction = $this->loyaltyPointTransactionService->createPointEntry(
            customerId: $customer->id,
            currentPoint: (float) $customer->loyalty_point,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
        );

        if ($pointTransaction !== null) {
            $this->customerService->updateWhere(params: ['id' => $customer->id], data: ['loyalty_point' => $pointTransaction->balance]);
        }
    }

    private function creditDeliveryManEarning(Model $order): void
    {
        if (! $order['delivery_man_id']) {
            return;
        }

        $earning = $this->orderService->getDeliveryManEarning(order: $order);
        $this->deliverymanWalletService->creditDeliveryEarning(
            deliverymanId: (int) $order['delivery_man_id'],
            deliverymanCharge: (float) $earning['deliveryman_charge'],
            cashInHand: (float) $earning['cash_in_hand'],
        );

        if ($order['deliveryman_charge']) {
            $this->deliveryManTransactionService->create(data: [
                'delivery_man_id' => $order['delivery_man_id'],
                'user_id' => self::ACTOR_ID,
                'user_type' => self::ACTOR_TYPE,
                'credit' => $order['deliveryman_charge'],
                'transaction_type' => self::DELIVERYMAN_CHARGE_TRANSACTION_TYPE,
            ]);
        }
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function getStatusChangeNotifications(Model $order, string $orderStatus, bool $notifiesPromoter): array
    {
        $notifications = [[$orderStatus, OrderService::NOTIFICATION_TYPE_CUSTOMER]];

        if ($orderStatus === OrderService::STATUS_CANCELED) {
            $notifications[] = [OrderService::STATUS_CANCELED, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN];
        }

        if ($order['seller_is'] == OrderService::SELLER_IS_SELLER && in_array($orderStatus, [OrderService::STATUS_CANCELED, OrderService::STATUS_DELIVERED], true)) {
            $notifications[] = [$orderStatus, OrderService::NOTIFICATION_TYPE_SELLER];
        }

        if ($notifiesPromoter) {
            $notifications[] = [OrderService::NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED, OrderService::NOTIFICATION_TYPE_PROMOTER];
        }

        return $notifications;
    }

    public function updateAddress(AddressUpdateRequest $request): RedirectResponse
    {
        $order = $this->orderService->findBy(params: ['id' => $request['order_id']], relations: ['seller.shop', 'deliveryMan']);

        if ($order === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $this->orderService->updateAddressData(order: $order, addressType: $request['address_type'], address: $request->getAddressData());

        if ($order['seller_is'] == OrderService::SELLER_IS_SELLER) {
            $this->orderService->dispatchStatusNotification(key: OrderService::NOTIFICATION_ORDER_EDIT, type: OrderService::NOTIFICATION_TYPE_SELLER, order: $order);
        }

        if ($order['delivery_type'] == OrderService::DELIVERY_TYPE_SELF && $order['delivery_man_id']) {
            $this->orderService->dispatchStatusNotification(key: OrderService::NOTIFICATION_ORDER_EDIT, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN, order: $order);
        }

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function addDeliveryMan(DeliveryManAssignRequest $request, string|int $order_id, string|int $delivery_man_id): JsonResponse
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }

        $order = $this->orderService->findBy(params: ['id' => $order_id], relations: []);
        $isAssignableDeliveryMan = $order !== null && $this->deliveryManService->exists(params: [
            'id' => (int) $delivery_man_id,
            'seller_id' => $this->orderService->getDeliveryManOwnerId(order: $order),
            'is_active' => 1,
        ]);

        if (! $isAssignableDeliveryMan) {
            return response()->json(['status' => false]);
        }

        $order = $this->orderService->assignDeliveryMan(orderId: $order['id'], sellerId: null, deliveryManId: $delivery_man_id);

        $order->load(['seller.shop', 'deliveryMan']);
        $this->orderService->dispatchStatusNotification(key: OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN, order: $order);

        if ($order['seller_is'] == OrderService::SELLER_IS_SELLER) {
            $this->orderService->dispatchStatusNotification(key: self::NOTIFICATION_DELIVERY_MAN_ASSIGNED_BY_ADMIN, type: OrderService::NOTIFICATION_TYPE_SELLER, order: $order);
        }

        return response()->json(['status' => true], 200);
    }

    public function updateAmountDate(DeliveryScheduleUpdateRequest $request): JsonResponse
    {
        $order = $this->orderService->findBy(params: ['id' => $request['order_id']], relations: ['customer', 'deliveryMan']);

        if ($order === null) {
            return response()->json(['status' => false, 'message' => translate('Not found')], 403);
        }

        $isChargeUpdate = $request['field_name'] === OrderSettlementService::FIELD_DELIVERYMAN_CHARGE;

        $isUpdated = DB::transaction(function () use ($order, $request, $isChargeUpdate): bool {
            if (! $isChargeUpdate) {
                $this->orderSettlementService->createExpectedDeliveryHistory(
                    orderId: $order['id'],
                    userId: self::ACTOR_ID,
                    userType: self::ACTOR_TYPE,
                    expectedDeliveryDate: $request['field_val'],
                );
            }

            return $this->orderService->updateDeliverySchedule(
                order: $order,
                deliverymanCharge: $isChargeUpdate ? currencyConverter(amount: (float) $request['field_val']) : $order['deliveryman_charge'],
                expectedDeliveryDate: $isChargeUpdate ? $order['expected_delivery_date'] : $request['field_val'],
            );
        });

        $this->orderService->dispatchStatusNotification(
            key: $isChargeUpdate ? self::NOTIFICATION_DELIVERY_MAN_CHARGE : OrderService::NOTIFICATION_EXPECTED_DELIVERY_DATE,
            type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
            order: $order,
        );

        return response()->json([
            'status' => $isUpdated,
            'message' => translate(':item added successfully', ['item' => $isChargeUpdate ? translate('Deliveryman charge') : translate('Expected delivery date')]),
        ], $isUpdated ? 200 : 403);
    }

    public function getCustomers(Request $request): JsonResponse
    {
        $allCustomer = ['id' => 'all', 'text' => 'All Customer'];
        $customers = $this->customerService->getNameOptions(searchValue: $request['q'] ?? $request['searchValue'])->toArray();
        array_unshift($customers, $allCustomer);

        return response()->json($customers);
    }

    public function updatePaymentStatus(PaymentStatusUpdateRequest $request): JsonResponse
    {
        $order = $this->orderService->findBy(params: ['id' => $request['id']], relations: ['customer']);

        $refusal = match (true) {
            $order === null => translate('Not found'),
            $this->orderService->isCustomerAccountDeleted(order: $order) => translate('Account has been deleted, you cannot change the status'),
            $this->orderService->isOfflinePaymentUnconfirmed(order: $order) => translate('Please confirm the offline payment information before editing this order.'),
            default => null,
        };

        if ($refusal !== null) {
            return response()->json(['status' => 0, 'message' => $refusal]);
        }

        $this->orderService->update(id: $order['id'], data: ['payment_status' => $request['payment_status']]);

        return response()->json([
            'status' => 1,
            'message' => translate(':item changed successfully', ['item' => translate('Status')]),
        ]);
    }

    public function filterInHouseOrder(): RedirectResponse
    {
        if (session()->has('show_inhouse_orders') && session('show_inhouse_orders') == 1) {
            session()->put('show_inhouse_orders', 0);
        } else {
            session()->put('show_inhouse_orders', 1);
        }

        return back();
    }

    public function uploadDigitalFileAfterSell(DigitalFileUploadAfterSellRequest $request): RedirectResponse
    {
        $orderDetail = $this->orderDetailService->find(id: $request['order_id']);

        if ($orderDetail !== null && $this->orderDetailService->updateDigitalFileAfterSell(orderDetail: $orderDetail, file: $request->file('digital_file_after_sell'))) {
            ToastMagic::success(translate('Digital file uploaded successfully'));
        } else {
            ToastMagic::error(translate('Digital file upload failed'));
        }

        return back();
    }
}
