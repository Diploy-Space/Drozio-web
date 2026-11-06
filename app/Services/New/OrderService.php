<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Events\OrderEditDuePaymentEvent;
use App\Events\OrderEditEvent;
use App\Events\OrderPlacedEvent;
use App\Events\OrderStatusEvent;
use App\Models\Order;
use App\Services\BaseService;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class OrderService extends BaseService
{
    use VatTaxManagement;

    public const WALLET_ORDER_PAYMENT_REFERENCE = 'order payment';

    public const PAYMENT_PAYLOAD_ORDER_AMOUNT = 'order_amount';

    public const PAYMENT_PAYLOAD_CART_GROUP_IDS = 'cart_group_ids';

    public const SORT_NEWEST = 'newest';

    public const SORT_OLDEST = 'oldest';

    public const SORT_ORDER_ID_ASC = 'order-id-asc';

    public const SORT_ORDER_ID_DESC = 'order-id-desc';

    public const SORT_ORDER_DATE_ASC = 'order-date-asc';

    public const SORT_ORDER_DATE_DESC = 'order-date-desc';

    public const SORT_CUSTOMER_NAME_ASC = 'customer-name-asc';

    public const SORT_CUSTOMER_NAME_DESC = 'customer-name-desc';

    public const SORT_STORE_NAME_ASC = 'store-name-asc';

    public const SORT_STORE_NAME_DESC = 'store-name-desc';

    public const SORT_ORDER_AMOUNT_ASC = 'order-amount-asc';

    public const SORT_ORDER_AMOUNT_DESC = 'order-amount-desc';

    public const SORT_ORDER_STATUS_ASC = 'order-status-asc';

    public const SORT_ORDER_STATUS_DESC = 'order-status-desc';

    public const COLUMN_SORTS = [
        self::SORT_ORDER_ID_ASC,
        self::SORT_ORDER_ID_DESC,
        self::SORT_ORDER_DATE_ASC,
        self::SORT_ORDER_DATE_DESC,
        self::SORT_CUSTOMER_NAME_ASC,
        self::SORT_CUSTOMER_NAME_DESC,
        self::SORT_STORE_NAME_ASC,
        self::SORT_STORE_NAME_DESC,
        self::SORT_ORDER_AMOUNT_ASC,
        self::SORT_ORDER_AMOUNT_DESC,
        self::SORT_ORDER_STATUS_ASC,
        self::SORT_ORDER_STATUS_DESC,
    ];

    private const SORT_ASCENDING = 'asc';

    private const SORT_DESCENDING = 'desc';

    public const FILTER_DATE_TYPE = 'date_type';

    public const FILTER_DATE_RANGE = 'date_range';

    public const FILTER_ID_BEFORE = 'id_before';

    public const FILTER_ID_AFTER = 'id_after';

    public const FILTER_ID_EXCEPT = 'id_except';

    public const FILTER_SETTLEMENT = 'has_order_edit_settlement';

    public const FILTER_DELIVERYMAN_SELLER_ID = 'deliveryman_seller_id';

    public const FILTER_SOURCE = 'source';

    public const SOURCE_POS = 'POS';

    public const SETTLEMENT_DUE = 'due';

    public const SETTLEMENT_RETURN = 'return';

    public const DATE_TYPE_TODAY = 'today';

    public const DATE_TYPE_THIS_WEEK = 'this_week';

    public const DATE_TYPE_THIS_MONTH = 'this_month';

    public const DATE_TYPE_THIS_YEAR = 'this_year';

    public const DATE_TYPE_CUSTOM = 'custom_date';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_FAILED = 'failed';

    public const SELLER_IS_ADMIN = 'admin';

    public const SELLER_IS_SELLER = 'seller';

    public const ORDER_TYPE_DEFAULT = 'default_type';

    public const OWNER_IN_HOUSE = 'in-house';

    public const PRODUCT_TYPE_PHYSICAL = 'physical';

    public const PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    public const PAYMENT_METHOD_OFFLINE = 'offline_payment';

    public const DELIVERY_TYPE_SELF = 'self_delivery';

    public const DELIVERY_TYPE_THIRD_PARTY = 'third_party_delivery';

    public const DELIVERY_PARTNER_PANEL_ADMIN = 'admin';

    public const DELIVERY_PARTNER_PANEL_VENDOR = 'vendor';

    public const DIGITAL_PRODUCT_TYPE_READY_AFTER_SELL = 'ready_after_sell';

    public const STATUS_GROUP_ONGOING = 'ongoing';

    public const STATUS_GROUP_PREVIOUS = 'previous';

    /** @var array<int, string> */
    public const ONGOING_STATUSES = ['pending', 'confirmed', 'processing', 'out_for_delivery'];

    public const STATUS_CONFIRMED = 'confirmed';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const PAYMENT_METHOD_WALLET = 'pay_by_wallet';

    public const NOTIFICATION_TYPE_SELLER = 'seller';

    public const NOTIFICATION_TYPE_CUSTOMER = 'customer';

    public const NOTIFICATION_TYPE_PROMOTER = 'promoter';

    public const NOTIFICATION_NEW_ORDER = 'new_order_message';

    public const NOTIFICATION_REFERRED_CUSTOMER_ORDERED = 'your_referred_customer_has_been_place_order';

    public const NOTIFICATION_EDIT_DUE_PAYMENT = 'order_edit_due_payment_message';

    public const NOTIFICATION_TYPE_DELIVERY_MAN = 'delivery_man';

    public const NOTIFICATION_NEW_ORDER_ASSIGNED = 'new_order_assigned_message';

    public const NOTIFICATION_EXPECTED_DELIVERY_DATE = 'expected_delivery_date';

    public const NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED = 'your_referred_customer_order_has_been_delivered';

    public const NOTIFICATION_ORDER_EDIT = 'order_edit_message';

    public const NOTIFICATION_ORDER_RESCHEDULED = 'order_rescheduled_message';

    /** @var array<int, string> */
    public const DELIVERYMAN_UPDATABLE_STATUSES = ['delivered', 'canceled', 'returned', 'out_for_delivery'];

    private const VERIFICATION_CODE_MIN = 100000;

    private const VERIFICATION_CODE_MAX = 999999;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_GROUP_ALL = 'all';

    private const OUT_FOR_DELIVERY_ADVANCEABLE_STATUSES = ['pending', 'confirmed', 'processing'];

    public const LIST_TYPE_REORDER = 'reorder';

    /** @var array<string, array<int, string>> */
    public const CUSTOMER_APP_STATUS_GROUPS = [
        'ongoing' => ['out_for_delivery', 'processing', 'confirmed', 'pending'],
        'canceled' => ['canceled', 'failed', 'returned'],
        'delivered' => ['delivered'],
    ];

    /** @var array<int, string> */
    public const SELLER_STATISTICS_STATUSES = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'canceled', 'returned', 'failed'];

    /** @var array<int, string> */
    public const REVERSIBLE_AFTER_DELIVERY_STATUSES = ['returned', 'failed', 'canceled'];

    private const STATUS_TALLY_ORDER = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'returned', 'failed', 'canceled'];

    /** @var array<int, string> */
    public const CUSTOMER_LIST_STATUSES = self::STATUS_TALLY_ORDER;

    public const SORT_LATEST_UPDATED = 'latest_updated';

    public const ADDRESS_TYPE_SHIPPING = 'shipping';

    public const ADDRESS_TYPE_BILLING = 'billing';

    public const EDITOR_ADMIN = 'admin';

    public const REASON_CUSTOMER_ACCOUNT_DELETED = 'customer_account_deleted';

    public const REASON_OFFLINE_PAYMENT_UNCONFIRMED = 'offline_payment_unconfirmed';

    public const REASON_EDIT_DUE_PAYMENT_UNCONFIRMED = 'edit_due_payment_unconfirmed';

    public const REASON_EDIT_RETURN_PAYMENT_UNCONFIRMED = 'edit_return_payment_unconfirmed';

    public const REASON_EDIT_DUE_UNPAID_BEFORE_DELIVERY = 'edit_due_unpaid_before_delivery';

    public const REASON_CASH_ON_DELIVERY_NOT_DELIVERED = 'cash_on_delivery_not_delivered';

    public const REASON_ALREADY_DELIVERED = 'already_delivered';

    public const REASON_PAYMENT_UNCONFIRMED_BEFORE_DELIVERY = 'payment_unconfirmed_before_delivery';

    public const REASON_DIGITAL_FILE_PENDING = 'digital_file_pending';

    public const REASON_EDIT_RETURN_NOT_PENDING = 'edit_return_not_pending';

    public const REASON_EDIT_DUE_AMOUNT_MISMATCH = 'edit_due_amount_mismatch';

    public const REASON_EDIT_HISTORY_ABSENT = 'edit_history_absent';

    public const REASON_EDIT_DUE_NOT_UNPAID = 'edit_due_not_unpaid';

    public const REASON_EDIT_DUE_ALREADY_CASH_ON_DELIVERY = 'edit_due_already_cash_on_delivery';

    private const EDIT_AMOUNT_SCALE = 20;

    private const PLAIN_DECIMAL_PATTERN = '/^\d+(\.\d+)?$/';

    public const STATUS_CHANGE_GUARDS_ADMIN = 'admin';

    public const STATUS_CHANGE_GUARDS_CUSTOMER = 'customer';

    public const STATUS_CHANGE_GUARDS_VENDOR = 'vendor';

    private const SHIPPING_RESPONSIBILITY_INHOUSE = 'inhouse_shipping';

    private const SHIPPING_RESPONSIBILITY_SELLERWISE = 'sellerwise_shipping';

    private const IN_HOUSE_DELIVERY_MAN_OWNER_ID = 0;

    private const EDIT_DUE_PAYMENT_STATUS_UNPAID = 'unpaid';

    private const EDIT_RETURN_PAYMENT_STATUS_PENDING = 'pending';

    private const PRODUCT_TYPE_DIGITAL = 'digital';

    private const DIGITAL_READY_AFTER_SELL = 'ready_after_sell';

    private const POS_ORDER_ID_BASE = 100000;

    private const DISCOUNT_TYPE_COUPON = 'coupon_discount';

    private const COUPON_BEARER_INHOUSE = 'inhouse';

    /** @var array<int, string> */
    private const FIRST_ORDER_ID = 100001;

    private const CUSTOMER_TYPE_CUSTOMER = 'customer';

    private const TAX_MODEL_INCLUDE = 'include';

    private const TAX_MODEL_EXCLUDE = 'exclude';

    private const PAYMENT_METHODS_PAID_ON_SETTLEMENT = ['cash_on_delivery', 'offline_payment'];

    public function __construct(Order $order)
    {
        parent::__construct($order);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    /** @return array{setting: string, seller_column: string, vendor_wise_status_setting: ?string} */
    protected function commissionSettings(): array
    {
        return [
            'setting' => 'sales_commission',
            'seller_column' => 'sales_commission_percentage',
            'vendor_wise_status_setting' => null,
        ];
    }

    protected function billingInputSetting(): string
    {
        return 'billing_input_by_customer';
    }

    protected function shippingResponsibilitySetting(): string
    {
        return 'shipping_method';
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
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
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount);

        $this->applySearchFilter($query, $searchValue);

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);
        $this->applyListSort(query: $query, sortBy: $sortBy);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    private function applySearchFilter(Builder $query, ?string $searchValue): void
    {
        $query->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
            $inner->where('id', 'like', "%{$searchValue}%")
                ->orWhere('order_status', 'like', "%{$searchValue}%")
                ->orWhere('transaction_ref', 'like', "%{$searchValue}%")
                ->orWhereHas('seller.shop', fn (Builder $shop) => $shop->where('name', 'like', "%{$searchValue}%"));
        }));
    }

    protected function applyListSort(Builder $query, ?string $sortBy): void
    {
        match ($sortBy) {
            self::SORT_NEWEST => $query->latest('id'),
            self::SORT_OLDEST => $query->oldest('id'),
            self::SORT_LATEST_UPDATED => $query->latest('updated_at'),
            self::SORT_ORDER_ID_ASC => $query->orderBy('id'),
            self::SORT_ORDER_ID_DESC => $query->orderByDesc('id'),
            self::SORT_ORDER_DATE_ASC => $this->orderByColumn(query: $query, column: 'created_at', direction: self::SORT_ASCENDING),
            self::SORT_ORDER_DATE_DESC => $this->orderByColumn(query: $query, column: 'created_at', direction: self::SORT_DESCENDING),
            self::SORT_CUSTOMER_NAME_ASC => $this->orderByColumn(query: $query, column: $this->customerNameColumn(...), direction: self::SORT_ASCENDING),
            self::SORT_CUSTOMER_NAME_DESC => $this->orderByColumn(query: $query, column: $this->customerNameColumn(...), direction: self::SORT_DESCENDING),
            self::SORT_STORE_NAME_ASC => $this->orderByColumn(query: $query, column: $this->storeNameColumn(...), direction: self::SORT_ASCENDING),
            self::SORT_STORE_NAME_DESC => $this->orderByColumn(query: $query, column: $this->storeNameColumn(...), direction: self::SORT_DESCENDING),
            self::SORT_ORDER_AMOUNT_ASC => $this->orderByColumn(query: $query, column: 'order_amount', direction: self::SORT_ASCENDING),
            self::SORT_ORDER_AMOUNT_DESC => $this->orderByColumn(query: $query, column: 'order_amount', direction: self::SORT_DESCENDING),
            self::SORT_ORDER_STATUS_ASC => $this->orderByColumn(query: $query, column: 'order_status', direction: self::SORT_ASCENDING),
            self::SORT_ORDER_STATUS_DESC => $this->orderByColumn(query: $query, column: 'order_status', direction: self::SORT_DESCENDING),
            default => $query,
        };
    }

    private function orderByColumn(Builder $query, string|Closure $column, string $direction): Builder
    {
        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    private function customerNameColumn(QueryBuilder $users): QueryBuilder
    {
        return $users->from('users')
            ->selectRaw("CONCAT_WS(' ', users.f_name, users.l_name)")
            ->whereColumn('users.id', 'orders.customer_id')
            ->where('orders.is_guest', 0)
            ->limit(1);
    }

    private function storeNameColumn(QueryBuilder $shops): QueryBuilder
    {
        return $shops->from('shops')
            ->select('shops.name')
            ->where(fn (QueryBuilder $owner): QueryBuilder => $owner
                ->where(fn (QueryBuilder $vendorShop): QueryBuilder => $vendorShop
                    ->where('orders.seller_is', self::SELLER_IS_SELLER)
                    ->whereColumn('shops.seller_id', 'orders.seller_id')
                    ->where('shops.author_type', ShopService::AUTHOR_TYPE_VENDOR))
                ->orWhere(fn (QueryBuilder $inHouseShop): QueryBuilder => $inHouseShop
                    ->where('orders.seller_is', self::SELLER_IS_ADMIN)
                    ->where('shops.author_type', ShopService::AUTHOR_TYPE_ADMIN)))
            ->limit(1);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            ...$data,
            'business_module' => $this->businessModule(),
        ]);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $order = $this->query()->find($id);

        if ($order === null) {
            return null;
        }

        $order->update($data);

        return $order->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        return false;
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function updateOrInsert(array $params, array $data): bool
    {
        return (bool) $this->query()->updateOrInsert($params, $data);
    }

    public function delete(mixed $id): bool
    {
        $order = $this->query()->find($id);

        if ($order === null) {
            return false;
        }

        return (bool) $order->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    public function countPendingByBusinessModule(string $businessModule, ?int $sellerId = null): int
    {
        return $this->pendingByBusinessModuleQuery($businessModule, $sellerId)->count();
    }

    public function getRecentPendingByBusinessModule(string $businessModule, int $limit, array $relations = [], ?int $sellerId = null): Collection
    {
        return $this->pendingByBusinessModuleQuery($businessModule, $sellerId)
            ->with($relations)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function pendingByBusinessModuleQuery(string $businessModule, ?int $sellerId): Builder
    {
        $query = $this->model->newQuery()
            ->businessModule($businessModule)
            ->where('order_status', self::STATUS_PENDING);

        if ($sellerId !== null) {
            $query->where('seller_is', self::SELLER_IS_SELLER)->where('seller_id', $sellerId);
        }

        return $query;
    }

    public function countOrderGroups(array $filters = []): int
    {
        $query = $this->query();

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);

        return $query->select('order_group_id')->groupBy('order_group_id')->get()->count();
    }

    /** @return array<string, int> */
    public function getCouponOrderGroupCounts(int $customerId): array
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->whereNotNull('coupon_code')
            ->select('coupon_code', 'order_group_id')
            ->groupBy('coupon_code', 'order_group_id')
            ->get()
            ->countBy(static fn (Model $order): string => mb_strtolower((string) $order->coupon_code))
            ->all();
    }

    public function createPosOrder(
        int|string $orderId,
        array $cart,
        float $amount,
        float $totalTaxAmount,
        float $paidAmount,
        string $paymentType,
        string $addedBy,
        int $userId,
        int $sellerId,
    ): ?Model {
        $taxConfig = self::getTaxSystemType();

        return $this->create(data: [
            'id' => $orderId,
            'customer_id' => $userId,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'delivered',
            'seller_id' => $sellerId,
            'seller_is' => $addedBy,
            'payment_method' => $paymentType,
            'order_type' => 'POS',
            'checked' => 1,
            'total_tax_amount' => $totalTaxAmount,
            'tax_type' => $taxConfig['SystemTaxVatType'],
            'tax_model' => $taxConfig['is_included'] ? 'include' : 'exclude',
            'extra_discount' => $cart['ext_discount'] ?? 0,
            'extra_discount_type' => $cart['ext_discount_type'] ?? null,
            'order_amount' => currencyConverter(amount: $amount),
            'init_order_amount' => currencyConverter(amount: $amount),
            'paid_amount' => currencyConverter(amount: $paidAmount),
            'discount_amount' => $cart['coupon_discount'] ?? 0,
            'coupon_code' => $cart['coupon_code'] ?? null,
            'discount_type' => (isset($cart['coupon_code']) && $cart['coupon_code']) ? 'coupon_discount' : null,
            'coupon_discount_bearer' => $cart['coupon_bearer'] ?? 'inhouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function isOnlyDigital(object|array $order): bool
    {
        foreach ($this->getLoadedOrderLines(order: $order) as $detail) {
            $product = json_decode($detail->product_details);

            if (isset($product->product_type) && $product->product_type === self::PRODUCT_TYPE_PHYSICAL) {
                return false;
            }
        }

        return true;
    }

    public function isDigitalFileAfterSellUploaded(object|array $order): bool
    {
        foreach ($this->getLoadedOrderLines(order: $order) as $detail) {
            $product = json_decode($detail->product_details);

            if (isset($product->digital_product_type)
                && $product->digital_product_type == self::DIGITAL_PRODUCT_TYPE_READY_AFTER_SELL
                && $detail->digital_file_after_sell == null) {
                return false;
            }
        }

        return true;
    }

    /** @return array{is_service_available: bool, is_enabled: bool, is_assigned: bool, is_paid: bool, cod: mixed, has_coordinates: bool} */
    public function getDeliveryPartnerState(object|array $order, string $panel): array
    {
        $shippingAddress = $order['shipping_address_data'] ?? null;
        $isEnabled = deliveryPartnerAvailable(order: $order, panel: $panel);
        $isShipped = deliveryPartnerShipment(orderId: $order['id']) !== null;
        $isPaid = $order['payment_status'] == self::PAYMENT_STATUS_PAID;

        return [
            'is_service_available' => courierService() !== null,
            'is_enabled' => $isEnabled,
            'is_assigned' => $order['delivery_type'] == self::DELIVERY_TYPE_THIRD_PARTY
                || ($isShipped && $order['delivery_type'] != self::DELIVERY_TYPE_SELF),
            'is_paid' => $isPaid,
            'cod' => $isPaid ? 0 : ($order['order_amount'] ?? 0),
            'has_coordinates' => (float) ($shippingAddress?->latitude ?? 0) !== 0.0
                && (float) ($shippingAddress?->longitude ?? 0) !== 0.0,
        ];
    }

    private function getLoadedOrderLines(object|array $order): iterable
    {
        return $order instanceof Model && $order->relationLoaded('details')
            ? $order->details
            : ($order->orderDetails ?? []);
    }

    /** @return array{in_house: bool, seller_ids: array<int, int>} */
    public function getOwnersWithOrders(): array
    {
        $rows = $this->defaultOrderQuery()
            ->groupBy('seller_is', 'seller_id')
            ->get(['seller_is', 'seller_id']);

        $sellerIds = [];
        $inHouse = false;
        foreach ($rows as $row) {
            $row->seller_is === self::SELLER_IS_ADMIN
                ? $inHouse = true
                : $sellerIds[] = (int) $row->seller_id;
        }

        return ['in_house' => $inHouse, 'seller_ids' => $sellerIds];
    }

    /**
     * @param  array<int, int>  $sellerIds
     * @return array<int|string, int>
     */
    public function countBySellers(array $sellerIds, bool $includeInHouse = false): array
    {
        if ($sellerIds === [] && ! $includeInHouse) {
            return [];
        }

        $counts = [];

        if ($includeInHouse) {
            $counts[self::OWNER_IN_HOUSE] = $this->getInHouseCount();
        }

        if ($sellerIds !== []) {
            $rows = $this->query()
                ->where('seller_is', self::SELLER_IS_SELLER)
                ->whereIn('seller_id', $sellerIds)
                ->groupBy('seller_id')
                ->selectRaw('seller_id, COUNT(*) AS total_order')
                ->get();

            foreach ($rows as $row) {
                $counts[(int) $row->seller_id] = (int) $row->total_order;
            }
        }

        return $counts;
    }

    /** @return array{total_order: int, ongoing: int, completed: int, returned: int, canceled: int, failed: int} */
    public function getStatusSummaryForCustomer(int $customerId): array
    {
        $counts = $this->query()
            ->where('customer_id', $customerId)
            ->where('is_guest', 0)
            ->select('order_status')
            ->selectRaw('count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $ongoing = 0;
        foreach (self::ONGOING_STATUSES as $status) {
            $ongoing += (int) ($counts[$status] ?? 0);
        }

        return [
            'total_order' => (int) $counts->sum(),
            'ongoing' => $ongoing,
            'completed' => (int) ($counts[self::STATUS_DELIVERED] ?? 0),
            'returned' => (int) ($counts[self::STATUS_RETURNED] ?? 0),
            'canceled' => (int) ($counts[self::STATUS_CANCELED] ?? 0),
            'failed' => (int) ($counts[self::STATUS_FAILED] ?? 0),
        ];
    }

    /** @return array{total_delivery: int, ongoing: int, completed: int, paused: int} */
    public function getDeliveryStatusSummary(int $deliverymanId): array
    {
        return $this->summarizeDeliveryStatuses(query: $this->query(), deliverymanId: $deliverymanId);
    }

    /** @return array{total_delivery: int, ongoing: int, completed: int, paused: int} */
    public function getDeliveryStatusSummaryInAnyBusinessModule(int $deliverymanId): array
    {
        return $this->summarizeDeliveryStatuses(query: $this->model->newQuery(), deliverymanId: $deliverymanId);
    }

    /** @return array{total_delivery: int, ongoing: int, completed: int, paused: int} */
    private function summarizeDeliveryStatuses(Builder $query, int $deliverymanId): array
    {
        $ongoingPlaceholders = implode(',', array_fill(0, count(self::ONGOING_STATUSES), '?'));

        $row = $query
            ->where('delivery_man_id', $deliverymanId)
            ->selectRaw('count(*) as total_delivery')
            ->selectRaw('sum(case when order_status = ? then 1 else 0 end) as completed', [self::STATUS_DELIVERED])
            ->selectRaw("sum(case when order_status in ({$ongoingPlaceholders}) then 1 else 0 end) as ongoing", self::ONGOING_STATUSES)
            ->selectRaw('sum(case when is_pause = 1 then 1 else 0 end) as paused')
            ->first();

        return [
            'total_delivery' => (int) ($row->total_delivery ?? 0),
            'ongoing' => (int) ($row->ongoing ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'paused' => (int) ($row->paused ?? 0),
        ];
    }

    public function isShippingMethodUsedInAnyBusinessModule(int $shippingMethodId): bool
    {
        return $this->model->newQuery()->where('shipping_method_id', $shippingMethodId)->exists();
    }

    public function findInAnyBusinessModule(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function updateDeliveryPartnerInAnyBusinessModule(int|string $id, string $serviceName, string $trackingId): ?Model
    {
        $order = $this->findInAnyBusinessModule(id: $id);

        if ($order === null) {
            return null;
        }

        $order->update([
            'delivery_type' => self::DELIVERY_TYPE_THIRD_PARTY,
            'delivery_service_name' => $serviceName,
            'third_party_delivery_tracking_id' => $trackingId,
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
        ]);

        return $order;
    }

    public function advanceToOutForDeliveryInAnyBusinessModule(Model $order): bool
    {
        if (! in_array($order['order_status'], self::OUT_FOR_DELIVERY_ADVANCEABLE_STATUSES, true) || ! deliveryPartnerAssigned(order: $order)) {
            return false;
        }

        $order->update(['order_status' => self::STATUS_OUT_FOR_DELIVERY]);

        return true;
    }

    public function hasOngoingDeliveryInAnyBusinessModule(int $deliverymanId): bool
    {
        return $this->model->newQuery()
            ->where('delivery_man_id', $deliverymanId)
            ->where('order_status', self::STATUS_OUT_FOR_DELIVERY)
            ->where('is_pause', 0)
            ->exists();
    }

    public function getCustomerOrderList(
        int $customerId,
        string $sortDirection = 'desc',
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        ?string $statusGroup = null,
        ?string $orderIdSearch = null,
        ?string $orderStatus = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with([
                'details',
                'seller.shop',
                'latestEditHistory' => fn ($editHistory) => $editHistory->select([
                    'id', 'order_id', 'order_due_payment_method', 'order_due_amount',
                    'order_due_payment_status', 'order_return_amount', 'order_return_payment_status',
                ]),
            ])
            ->withSum('orderDetails', 'qty')
            ->where(['customer_id' => $customerId, 'is_guest' => '0'])
            ->when($statusGroup === self::STATUS_GROUP_ONGOING, fn (Builder $builder) => $builder->whereIn('order_status', self::ONGOING_STATUSES))
            ->when($statusGroup === self::STATUS_GROUP_PREVIOUS, fn (Builder $builder) => $builder->whereNotIn('order_status', self::ONGOING_STATUSES))
            ->when($orderIdSearch, fn (Builder $builder) => $builder->where('id', 'like', "%{$orderIdSearch}%"))
            ->when(in_array($orderStatus, self::CUSTOMER_LIST_STATUSES, true), fn (Builder $builder) => $builder->where('order_status', $orderStatus))
            ->orderBy('id', $sortDirection);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @return array<string, int> */
    public function getCustomerOrderCountsByBusinessModule(int $customerId, ?string $orderStatus = null): array
    {
        $counts = $this->model->newQuery()
            ->where(['customer_id' => $customerId, 'is_guest' => '0'])
            ->when(in_array($orderStatus, self::CUSTOMER_LIST_STATUSES, true), fn (Builder $builder) => $builder->where('order_status', $orderStatus))
            ->select('business_module')
            ->selectRaw('count(*) as total')
            ->groupBy('business_module')
            ->pluck('total', 'business_module');

        return [
            BusinessModule::RETAIL => (int) ($counts[BusinessModule::RETAIL] ?? 0),
            BusinessModule::WHOLESALE => (int) ($counts[BusinessModule::WHOLESALE] ?? 0),
        ];
    }

    /** @return array<string, int> */
    public function getSellerOrderCountsByBusinessModule(int $sellerId): array
    {
        return $this->model->newQuery()
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId])
            ->selectRaw('business_module, COUNT(*) as total')
            ->groupBy('business_module')
            ->pluck('total', 'business_module')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /** @return array<int, int> */
    public function getCustomerOrderedProductIds(int $customerId, int $orderLimit): array
    {
        return $this->query()
            ->with(['details:id,order_id,product_id'])
            ->where(['customer_id' => $customerId, 'is_guest' => '0'])
            ->latest('id')
            ->limit($orderLimit)
            ->get(['id'])
            ->flatMap(fn (Model $order): iterable => $order->details->pluck('product_id'))
            ->filter()
            ->map(static fn (mixed $productId): int => (int) $productId)
            ->unique()
            ->values()
            ->all();
    }

    public function findByDetailCustomer(int|string $id, int $customerId, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where('id', $id)
            ->whereHas('details', fn (Builder $details) => $details->where('customer_id', $customerId))
            ->first();
    }

    public function findLatestForCustomer(int $customerId): ?Model
    {
        return $this->query()
            ->with(['deliveryMan', 'orderStatusHistory' => fn ($history) => $history->latest()])
            ->where('customer_id', $customerId)
            ->latest()
            ->first();
    }

    public function findDeliveryManInfoBy(array $params): ?Model
    {
        return $this->query()
            ->with([
                'verificationImages',
                'details.product',
                'deliveryMan.rating',
                'deliveryManReview',
                'deliveryMan' => fn ($deliveryMan) => $deliveryMan->withCount('review'),
                'seller.shop',
            ])
            ->where($params)
            ->first();
    }

    public function matchesGuestPhone(Model $order, ?string $phoneNumber): bool
    {
        $shippingAddress = (array) ($order['shipping_address_data'] ?? []);
        $billingAddress = (array) ($order['billing_address_data'] ?? []);

        return ($order->shippingAddress && $order->shippingAddress->phone == $phoneNumber)
            || ($shippingAddress['phone'] ?? null) == $phoneNumber
            || ($order->billingAddress && $order->billingAddress->phone == $phoneNumber)
            || ($billingAddress['phone'] ?? null) == $phoneNumber;
    }

    public function generateUniqueReferenceId(): string
    {
        return rand(1000, 9999).'-'.Str::random(5).'-'.time();
    }

    public function generateNewOrderId(?int $latestOrderDetailOrderId): int
    {
        $orderId = $latestOrderDetailOrderId ?? self::FIRST_ORDER_ID;

        if ($this->model->newQuery()->find($orderId)) {
            $orderId = $this->model->newQuery()->orderBy('id', 'DESC')->first()->id + 1;
        }

        return $orderId;
    }

    public function calculateAdminCommission(iterable $groupCartItems, ?Model $coupon, float|int $couponDiscount, ?Model $seller): float
    {
        $cartItems = collect($groupCartItems);
        $firstCartItem = $cartItems->first();

        $appliedCouponDiscount = 0;
        if ($coupon && ($coupon->seller_id == null || $coupon->seller_id == '0' || $coupon->seller_id == $firstCartItem->seller_id)) {
            $appliedCouponDiscount = $coupon->coupon_type == CouponService::TYPE_FREE_DELIVERY ? 0 : $couponDiscount;
        }

        $subTotal = 0;
        $totalDiscountOnProduct = 0;
        foreach ($cartItems as $cartItem) {
            $subTotal += $cartItem->price * $cartItem->quantity;
            $totalDiscountOnProduct += $cartItem->discount * $cartItem->quantity;
        }
        $orderTotal = $subTotal - $totalDiscountOnProduct - $appliedCouponDiscount;

        if ($firstCartItem['seller_is'] != self::SELLER_IS_SELLER) {
            return 0.0;
        }

        $commissionSettings = $this->commissionSettings();
        $isSellerRateApplicable = isset($seller)
            && $seller[$commissionSettings['seller_column']] !== null
            && ($commissionSettings['vendor_wise_status_setting'] === null || getWebConfig(name: $commissionSettings['vendor_wise_status_setting']));
        $commissionPercentage = $isSellerRateApplicable
            ? $seller[$commissionSettings['seller_column']]
            : getWebConfig(name: $commissionSettings['setting']);

        return (float) str_replace(',', '', number_format(($orderTotal / 100) * $commissionPercentage, 2));
    }

    /** @return array<string, mixed> */
    public function createPlacedOrder(
        int $orderId,
        string $orderGroupId,
        int|string $customerId,
        int $isGuest,
        array $vendorCart,
        array $orderData,
        float $adminCommission,
        ?Model $shippingAddress,
        ?Model $billingAddress,
    ): array {
        $taxConfig = self::getTaxSystemType(businessModule: $this->businessModule());
        $isBillingInputByCustomer = getWebConfig($this->billingInputSetting());

        $columns = [
            'id' => $orderId,
            'business_module' => $this->businessModule(),
            'verification_code' => rand(100000, 999999),
            'customer_id' => $customerId,
            'is_guest' => $isGuest,
            'seller_id' => $vendorCart['seller_id'],
            'seller_is' => $vendorCart['seller_is'],
            'customer_type' => self::CUSTOMER_TYPE_CUSTOMER,
            'payment_status' => $orderData['payment_status'],
            'order_status' => $orderData['order_status'],
            'payment_method' => $orderData['payment_method'],
            'transaction_ref' => $orderData['transaction_ref'] ?? null,
            'payment_by' => $orderData['payment_by'] ?? null,
            'payment_note' => $orderData['payment_note'] ?? null,
            'order_group_id' => $orderGroupId,
            'discount_amount' => $vendorCart['coupon_discount'],
            'discount_type' => $vendorCart['discount_type'],
            'coupon_code' => $vendorCart['coupon_code'],
            'coupon_discount_bearer' => $vendorCart['coupon_bearer'],
            'order_amount' => $vendorCart['order_amount_with_tax'] - $vendorCart['refer_and_earn_discount'],
            'init_order_amount' => $vendorCart['order_amount_with_tax'] - $vendorCart['refer_and_earn_discount'],
            'total_tax_amount' => $vendorCart['total_tax_amount'],
            'tax_type' => $taxConfig['SystemTaxVatType'],
            'tax_model' => $taxConfig['is_included'] ? self::TAX_MODEL_INCLUDE : self::TAX_MODEL_EXCLUDE,
            'bring_change_amount' => $orderData['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY ? $orderData['bring_change_amount'] ?? 0 : null,
            'bring_change_amount_currency' => $orderData['bring_change_amount_currency'] ?? null,
            'admin_commission' => $adminCommission,
            'shipping_address' => $vendorCart['shipping_address_id'],
            'shipping_address_data' => $shippingAddress,
            'billing_address' => $isBillingInputByCustomer ? $vendorCart['billing_address_id'] : null,
            'billing_address_data' => $isBillingInputByCustomer ? $billingAddress : null,
            'shipping_responsibility' => getWebConfig(name: $this->shippingResponsibilitySetting()),
            'shipping_cost' => $vendorCart['shipping_cost'],
            'extra_discount' => $vendorCart['free_delivery_discount'],
            'extra_discount_type' => $vendorCart['extra_discount_type'],
            'refer_and_earn_discount' => $vendorCart['refer_and_earn_discount'],
            'free_delivery_bearer' => $vendorCart['free_delivery_bearer'],
            'is_shipping_free' => $vendorCart['is_shipping_free'],
            'shipping_method_id' => $vendorCart['shipping_method_id'],
            'shipping_type' => $vendorCart['shipping_type'],
            'created_at' => now(),
            'updated_at' => now(),
            'order_note' => $orderData['order_note'] ?? null,
        ];

        $this->query()->insert($columns);

        return $columns;
    }

    public function createOfflinePaymentRecord(int $orderId, array $paymentInfo): bool
    {
        return $this->model->newFromBuilder(['id' => $orderId])->offlinePayments()->insert([
            'order_id' => $orderId,
            'payment_info' => json_encode($paymentInfo),
            'created_at' => Carbon::now(),
        ]);
    }

    /** @return array<int, object> */
    public function getPlacedOrderNotifications(Model $order, ?Model $seller, bool $isCustomerNotified): array
    {
        $notifications = [];

        if ($order['seller_is'] == self::SELLER_IS_SELLER && $seller !== null) {
            $notifications[] = (object) [
                'key' => self::NOTIFICATION_NEW_ORDER,
                'type' => self::NOTIFICATION_TYPE_SELLER,
                'order' => $order,
            ];
        }

        if ($isCustomerNotified) {
            $notifications[] = (object) [
                'key' => in_array($order['payment_method'], self::PAYMENT_METHODS_PAID_ON_SETTLEMENT) ? self::STATUS_PENDING : self::STATUS_CONFIRMED,
                'type' => self::NOTIFICATION_TYPE_CUSTOMER,
                'order' => $order,
            ];
        }

        return $notifications;
    }

    public function getReferredCustomerOrderedNotification(Model $order): object
    {
        return (object) [
            'key' => self::NOTIFICATION_REFERRED_CUSTOMER_ORDERED,
            'type' => self::NOTIFICATION_TYPE_PROMOTER,
            'order' => $order,
        ];
    }

    /**
     * @param  array<int, object>  $notifications
     * @param  array<int, array{email: ?string, data: array<string, mixed>}>  $mails
     */
    public function dispatchPlacedOrderEvents(array $notifications, array $mails): void
    {
        foreach ($notifications as $notification) {
            event(new OrderPlacedEvent(notification: $notification));
        }

        foreach ($mails as $mail) {
            try {
                event(new OrderPlacedEvent(email: $mail['email'], data: $mail['data']));
            } catch (Exception) {
            }
        }
    }

    public function dispatchEditDuePaymentNotification(object|array $order, ?Model $seller): void
    {
        if ($order['seller_is'] != self::SELLER_IS_SELLER || $seller === null) {
            return;
        }

        event(new OrderEditDuePaymentEvent(notification: (object) [
            'key' => self::NOTIFICATION_EDIT_DUE_PAYMENT,
            'type' => self::NOTIFICATION_TYPE_SELLER,
            'order' => $order,
        ]));
    }

    public function canPayEditDueFromWallet(object|array $order, object|array $customer): bool
    {
        return ! (($customer['wallet_balance'] ?? 0) < $order['edit_due_amount']);
    }

    public function settleEditDueAmount(object|array $order): int
    {
        return $this->query()->where('id', $order['id'])->update([
            'edit_due_amount' => 0,
            'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
            'payment_status' => self::PAYMENT_STATUS_PAID,
        ]);
    }

    private function defaultOrderQuery(): Builder
    {
        return $this->query()->where('order_type', self::ORDER_TYPE_DEFAULT);
    }

    public function getInHouseCount(): int
    {
        return $this->query()->where('seller_is', self::SELLER_IS_ADMIN)->count();
    }

    /** @return array{active: int, inactive: int}|array<string, int> */
    public function getStatusCounts(?string $searchValue = null, array $filters = []): array
    {
        $query = $this->query()
            ->when($searchValue, fn (Builder $builder) => $builder->where('id', 'like', "%{$searchValue}%"));

        $filters = $this->applyKeyedFilters($query, $filters);
        unset($filters['order_status']);

        $this->applyFilters($query, $filters);

        return $query->select('order_status')
            ->selectRaw('count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    /** @return array<int, array<int, array{name: string, thumbnail: string, variation: ?string, quantity: int, price: float}>> */
    public function getItemPreviews(iterable $orders): array
    {
        $previews = [];

        foreach ($orders as $order) {
            $previews[(int) $order['id']] = [];

            if (! $order->relationLoaded('details')) {
                continue;
            }

            foreach ($order->details as $detail) {
                $snapshot = json_decode((string) $detail['product_details'], true) ?? [];

                $previews[(int) $order['id']][] = [
                    'name' => (string) ($snapshot['name'] ?? ''),
                    'thumbnail' => getStorageImages(path: $snapshot['thumbnail_full_url'] ?? null, type: 'backend-product'),
                    'variation' => blank($detail['variant']) ? null : (string) $detail['variant'],
                    'quantity' => (int) $detail['qty'],
                    'price' => (float) $detail['price'] * (int) $detail['qty'] - (float) $detail['discount'],
                ];
            }
        }

        return $previews;
    }

    /** @return array<int|string, array<string, mixed>> */
    public function getTotalPriceSummaries(iterable $orders): array
    {
        $summaries = [];
        foreach ($orders as $order) {
            $summaries[$order->id] = OrderManager::getOrderTotalPriceSummary(order: $order);
        }

        return $summaries;
    }

    /** @return array<string, int> */
    public function getStatusTally(iterable $orders): array
    {
        $tally = array_fill_keys(self::STATUS_TALLY_ORDER, 0);
        foreach ($orders as $order) {
            if (isset($tally[$order->order_status])) {
                $tally[$order->order_status]++;
            }
        }

        return $tally;
    }

    /** @return array{from: string, to: string} */
    public function getExportDateRange(?string $dateType, ?string $from, ?string $to): array
    {
        return match ($dateType) {
            self::DATE_TYPE_THIS_YEAR => ['from' => date('Y-01-01'), 'to' => date('Y-12-31')],
            self::DATE_TYPE_THIS_MONTH => ['from' => date('Y-m-01'), 'to' => date('Y-m-t')],
            self::DATE_TYPE_THIS_WEEK => [
                'from' => Carbon::now()->subDays(7)->startOfWeek()->format('Y-m-d'),
                'to' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            ],
            default => ['from' => $from ?? '', 'to' => $to ?? ''],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applyKeyedFilters(Builder $query, array $filters): array
    {
        $this->applyDateFilter(
            query: $query,
            dateType: $filters[self::FILTER_DATE_TYPE] ?? null,
            dateRange: $filters[self::FILTER_DATE_RANGE] ?? [],
        );

        $query->when($filters[self::FILTER_ID_BEFORE] ?? null, fn (Builder $builder, $id) => $builder->where('id', '<', $id))
            ->when($filters[self::FILTER_ID_AFTER] ?? null, fn (Builder $builder, $id) => $builder->where('id', '>', $id))
            ->when($filters[self::FILTER_ID_EXCEPT] ?? null, fn (Builder $builder, $ids) => $builder->whereNotIn('id', (array) $ids));

        $this->applySettlementFilter($query, $filters[self::FILTER_SETTLEMENT] ?? null);
        $this->applySourceFilter($query, $filters[self::FILTER_SOURCE] ?? null);

        if (array_key_exists(self::FILTER_DELIVERYMAN_SELLER_ID, $filters)) {
            $sellerId = $filters[self::FILTER_DELIVERYMAN_SELLER_ID];
            $query->whereHas('deliveryMan', static fn (Builder $deliveryMan): Builder => $deliveryMan->where('seller_id', $sellerId));
        }

        unset(
            $filters[self::FILTER_DATE_TYPE],
            $filters[self::FILTER_DATE_RANGE],
            $filters[self::FILTER_ID_BEFORE],
            $filters[self::FILTER_ID_AFTER],
            $filters[self::FILTER_ID_EXCEPT],
            $filters[self::FILTER_SETTLEMENT],
            $filters[self::FILTER_DELIVERYMAN_SELLER_ID],
            $filters[self::FILTER_SOURCE],
        );

        return $filters;
    }

    private function applySourceFilter(Builder $query, ?string $source): void
    {
        if ($source === null) {
            return;
        }

        if (in_array($source, [self::SOURCE_POS, self::ORDER_TYPE_DEFAULT], true)) {
            $query->where('order_type', $source);

            return;
        }

        if (in_array($source, [self::SELLER_IS_ADMIN, self::SELLER_IS_SELLER], true)) {
            $query->where('seller_is', $source);
        }
    }

    private function applySettlementFilter(Builder $query, ?array $settlements): void
    {
        if (empty($settlements)) {
            return;
        }

        $hasDue = in_array(self::SETTLEMENT_DUE, $settlements, true);
        $hasReturn = in_array(self::SETTLEMENT_RETURN, $settlements, true);

        $query->where('edited_status', 1)
            ->when($hasDue && ! $hasReturn, fn (Builder $builder) => $builder->where('edit_due_amount', '>', 0))
            ->when($hasReturn && ! $hasDue, fn (Builder $builder) => $builder->where('edit_return_amount', '>', 0))
            ->when($hasDue && $hasReturn, fn (Builder $builder) => $builder->where(
                fn (Builder $inner) => $inner->where('edit_due_amount', '>', 0)->orWhere('edit_return_amount', '>', 0),
            ));
    }

    public function getCustomerAppOrderList(
        int|string $customerId,
        ?string $statusGroup = null,
        ?string $listType = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $statuses = self::CUSTOMER_APP_STATUS_GROUPS[$statusGroup] ?? null;

        $query = $this->query()
            ->with($relations)
            ->withSum('details as order_details_count', 'qty')
            ->where(['customer_id' => $customerId, 'is_guest' => '0'])
            ->when($statuses !== null, fn (Builder $builder) => $builder->whereIn('order_status', $statuses))
            ->when($listType === self::LIST_TYPE_REORDER, fn (Builder $builder) => $builder->where('order_type', self::ORDER_TYPE_DEFAULT))
            ->orderBy('id', 'desc');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function findForCustomerOwner(int|string $id, int|string $customerId, bool $isGuest, array $relations = [], array $withCount = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->where(['id' => $id, 'customer_id' => $customerId, 'is_guest' => (int) $isGuest])
            ->first();
    }

    public function findForSeller(int|string $id, int $sellerId, array $relations = [], array $withCount = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->where(['id' => $id, 'seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId])
            ->first();
    }

    public function findTrackingBy(array $params): ?Model
    {
        return $this->query()
            ->with(['deliveryMan', 'orderStatusHistory' => fn ($history) => $history->latest()])
            ->where($params)
            ->first();
    }

    public function findHavingDetailsBy(array $params, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where($params)
            ->whereHas('details')
            ->first();
    }

    public function matchesGuestPhoneExactly(Model $order, ?string $phoneNumber): bool
    {
        if ($phoneNumber === null || $phoneNumber === '') {
            return false;
        }

        $phoneCandidates = [
            $order->shippingAddress?->phone,
            ((array) ($order['shipping_address_data'] ?? []))['phone'] ?? null,
            $order->billingAddress?->phone,
            ((array) ($order['billing_address_data'] ?? []))['phone'] ?? null,
        ];

        foreach ($phoneCandidates as $phoneCandidate) {
            if ($phoneCandidate !== null && (string) $phoneCandidate === $phoneNumber) {
                return true;
            }
        }

        return false;
    }

    public function isCancellableByCustomer(object|array $order): bool
    {
        return $order['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY && $order['order_status'] == self::STATUS_PENDING;
    }

    /**
     * @param  array<int, int>  $sellerIds
     * @return array<int, int>
     */
    public function getCountsBySeller(array $sellerIds): array
    {
        if ($sellerIds === []) {
            return [];
        }

        return $this->query()
            ->where('seller_is', self::SELLER_IS_SELLER)
            ->whereIn('seller_id', $sellerIds)
            ->selectRaw('seller_id, COUNT(*) AS seller_count')
            ->groupBy('seller_id')
            ->pluck('seller_count', 'seller_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /** @return array<string, int> */
    public function getStatusCountsForSeller(int $sellerId, ?string $dateType = null): array
    {
        $query = $this->query()->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId]);

        $this->applyDateFilter(
            query: $query,
            dateType: $dateType === self::DATE_TYPE_CUSTOM ? null : $dateType,
            dateRange: [],
        );

        $counts = $query->select('order_status')
            ->selectRaw('count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $statistics = [];
        foreach (self::SELLER_STATISTICS_STATUSES as $status) {
            $statistics[$status] = (int) ($counts[$status] ?? 0);
        }

        return $statistics;
    }

    public function getSellerOrderList(
        int $sellerId,
        ?string $searchValue = null,
        array $filters = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        string $sortBy = self::SORT_NEWEST,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId]);

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);
        $this->applySellerOrderSearch(query: $query, searchValue: $searchValue);
        $this->applyListSort(query: $query, sortBy: $sortBy);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function assignDeliveryMan(int|string $orderId, ?int $sellerId, int|string $deliveryManId): ?Model
    {
        $order = $sellerId === null
            ? $this->find(id: $orderId, relations: ['deliveryMan'])
            : $this->findForSeller(id: $orderId, sellerId: $sellerId, relations: ['deliveryMan']);

        if ($order === null) {
            return null;
        }

        if ($order['delivery_man_id'] != $deliveryManId) {
            $order->deliveryman_assigned_at = Carbon::now();
        }

        $order->delivery_man_id = $deliveryManId;
        $order->delivery_type = self::DELIVERY_TYPE_SELF;
        $order->delivery_service_name = null;
        $order->third_party_delivery_tracking_id = null;
        $order->save();

        return $order;
    }

    public function hasExpectedDeliveryDateChanged(object|array $order, ?string $expectedDeliveryDate): bool
    {
        return ! empty($expectedDeliveryDate) && $order['expected_delivery_date'] != $expectedDeliveryDate;
    }

    public function updateDeliverySchedule(Model $order, mixed $deliverymanCharge, ?string $expectedDeliveryDate): bool
    {
        $order->deliveryman_charge = $deliverymanCharge;
        $order->expected_delivery_date = $expectedDeliveryDate;

        return $order->save();
    }

    public function isCustomerAccountDeleted(Model $order): bool
    {
        return ! $order['is_guest'] && empty($order->customer);
    }

    public function getPaymentStatusUpdateBlocker(Model $order, string $paymentStatus): ?string
    {
        if ($this->isCustomerAccountDeleted(order: $order)) {
            return self::REASON_CUSTOMER_ACCOUNT_DELETED;
        }

        if ($order['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order['order_status'] != self::STATUS_DELIVERED
            && $paymentStatus == self::PAYMENT_STATUS_PAID) {
            return self::REASON_CASH_ON_DELIVERY_NOT_DELIVERED;
        }

        return null;
    }

    public function getSettlementBlocker(Model $order, ?string $orderStatus): ?string
    {
        return match (true) {
            $this->isOfflinePaymentUnconfirmed(order: $order) => self::REASON_OFFLINE_PAYMENT_UNCONFIRMED,
            $this->hasUnconfirmedEditDuePayment(order: $order) => self::REASON_EDIT_DUE_PAYMENT_UNCONFIRMED,
            $this->hasPendingEditReturnPayment(order: $order) => self::REASON_EDIT_RETURN_PAYMENT_UNCONFIRMED,
            $orderStatus == self::STATUS_DELIVERED
                && $this->hasUnpaidCashOnDeliveryEditDue(order: $order, isInHouseShippingOnly: true) => self::REASON_EDIT_DUE_UNPAID_BEFORE_DELIVERY,
            default => null,
        };
    }

    public function getStatusChangeBlocker(Model $order, string $orderStatus, string $guardSet): ?string
    {
        $isVendorGuardSet = $guardSet === self::STATUS_CHANGE_GUARDS_VENDOR;
        $isDelivering = $orderStatus === self::STATUS_DELIVERED;

        return match (true) {
            ! $isVendorGuardSet
                && $this->isAlreadyDelivered(order: $order, orderStatus: $orderStatus, allowsReversal: true) => self::REASON_ALREADY_DELIVERED,
            $this->isCustomerAccountDeleted(order: $order) => self::REASON_CUSTOMER_ACCOUNT_DELETED,
            $this->isOfflinePaymentUnconfirmed(order: $order) => self::REASON_OFFLINE_PAYMENT_UNCONFIRMED,
            $this->hasUnconfirmedEditDuePayment(order: $order) => self::REASON_EDIT_DUE_PAYMENT_UNCONFIRMED,
            $this->hasPendingEditReturnPayment(order: $order) => self::REASON_EDIT_RETURN_PAYMENT_UNCONFIRMED,
            $isVendorGuardSet && $isDelivering
                && $order['payment_method'] != self::PAYMENT_METHOD_CASH_ON_DELIVERY
                && $order['payment_status'] != self::PAYMENT_STATUS_PAID => self::REASON_PAYMENT_UNCONFIRMED_BEFORE_DELIVERY,
            $isDelivering
                && $this->hasUnpaidCashOnDeliveryEditDue(order: $order, isInHouseShippingOnly: $isVendorGuardSet) => self::REASON_EDIT_DUE_UNPAID_BEFORE_DELIVERY,
            $isVendorGuardSet && $this->isAlreadyDelivered(order: $order) => self::REASON_ALREADY_DELIVERED,
            $isDelivering && $this->hasPendingDigitalFileUpload(order: $order) => self::REASON_DIGITAL_FILE_PENDING,
            default => null,
        };
    }

    public function isOfflinePaymentUnconfirmed(object|array $order): bool
    {
        return $order['payment_method'] == self::PAYMENT_METHOD_OFFLINE && $order['payment_status'] == self::PAYMENT_STATUS_UNPAID;
    }

    public function lockForEditSettlement(int|string $id, ?int $sellerId = null): ?Model
    {
        return $this->query()
            ->when($sellerId !== null, fn (Builder $builder) => $builder->where(['seller_is' => self::SELLER_IS_SELLER, 'seller_id' => $sellerId]))
            ->lockForUpdate()
            ->find($id);
    }

    public function isPaymentHeldInPendingWallet(object|array $order): bool
    {
        return ! in_array($order['payment_method'], self::PAYMENT_METHODS_PAID_ON_SETTLEMENT, true);
    }

    public function matchesEditReturnAmount(object|array $order, mixed $amount): bool
    {
        $submittedAmount = is_scalar($amount) ? trim((string) $amount) : '';
        $storedAmount = is_float($order['edit_return_amount'])
            ? number_format($order['edit_return_amount'], self::EDIT_AMOUNT_SCALE, '.', '')
            : trim((string) $order['edit_return_amount']);

        return preg_match(self::PLAIN_DECIMAL_PATTERN, $submittedAmount) === 1
            && preg_match(self::PLAIN_DECIMAL_PATTERN, $storedAmount) === 1
            && bccomp($submittedAmount, $storedAmount, self::EDIT_AMOUNT_SCALE) === 0;
    }

    public function getEditReturnSettlementBlocker(Model $order, ?Model $latestEditHistory): ?string
    {
        return match (true) {
            ! ($order['edit_return_amount'] > 0),
            $latestEditHistory?->order_return_payment_status !== self::EDIT_RETURN_PAYMENT_STATUS_PENDING => self::REASON_EDIT_RETURN_NOT_PENDING,
            $this->isAlreadyDelivered(order: $order) => self::REASON_ALREADY_DELIVERED,
            default => null,
        };
    }

    public function getEditDueCashOnDeliverySwitchBlocker(Model $order, ?Model $latestEditHistory, mixed $dueAmount): ?string
    {
        return match (true) {
            $dueAmount != $order['edit_due_amount'] => self::REASON_EDIT_DUE_AMOUNT_MISMATCH,
            ! ($order['edit_due_amount'] > 0) => self::REASON_EDIT_DUE_NOT_UNPAID,
            $latestEditHistory === null => self::REASON_EDIT_HISTORY_ABSENT,
            $latestEditHistory->order_due_payment_status !== self::EDIT_DUE_PAYMENT_STATUS_UNPAID => self::REASON_EDIT_DUE_NOT_UNPAID,
            $latestEditHistory->order_due_payment_method === self::PAYMENT_METHOD_CASH_ON_DELIVERY => self::REASON_EDIT_DUE_ALREADY_CASH_ON_DELIVERY,
            $this->isAlreadyDelivered(order: $order) => self::REASON_ALREADY_DELIVERED,
            default => null,
        };
    }

    public function canMarkEditDueAsPaid(Model $order, ?Model $latestEditHistory): bool
    {
        return $order['edit_due_amount'] > 0
            && $latestEditHistory?->order_due_payment_status === self::EDIT_DUE_PAYMENT_STATUS_UNPAID;
    }

    public function getDeliveryManOwnerId(object|array $order): int
    {
        return $order['seller_is'] == self::SELLER_IS_SELLER && $order['shipping_responsibility'] == self::SHIPPING_RESPONSIBILITY_SELLERWISE
            ? (int) $order['seller_id']
            : self::IN_HOUSE_DELIVERY_MAN_OWNER_ID;
    }

    private function hasUnconfirmedEditDuePayment(Model $order): bool
    {
        return $order['payment_method'] !== self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order['edit_due_amount'] > 0
            && $order->latestEditHistory?->order_due_payment_method !== self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order->latestEditHistory?->order_due_payment_status == self::EDIT_DUE_PAYMENT_STATUS_UNPAID;
    }

    private function hasPendingEditReturnPayment(Model $order): bool
    {
        return $order['payment_method'] !== self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order['edit_return_amount'] > 0
            && $order->latestEditHistory?->order_due_payment_method !== self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order->latestEditHistory?->order_return_payment_status == self::EDIT_RETURN_PAYMENT_STATUS_PENDING;
    }

    private function hasUnpaidCashOnDeliveryEditDue(Model $order, bool $isInHouseShippingOnly): bool
    {
        return $order['edit_due_amount'] > 0
            && $order->latestEditHistory?->order_due_payment_method == self::PAYMENT_METHOD_CASH_ON_DELIVERY
            && $order->latestEditHistory?->order_due_payment_status == self::EDIT_DUE_PAYMENT_STATUS_UNPAID
            && (! $isInHouseShippingOnly || $order['shipping_responsibility'] == self::SHIPPING_RESPONSIBILITY_INHOUSE);
    }

    public function hasPendingDigitalFileUpload(Model $order): bool
    {
        foreach ($order['details'] as $orderDetail) {
            $productDetails = json_decode($orderDetail?->product_details ?? '', true);

            if (($productDetails['product_type'] ?? null) == self::PRODUCT_TYPE_DIGITAL
                && ($productDetails['digital_product_type'] ?? null) == self::DIGITAL_READY_AFTER_SELL
                && is_null($orderDetail['digital_file_after_sell'])) {
                return true;
            }
        }

        return false;
    }

    public function isAlreadyDelivered(object|array $order, ?string $orderStatus = null, bool $allowsReversal = false): bool
    {
        if ($order['order_status'] != self::STATUS_DELIVERED) {
            return false;
        }

        return ! $allowsReversal || ! in_array($orderStatus, self::REVERSIBLE_AFTER_DELIVERY_STATUSES);
    }

    /** @return array{deliveryman_charge: mixed, cash_in_hand: mixed} */
    public function getDeliveryManEarning(object|array $order): array
    {
        return [
            'deliveryman_charge' => $order['deliveryman_charge'] ?? 0,
            'cash_in_hand' => $order['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY ? $order['order_amount'] : 0,
        ];
    }

    public function findForDeliveryman(int|string $id, int $deliverymanId, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where(['id' => $id, 'delivery_man_id' => $deliverymanId])
            ->first();
    }

    public function lockForDeliveryman(int|string $id, int $deliverymanId, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where(['id' => $id, 'delivery_man_id' => $deliverymanId])
            ->lockForUpdate()
            ->first();
    }

    public function getDeliverymanCurrentList(
        int $deliverymanId,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->where('delivery_man_id', $deliverymanId)
            ->whereIn('order_status', self::ONGOING_STATUSES)
            ->orderBy('expected_delivery_date');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @param  array<int, string>  $dateRange */
    public function getDeliverymanOrderList(
        int $deliverymanId,
        ?string $searchValue = null,
        ?string $orderStatus = null,
        ?int $isPause = null,
        ?string $dateType = null,
        array $dateRange = [],
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->where('delivery_man_id', $deliverymanId)
            ->when(! empty($searchValue), fn (Builder $builder) => $builder->where(fn (Builder $matched) => $matched
                ->where('id', 'like', "%{$searchValue}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('phone', 'like', "%{$searchValue}%"))))
            ->when(! empty($orderStatus), fn (Builder $builder) => $builder->where('order_status', $orderStatus))
            ->when(in_array($isPause, [0, 1], true), fn (Builder $builder) => $builder->where('is_pause', $isPause));

        $this->applyDateFilter(query: $query, dateType: $dateType, dateRange: $dateRange);

        $query->latest();

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    /** @param  array<int, int|string>|null  $customerIds */
    public function getDeliverymanSearchList(
        int $deliverymanId,
        ?string $orderIdSearch = null,
        ?array $customerIds = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->where('delivery_man_id', $deliverymanId)
            ->when($orderIdSearch !== null, fn (Builder $builder) => $builder->where('id', 'like', "%{$orderIdSearch}%"))
            ->when($customerIds !== null, fn (Builder $builder) => $builder->whereIn('customer_id', $customerIds))
            ->orderBy('id');

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function getDeliverymanOrderHistory(
        int $deliverymanId,
        ?string $searchValue = null,
        array $filters = [],
        ?string $businessModule = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
    ): Collection|LengthAwarePaginator {
        $query = $this->deliverymanOrderHistoryQuery(
            deliverymanId: $deliverymanId,
            businessModule: $businessModule,
            relations: $relations,
        );

        $this->applySearchFilter($query, $searchValue);

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);
        $query->latest('id');

        if ($dataLimit === 'all') {
            return $query->get();
        }

        $appendFilters = $businessModule === null ? $filters : [...$filters, 'business_module' => $businessModule];

        return $query->paginate(perPage: (int) $dataLimit, page: $page)
            ->appends($this->listQueryString($searchValue, null, $appendFilters));
    }

    public function countDeliverymanOrderHistory(
        int $deliverymanId,
        ?string $searchValue = null,
        array $filters = [],
        ?string $businessModule = null,
    ): int {
        $query = $this->deliverymanOrderHistoryQuery(deliverymanId: $deliverymanId, businessModule: $businessModule);

        $this->applySearchFilter($query, $searchValue);

        $filters = $this->applyKeyedFilters($query, $filters);
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    private function deliverymanOrderHistoryQuery(int $deliverymanId, ?string $businessModule, array $relations = []): Builder
    {
        return $this->model->newQuery()
            ->with($relations)
            ->where('delivery_man_id', $deliverymanId)
            ->when($businessModule !== null, fn (Builder $builder) => $builder->where('business_module', $businessModule));
    }

    public function getDeliverymanCashCollection(Model $order): float|int
    {
        if ($order['payment_method'] == self::PAYMENT_METHOD_CASH_ON_DELIVERY) {
            return $order['order_amount'];
        }

        $latestEditHistory = $order->latestEditHistory;

        if ($latestEditHistory
            && $latestEditHistory->order_due_payment_status == self::PAYMENT_STATUS_PAID
            && $latestEditHistory->order_due_payment_method == self::PAYMENT_METHOD_CASH_ON_DELIVERY) {
            return $latestEditHistory->order_due_amount ?? 0;
        }

        return 0;
    }

    public function regenerateDeliveryVerificationCode(Model $order): ?string
    {
        $verificationCode = (string) random_int(self::VERIFICATION_CODE_MIN, self::VERIFICATION_CODE_MAX);
        $order->verification_code = $verificationCode;

        return $order->save() ? $verificationCode : null;
    }

    public function verifyDeliveryCode(Model $order, string $verificationCode): bool
    {
        if (! hash_equals((string) $order['verification_code'], $verificationCode)) {
            return false;
        }

        $order->verification_status = 1;

        return $order->save();
    }

    public function updateAddressData(Model $order, string $addressType, array $address): int
    {
        $column = match ($addressType) {
            self::ADDRESS_TYPE_SHIPPING => 'shipping_address_data',
            self::ADDRESS_TYPE_BILLING => 'billing_address_data',
            default => null,
        };

        if ($column === null) {
            return 0;
        }

        $isDetachingMirroredBilling = $addressType === self::ADDRESS_TYPE_BILLING && $this->isBillingSameAsShipping(order: $order);
        $currentAddress = $isDetachingMirroredBilling ? $this->getBillingAddressData(order: $order) : $order[$column];
        $changedAddress = [...$address, 'updated_at' => now()];
        $columns = [$column => json_encode(array_merge($currentAddress ? json_decode(json_encode($currentAddress), true) : [], $changedAddress))];

        if ($isDetachingMirroredBilling) {
            $columns['billing_address'] = null;
        }

        return $this->query()
            ->toBase()
            ->where('id', $order['id'])
            ->update($columns);
    }

    public function isBillingSameAsShipping(object|array $order): bool
    {
        $shippingAddressId = (int) $order['shipping_address'];

        if ($shippingAddressId > 0 && (int) $order['billing_address'] === $shippingAddressId) {
            return true;
        }

        return empty($order['billing_address'])
            && empty((array) ($order['billing_address_data'] ?? []))
            && ! empty((array) ($order['shipping_address_data'] ?? []))
            && (bool) getWebConfig(name: $this->billingInputSetting());
    }

    public function withResolvedBillingAddress(iterable|Model $orders): iterable|Model
    {
        foreach ($orders instanceof Model ? [$orders] : $orders as $order) {
            if ($order instanceof Model) {
                $order->setAttribute('billing_address_data', $this->getBillingAddressData(order: $order));
                $order->syncOriginalAttribute('billing_address_data');
            }
        }

        return $orders;
    }

    public function getBillingAddressData(object|array $order): ?object
    {
        $billingAddress = $this->isBillingSameAsShipping(order: $order)
            ? $order['shipping_address_data']
            : $order['billing_address_data'];

        return empty((array) ($billingAddress ?? [])) ? null : (object) $billingAddress;
    }

    /** @return array<string, mixed> */
    public function getSellerInvoiceViewData(Model $order): array
    {
        $billingAddress = $this->getBillingAddressData(order: $order);

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
            'orderTotalPriceSummary' => OrderManager::getOrderTotalPriceSummary(order: $order),
            'billingAddress' => $billingAddress,
            'billingAddressTypeLabel' => ShippingAddressService::getAddressTypeLabel(addressType: $billingAddress?->address_type ?? null),
            'shippingAddressTypeLabel' => ShippingAddressService::getAddressTypeLabel(addressType: $order['shipping_address_data']?->address_type ?? null),
            'productNames' => $order->details->mapWithKeys(fn ($detail) => [
                $detail->id => ($detail->product ?? json_decode($detail->product_details))->name,
            ])->all(),
        ];
    }

    public function syncInitialOrderAmount(object|array $order): int
    {
        return $this->query()->where('id', $order['id'])->update(['init_order_amount' => $order['order_amount']]);
    }

    public function dispatchStatusNotification(string $key, string $type, object|array $order): void
    {
        event(new OrderStatusEvent(key: $key, type: $type, order: $order));
    }

    public function dispatchEditNotifications(object|array $order, ?Model $seller, string $editedBy): void
    {
        if ($order['seller_is'] == self::SELLER_IS_SELLER && $editedBy == self::EDITOR_ADMIN && $seller !== null) {
            event(new OrderEditEvent(notification: (object) [
                'key' => self::NOTIFICATION_ORDER_EDIT,
                'type' => self::NOTIFICATION_TYPE_SELLER,
                'order' => $order,
            ]));
        }

        event(new OrderEditEvent(notification: (object) [
            'key' => self::NOTIFICATION_ORDER_EDIT,
            'type' => self::NOTIFICATION_TYPE_CUSTOMER,
            'order' => $order,
        ]));
    }

    public function generatePosOrderId(): int
    {
        $latestOrderId = (int) $this->model->newQuery()->orderByDesc('id')->lockForUpdate()->value('id');

        return max(self::POS_ORDER_ID_BASE, $latestOrderId) + 1;
    }

    public function calculatePosCartTotal(array $modifiedCart): float|int
    {
        $cartTotal = 0;

        foreach ($modifiedCart as $cartItem) {
            if (! is_array($cartItem)) {
                continue;
            }

            $cartTotal += $cartItem['discounted_price'] * $cartItem['quantity'];
            $cartTotal += $cartItem['applied_tax_amount'] * $cartItem['quantity'];
            $cartTotal -= $cartItem['coupon_discount'] ?? 0;
            $cartTotal -= $cartItem['extra_discount'] ?? 0;
        }

        return $cartTotal;
    }

    public function createSellerAppPosOrder(int $orderId, int $sellerId, array $input, float|int $orderAmount, float|int $paidAmount): bool
    {
        $taxConfig = self::getTaxSystemType();
        $cart = $input['cart'] ?? null;

        return $this->query()->insert([
            'id' => $orderId,
            'business_module' => $this->businessModule(),
            'customer_id' => $input['customer_id'],
            'customer_type' => self::CUSTOMER_TYPE_CUSTOMER,
            'payment_status' => self::PAYMENT_STATUS_PAID,
            'order_status' => self::STATUS_DELIVERED,
            'seller_id' => $sellerId,
            'seller_is' => self::SELLER_IS_SELLER,
            'payment_method' => $input['payment_method'] ?? null,
            'order_type' => self::SOURCE_POS,
            'checked' => 1,
            'extra_discount' => $input['extra_discount'] ?? 0,
            'extra_discount_type' => $input['extra_discount_type'] ?? null,
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'total_tax_amount' => $input['total_tax_amount'] ?? 0,
            'tax_type' => $taxConfig['SystemTaxVatType'],
            'paid_amount' => $paidAmount,
            'discount_amount' => currencyConverter(amount: $input['coupon_discount_amount'] ?? 0),
            'coupon_code' => $input['coupon_code'] ?? null,
            'discount_type' => (isset($cart['coupon_code']) && $cart['coupon_code']) ? self::DISCOUNT_TYPE_COUPON : null,
            'coupon_discount_bearer' => $cart['coupon_bearer'] ?? self::COUPON_BEARER_INHOUSE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applySellerOrderSearch(Builder $query, ?string $searchValue): void
    {
        if (! $searchValue) {
            return;
        }

        $keywords = explode(' ', trim($searchValue));

        $query->where(function (Builder $inner) use ($keywords, $searchValue): void {
            foreach ($keywords as $word) {
                $inner->orWhere('id', 'like', "%{$word}%")
                    ->orWhere('order_status', 'like', "%{$word}%")
                    ->orWhere('transaction_ref', 'like', "%{$word}%");
            }

            $inner->orWhereHas('customer', function (Builder $customer) use ($keywords, $searchValue): void {
                $customer->where(function (Builder $name) use ($keywords): void {
                    foreach ($keywords as $word) {
                        $name->orWhere('f_name', 'like', "%{$word}%")
                            ->orWhere('l_name', 'like', "%{$word}%")
                            ->orWhere('phone', 'like', "%{$word}%");
                    }
                })->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$searchValue}%"]);
            });

            $inner->orWhereHas('seller.shop', function (Builder $shop) use ($keywords): void {
                foreach ($keywords as $word) {
                    $shop->where('name', 'like', "%{$word}%");
                }
            });
        });
    }

    /** @param  array<int, string>  $dateRange */
    private function applyDateFilter(Builder $query, ?string $dateType, array $dateRange): void
    {
        match ($dateType) {
            self::DATE_TYPE_TODAY => $query->whereDate('created_at', Carbon::today()),
            self::DATE_TYPE_THIS_WEEK => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            self::DATE_TYPE_THIS_MONTH => $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year),
            self::DATE_TYPE_THIS_YEAR => $query->whereYear('created_at', Carbon::now()->year),
            self::DATE_TYPE_CUSTOM => count($dateRange) === 2
                ? $query->whereDate('created_at', '>=', $dateRange[0])->whereDate('created_at', '<=', $dateRange[1])
                : $query,
            default => $query,
        };
    }
}
