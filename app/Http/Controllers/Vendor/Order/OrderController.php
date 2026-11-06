<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Enums\GlobalConstant;
use App\Enums\WebConfigKey;
use App\Exports\OrderExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Shared\Order\AddressUpdateRequest;
use App\Http\Requests\Shared\Order\DeliveryManAssignRequest;
use App\Http\Requests\Shared\Order\DeliveryScheduleUpdateRequest;
use App\Http\Requests\Shared\Order\DigitalFileUploadAfterSellRequest;
use App\Http\Requests\Shared\Order\DueAmountMarkAsPaidRequest;
use App\Http\Requests\Shared\Order\DueAmountSwitchToCODRequest;
use App\Http\Requests\Shared\Order\PaymentStatusUpdateRequest;
use App\Http\Requests\Shared\Order\StatusUpdateRequest;
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
use App\Services\New\VendorService;
use App\Services\New\WalletTransactionService;
use App\Traits\OrderEditManager;
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
    private const ACTOR_TYPE = 'seller';

    private const FILTER_OPTION_ALL = 'all';

    private const LOYALTY_POINT_TRANSACTION_TYPE = 'order_place';

    private const DELIVERYMAN_CHARGE_TRANSACTION_TYPE = 'deliveryman_charge';

    private const NOTIFICATION_DELIVERY_MAN_CHARGE = 'delivery_man_charge';

    private const DUE_PAYMENT_NOTE_SWITCHED_TO_CASH_ON_DELIVERY = 'Switched to COD by seller';

    private const DUE_PAYMENT_NOTE_MARKED_AS_PAID = 'Marked as paid by seller';

    use OrderEditManager;
    use OrderEditSessionTrait;
    use OrderEditSummaryTrait;
    use PdfGenerator;

    public function __construct(
        private readonly OrderSettlementService $orderSettlementService,
        private readonly CustomerService $customerService,
        private readonly VendorService $vendorService,
        private readonly DeliveryManService $deliveryManService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly OrderEditService $orderEditService,
        private readonly OrderService $orderService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly ProductRetailService $retailProductService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    public function index(?Request $request, ?string $type = null): View
    {
        $sellerId = auth('seller')->id();
        $searchValue = $request['searchValue'];
        $filter = $request['filter'];
        $dateType = $request['date_type'];
        $from = $request['from'];
        $to = $request['to'];
        $status = $request['status'];
        $paymentPaidStatus = $request['payment_status'] ?? [];
        $orderStatus = $request['order_current_status'] ?? [];
        $orderTypes = $request['order_types'] ?? [];

        $this->orderService->updateWhere(params: ['seller_id' => $sellerId, 'checked' => 0], data: ['checked' => 1]);

        $listFilters = $this->buildListFilters(request: $request, status: $status, sellerId: $sellerId);

        $statusCounts = $this->orderService->getStatusCounts(filters: $listFilters);

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

        $orders = $this->orderService->getList(
            searchValue: $searchValue,
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
            relations: [
                'customer',
                'latestEditHistory' => fn ($query) => $query->select([
                    'id', 'order_id', 'order_due_payment_method', 'order_due_amount',
                    'order_due_payment_status', 'order_return_amount', 'order_return_payment_status',
                ]),
                'details',
            ],
            filters: $listFilters,
            sortBy: OrderService::SORT_NEWEST,
        );

        $customers = $this->customerService->getNameOptions(searchValue: $searchValue, dataLimit: 'all')->toArray();
        array_unshift($customers, ['id' => 'all', 'text' => translate('All Customer')]);

        $vendorId = $request['seller_id'];
        $customerId = $request['customer_id'];
        $orderTotalPriceSummaries = $this->orderService->getTotalPriceSummaries(orders: $orders);
        $itemPreviews = $this->orderService->getItemPreviews(orders: $orders);

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
            'customer_id' => $customerId ?: 'all',
            'selected_customer_id' => $customerId ?? 'all',
            'order_amount_settlement' => $request->input('order_amount_settlement', []),
        ];

        $hasActiveFilters = $this->hasActiveListFilters(request: $request);

        return view('vendor-views.order.list', compact(
            'orders',
            'searchValue',
            'from',
            'to',
            'filter',
            'customers',
            'vendorId',
            'customerId',
            'dateType',
            'status',
            'allOrdersInfo',
            'paymentPaidStatus',
            'orderStatus',
            'orderTypes',
            'orderTotalPriceSummaries',
            'itemPreviews',
            'exportQuery',
            'filters',
            'hasActiveFilters',
        ));
    }

    private function hasActiveListFilters(Request $request): bool
    {
        $dateType = $request['date_type'];
        $customerId = $request['customer_id'];

        return array_filter([
            $request['delivery_man_id'],
            $dateType === self::FILTER_OPTION_ALL ? null : $dateType,
            $request['from'],
            $request['to'],
            $request['filter'],
            $request['order_types'],
            $request['order_current_status'],
            $request['order_amount_settlement'],
            $request['payment_status'],
            $customerId === self::FILTER_OPTION_ALL ? null : $customerId,
        ]) !== [];
    }

    private function buildListFilters(Request $request, ?string $status, int $sellerId): array
    {
        $orderTypes = $request['order_types'] ?? [];
        $orderTypeFilter = $request['filter'] === self::FILTER_OPTION_ALL ? null : $request['filter'];

        return array_filter([
            'order_status' => $status == self::FILTER_OPTION_ALL ? ($request['order_current_status'] ?? []) : $status,
            'payment_status' => $request['payment_status'] ?? [],
            'order_type' => $orderTypes !== [] ? $orderTypes : $orderTypeFilter,
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'] === self::FILTER_OPTION_ALL ? null : $request['customer_id'],
            'seller_id' => $sellerId,
            'seller_is' => OrderService::SELLER_IS_SELLER,
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

    public function orderReturnAmountToCustomer(): RedirectResponse
    {
        ToastMagic::error(translate('Please contact the admin to return the amount to the customer.'));

        return redirect()->back();
    }

    public function orderDueAmountMarkAsPaid(DueAmountMarkAsPaidRequest $request): RedirectResponse
    {
        $refusal = DB::transaction(function () use ($request): ?string {
            $order = $this->orderService->lockForEditSettlement(id: $request['order_id'], sellerId: (int) auth('seller')->id());

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

    /** @return array{edit_by: string, edited_user_id: int|string|null, edited_user_name: string} */
    private function getEditor(): array
    {
        $seller = auth('seller')->user();

        return [
            'edit_by' => self::ACTOR_TYPE,
            'edited_user_id' => $seller?->id,
            'edited_user_name' => $seller?->f_name.' '.$seller?->l_name,
        ];
    }

    public function exportList(Request $request, string $status): BinaryFileResponse
    {
        $sellerId = auth('seller')->id();
        $orders = $this->orderService->getList(
            searchValue: $request['searchValue'],
            dataLimit: 'all',
            relations: ['customer', 'seller.shop', 'details'],
            filters: $this->buildListFilters(request: $request, status: $status, sellerId: $sellerId),
            sortBy: OrderService::SORT_NEWEST,
        );

        $dateType = $request['date_type'] ?? '';
        $dateRange = $this->orderService->getExportDateRange(dateType: $dateType, from: $request['from'], to: $request['to']);

        $seller = $this->vendorService->findBy(params: ['id' => $sellerId]);
        $customer = [];
        if ($request['customer_id'] != 'all' && $request->has('customer_id')) {
            $customer = $this->customerService->findBy(params: ['id' => $request['customer_id']]);
        }

        $data = [
            'data-from' => 'vendor',
            'orders' => $orders,
            'order_summaries' => $this->orderService->getTotalPriceSummaries(orders: $orders),
            'order_status' => $status,
            'seller' => $seller,
            'store_name' => $seller?->shop?->name,
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

    public function getCustomers(Request $request): JsonResponse
    {
        $allCustomer = ['id' => 'all', 'text' => 'All Customer'];
        $customers = $this->customerService->getNameOptions(searchValue: $request['q'] ?? $request['searchValue'])->toArray();
        array_unshift($customers, $allCustomer);

        return response()->json($customers);
    }

    public function generateInvoice(string|int $id): ?RedirectResponse
    {
        $order = $this->orderService->findForSeller(
            id: $id,
            sellerId: auth('seller')->id(),
            relations: ['details.product', 'customer', 'shipping', 'seller', 'latestEditHistory'],
        );
        if (! $order) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $mpdfView = PdfView::make('vendor-views.order.invoice', [
            ...$this->orderService->getSellerInvoiceViewData(order: $order),
            'direction' => Session::get('direction'),
            'lang' => getDefaultLanguage(),
        ]);
        $this->generatePdf(view: $mpdfView, filePrefix: 'order_invoice_', filePostfix: $order['id'], pdfType: 'invoice');

        return null;
    }

    public function getView(string|int $id): View|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $order = $this->orderService->findForSeller(
            id: $id,
            sellerId: $vendorId,
            relations: [
                'seller.shop',
                'deliveryMan',
                'verificationImages',
                'details' => fn ($query) => $query->with(['product', 'productAllStatus', 'storage']),
                'latestEditHistory',
                'customer',
                'shipping',
                'offlinePayments',
                'orderEditHistory' => fn ($query) => $query->orderBy('id', 'desc'),
                'orderStatusHistory' => fn ($query) => $query->orderBy('created_at', 'desc'),
            ],
        );
        if (! $order) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        if ($order['init_order_amount'] <= 0) {
            $this->orderService->syncInitialOrderAmount(order: $order);
        }

        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');
        $deliveryCountry = $this->deliveryCountryCodeService->getList(dataLimit: 'all');
        $countries = $countryRestrictStatus ? $this->deliveryCountryCodeService->getDeliveryCountryArray(deliveryCountryCodes: $deliveryCountry) : GlobalConstant::COUNTRIES;
        $zipCodes = $zipRestrictStatus ? $this->deliveryZipCodeService->getList(dataLimit: 'all') : 0;

        $physicalProduct = false;
        foreach ($order->details as $orderDetail) {
            $orderDetailProduct = json_decode($orderDetail?->product_details, true);
            if (($orderDetailProduct['product_type'] ?? null) == OrderService::PRODUCT_TYPE_PHYSICAL) {
                $physicalProduct = true;
                break;
            }
        }

        $deliveryMen = $this->orderService->getDeliveryManOwnerId(order: $order) === (int) $vendorId
            ? $this->deliveryManService->getList(dataLimit: 'all', filters: ['is_active' => 1, 'seller_id' => $vendorId])
            : collect();
        $isOrderOnlyDigital = $this->orderService->isOnlyDigital(order: $order);
        $previousOrder = $this->orderService->getList(
            dataLimit: 1,
            filters: [
                'seller_id' => $vendorId,
                'seller_is' => OrderService::SELLER_IS_SELLER,
                OrderService::FILTER_ID_BEFORE => $id,
            ],
            sortBy: OrderService::SORT_NEWEST,
        )->first();
        $nextOrder = $this->orderService->getList(
            dataLimit: 1,
            filters: [
                'seller_id' => $vendorId,
                'seller_is' => OrderService::SELLER_IS_SELLER,
                OrderService::FILTER_ID_AFTER => $id,
            ],
            sortBy: OrderService::SORT_OLDEST,
        )->first();
        $allProductsList = $this->retailProductService->getList(
            dataLimit: 10,
            relations: ['brand', 'category', 'seller.shop'],
            filters: ['added_by' => ProductRetailService::ADDED_BY_SELLER, 'seller_id' => $order['seller_id']],
            sortBy: ProductRetailService::SORT_OLDEST,
        );
        $isOrderEditable = $this->orderEditService->checkIsOrderEditable(order: $order, type: OrderEditService::TYPE_VENDOR);
        Session::forget($this->orderEditService->getOrderEditSessionKey(owner: $this->orderEditSessionOwner(), orderId: $order['id']));
        $totalDelivered = $this->orderService->count(filters: [
            'seller_id' => $order['seller_id'],
            'order_status' => OrderService::STATUS_DELIVERED,
        ]);
        $orderProductsSession = ['product_list' => $this->orderEditProductList(orderEditService: $this->orderEditService, order: $order)];
        $editOrderSummary = $this->getEditOrderSummary(order: $order, editedProducts: $orderProductsSession['product_list'], editor: [
            'edit_by' => OrderEditService::EDITOR_SELLER,
            'edited_user_id' => auth()->guard('seller')->id(),
            'edited_user_name' => auth()->guard('seller')->user()->f_name.' '.auth()->guard('seller')->user()->l_name,
        ]);

        $orderEditPaymentHistory = $this->orderEditHistoryService->getList(filters: ['order_id' => $order['id']], dataLimit: 'all');
        $orderCount = $this->orderService->count(filters: ['customer_id' => $order['customer_id']]);

        if ($order['order_type'] != OrderService::ORDER_TYPE_DEFAULT) {
            return view('vendor-views.pos.order.order-details', compact('order', 'orderCount', 'totalDelivered', 'previousOrder', 'nextOrder', 'isOrderEditable', 'editOrderSummary'));
        }

        $courierPartnerAssigned = deliveryPartnerAssigned(order: $order);

        return view('vendor-views.order.order-details', [
            ...compact(
                'order', 'deliveryMen', 'totalDelivered', 'physicalProduct', 'isOrderOnlyDigital',
                'countryRestrictStatus', 'zipRestrictStatus', 'countries', 'zipCodes', 'orderCount',
                'previousOrder', 'nextOrder', 'allProductsList', 'isOrderEditable', 'orderProductsSession',
                'editOrderSummary', 'orderEditPaymentHistory', 'courierPartnerAssigned'
            ),
            'editProductQuantities' => $this->orderEditService->getProductListQuantityBounds(productList: $orderProductsSession['product_list']),
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
            'deliveryPartner' => $this->orderService->getDeliveryPartnerState(order: $order, panel: OrderService::DELIVERY_PARTNER_PANEL_VENDOR),
            'disableDeliveryType' => ! $physicalProduct && $shippingAddress,
            'statusHistoryDates' => $this->orderStatusHistoryService->getLatestStatusDates(histories: $order->orderStatusHistory),
            'digitalProductProcessComplete' => $this->orderService->isDigitalFileAfterSellUploaded(order: $order),
        ];
    }

    public function orderDueAmountSwitchToCOD(DueAmountSwitchToCODRequest $request): RedirectResponse
    {
        $order = $this->orderService->findForSeller(id: $request['order_id'], sellerId: auth('seller')->id());
        $latestEditHistory = $order === null ? null : $this->orderEditHistoryService->findLatestForOrder(orderId: $order['id']);
        $reason = $order === null ? null : $this->orderService->getEditDueCashOnDeliverySwitchBlocker(
            order: $order,
            latestEditHistory: $latestEditHistory,
            dueAmount: $request['order_due_amount'],
        );
        $refusal = match (true) {
            $order === null => translate('Not found'),
            $reason === null => null,
            $reason === OrderService::REASON_ALREADY_DELIVERED => translate('Order is already delivered.'),
            $reason === OrderService::REASON_EDIT_DUE_AMOUNT_MISMATCH => translate('The entered due amount must match the order due amount'),
            $reason === OrderService::REASON_EDIT_HISTORY_ABSENT => translate('No edit history found'),
            $reason === OrderService::REASON_EDIT_DUE_NOT_UNPAID => translate('Order already paid'),
            $reason === OrderService::REASON_EDIT_DUE_ALREADY_CASH_ON_DELIVERY => translate('The due amount is already set to cash on delivery'),
        };

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

    public function updateStatus(StatusUpdateRequest $request): JsonResponse
    {
        $orderStatus = $request['order_status'];
        $order = $this->orderService->findForSeller(
            id: $request['id'] ?? 0,
            sellerId: auth('seller')->id(),
            relations: ['customer', 'seller.shop', 'deliveryMan', 'latestEditHistory'],
        );
        $refusal = $order === null
            ? translate('Not found')
            : $this->getStatusChangeRefusal(order: $order, orderStatus: $orderStatus, checksDigitalFileUpload: $request->ajax());

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

    private function getStatusChangeRefusal(Model $order, string $orderStatus, bool $checksDigitalFileUpload): ?string
    {
        $reason = $this->orderService->getStatusChangeBlocker(order: $order, orderStatus: $orderStatus, guardSet: OrderService::STATUS_CHANGE_GUARDS_VENDOR);

        if ($reason === OrderService::REASON_DIGITAL_FILE_PENDING && ! $checksDigitalFileUpload) {
            $reason = null;
        }

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
            OrderService::REASON_PAYMENT_UNCONFIRMED_BEFORE_DELIVERY => translate('Please update the payment status first'),
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
            'user_id' => auth('seller')->id(),
            'user_type' => self::ACTOR_TYPE,
            'status' => $orderStatus,
        ]);
        $this->orderStatusHistoryService->deleteOutdatedHistory(orderId: $order['id'], orderStatus: $orderStatus);

        if ($isDelivered && $order['seller_id'] != null) {
            $this->orderSettlementService->manageWalletOnOrderStatusChange(order: $order, receivedBy: self::ACTOR_TYPE);
        }

        $notifications = [[$orderStatus, OrderService::NOTIFICATION_TYPE_CUSTOMER]];

        if ($orderStatus === OrderService::STATUS_CANCELED) {
            $notifications[] = [OrderService::STATUS_CANCELED, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN];
        }

        if ($referredDelivery['notifies_promoter']) {
            $notifications[] = [OrderService::NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED, OrderService::NOTIFICATION_TYPE_PROMOTER];
        }

        return $notifications;
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

        if ($order['is_guest'] || $order['seller_id'] == null || getWebConfig(name: 'wallet_status') != 1 || $loyaltyPointStatus != 1 || $loyaltyPointEachOrder != 1) {
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
                'user_id' => auth('seller')->id(),
                'user_type' => self::ACTOR_TYPE,
                'credit' => $order['deliveryman_charge'],
                'transaction_type' => self::DELIVERYMAN_CHARGE_TRANSACTION_TYPE,
            ]);
        }
    }

    public function updateAddress(AddressUpdateRequest $request): RedirectResponse
    {
        $order = $this->orderService->findForSeller(id: $request['order_id'] ?? 0, sellerId: auth('seller')->id(), relations: ['deliveryMan']);

        if ($order === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $this->orderService->updateAddressData(order: $order, addressType: $request['address_type'], address: $request->getAddressData());

        if ($order['delivery_type'] == OrderService::DELIVERY_TYPE_SELF && $order['delivery_man_id']) {
            $this->orderService->dispatchStatusNotification(key: OrderService::NOTIFICATION_ORDER_EDIT, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN, order: $order);
        }

        ToastMagic::success(translate('Updated successfully'));

        return back();
    }

    public function updatePaymentStatus(PaymentStatusUpdateRequest $request): JsonResponse
    {
        $order = $this->orderService->findForSeller(id: $request['id'] ?? 0, sellerId: auth('seller')->id(), relations: ['customer']);

        if ($order === null) {
            return response()->json(['status' => 0, 'message' => translate('Not found')]);
        }

        if ($order['payment_status'] == OrderService::PAYMENT_STATUS_PAID) {
            return response()->json([
                'status' => 0,
                'message' => translate('Once payment status is paid, it cannot be changed to unpaid.'),
            ]);
        }

        if ($this->orderService->isCustomerAccountDeleted(order: $order)) {
            return response()->json([
                'status' => 0,
                'customer_status' => 0,
                'message' => translate('Account has been deleted, you cannot change the status'),
            ]);
        }

        if ($this->orderService->isOfflinePaymentUnconfirmed(order: $order)) {
            return response()->json([
                'status' => 0,
                'message' => translate('Please confirm the offline payment information before editing this order.'),
            ]);
        }

        $this->orderService->update(id: $order['id'], data: ['payment_status' => $request['payment_status']]);

        return response()->json([
            'status' => 1,
            'message' => translate(':item changed successfully', ['item' => translate('Status')]),
        ]);
    }

    public function addDeliveryMan(DeliveryManAssignRequest $request, string|int $order_id, string|int $delivery_man_id): JsonResponse
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }

        $vendorId = (int) auth('seller')->id();
        $order = $this->orderService->findForSeller(id: $order_id, sellerId: $vendorId);

        if ($order === null) {
            return response()->json(['status' => false]);
        }

        if ($this->orderService->isAlreadyDelivered(order: $order)) {
            return response()->json(['status' => false], 403);
        }

        $isOwnActiveDeliveryMan = $this->orderService->getDeliveryManOwnerId(order: $order) === $vendorId
            && $this->deliveryManService->exists(params: ['id' => (int) $delivery_man_id, 'seller_id' => $vendorId, 'is_active' => 1]);

        if (! $isOwnActiveDeliveryMan) {
            return response()->json(['status' => false]);
        }

        $order = $this->orderService->assignDeliveryMan(orderId: $order['id'], sellerId: $vendorId, deliveryManId: $delivery_man_id);
        $order->load('deliveryMan');
        $this->orderService->dispatchStatusNotification(key: OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN, order: $order);

        return response()->json(['status' => true], 200);
    }

    public function updateAmountDate(DeliveryScheduleUpdateRequest $request): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $order = $this->orderService->findForSeller(id: $request['order_id'] ?? 0, sellerId: $vendorId, relations: ['customer', 'deliveryMan']);

        if ($order === null) {
            return response()->json(['status' => false, 'message' => translate('Not found')], 403);
        }

        $isChargeUpdate = $request['field_name'] === OrderSettlementService::FIELD_DELIVERYMAN_CHARGE;

        $isUpdated = DB::transaction(function () use ($order, $request, $isChargeUpdate, $vendorId): bool {
            if (! $isChargeUpdate) {
                $this->orderSettlementService->createExpectedDeliveryHistory(
                    orderId: $order['id'],
                    userId: $vendorId,
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

    public function uploadDigitalFileAfterSell(DigitalFileUploadAfterSellRequest $request): RedirectResponse
    {
        $orderDetail = $this->orderDetailService->findForSeller(id: $request['order_id'] ?? 0, sellerId: auth('seller')->id());

        if ($orderDetail !== null && $this->orderDetailService->updateDigitalFileAfterSell(orderDetail: $orderDetail, file: $request->file('digital_file_after_sell'))) {
            ToastMagic::success(translate('Digital file uploaded successfully'));
        } else {
            ToastMagic::error(translate('Digital file upload failed'));
        }

        return back();
    }
}
