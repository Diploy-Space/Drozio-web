<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\ListRequest;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\SettingService;
use App\Utils\CustomerOrderListSections;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    private const LIST_LIMIT = 10;

    private const SORT_DIRECTION_DESC = 'desc';

    private const LIST_ROUTE = 'account-oder';

    private const IS_REGISTERED_CUSTOMER_ORDER = '0';

    private const VENDOR_ABILITY_TYPE = 'vendor';

    private const IN_HOUSE_ABILITY_TYPE = 'inhouse';

    private const TEMPORARY_CLOSE_STATUS = 'temporary_close';

    private const VACATION_STATUS = 'vacation_status';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly ProductRetailService $productRetailService,
        private readonly ReviewService $reviewService,
        private readonly SettingService $settingService,
    ) {}

    public function index(ListRequest $request): View
    {
        $customerId = (int) auth('customer')->id();
        $selectedStatus = $request['status'] ?? OrderService::STATUS_GROUP_ALL;

        $orders = $this->orderService->getCustomerOrderList(
            customerId: $customerId,
            sortDirection: self::SORT_DIRECTION_DESC,
            dataLimit: self::LIST_LIMIT,
            orderStatus: $selectedStatus,
        )->appends($request->only(['status']));
        $orderCountsByModule = $this->orderService->getCustomerOrderCountsByBusinessModule(customerId: $customerId);

        $orderSuccessIds = $this->pullOrderSuccessIds();

        return view(themeViewName(key: 'account_orders'), [
            'orders' => $orders,
            'orderRoutes' => customerOrderPageRoutes(businessModule: BusinessModule::RETAIL),
            'orderTotalAmounts' => $this->getOrderTotalAmounts(orders: $orders),
            'orderSuccessIds' => $orderSuccessIds,
            'exploreUrl' => route('home'),
            'moduleTabs' => customerProfileModuleTabs(
                menuKey: 'orders',
                activeModule: BusinessModule::RETAIL,
                counts: [...$orderCountsByModule, BusinessModule::RETAIL => $orders->total()],
                historyModules: array_keys(array_filter($orderCountsByModule)),
            ),
            'statusFilterOptions' => CustomerOrderListSections::getStatusFilterOptions(routeName: self::LIST_ROUTE, selectedStatus: $selectedStatus),
            'selectedStatusLabel' => CustomerOrderListSections::getStatusFilterLabel(selectedStatus: $selectedStatus),
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
        ]);
    }

    public function details(Request $request): View|RedirectResponse
    {
        $order = $this->findCustomerOrder(id: $request['id'], relations: [
            'deliveryManReview', 'customer', 'offlinePayments',
            'details.productAllStatus', 'details.refundRequest', 'details.product',
            'seller.shop',
        ]);

        if ($order === null) {
            return $this->redirectInvalidOrder();
        }

        $offlinePaymentMethods = $this->getActiveOfflinePaymentMethods();
        $offlinePaymentStatus = getWebConfig(name: 'offline_payment');
        $paymentGatewayList = $this->getPaymentGatewayList();
        $isPhysicalProduct = $order->details->contains(
            fn ($detail) => $detail->product?->product_type === OrderService::PRODUCT_TYPE_PHYSICAL
        );

        $this->attachReviewData(order: $order, reviews: $this->getCustomerProductReviews(order: $order, relations: []), fallsBackToFirstOrderReview: false);

        return view(themeViewName(key: 'account_order_details'), [
            'order' => $order,
            'refund_day_limit' => getWebConfig(name: 'refund_day_limit'),
            'current_date' => Carbon::now(),
            'paymentGatewayList' => $paymentGatewayList,
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery')['status'],
            'isPhysicalProduct' => $isPhysicalProduct,
            'offlinePaymentStatus' => $offlinePaymentStatus,
            'digitalPayment' => getWebConfig(name: 'digital_payment')['status'],
            'walletStatus' => getWebConfig(name: 'wallet_status'),
            'offlinePaymentMethods' => $offlinePaymentMethods,
            'availablePaymentMethod' => $this->getAvailablePaymentMethod(
                isPhysicalProduct: $isPhysicalProduct,
                paymentGatewayCount: count($paymentGatewayList),
                offlinePaymentStatus: $offlinePaymentStatus,
                offlinePaymentMethodCount: count($offlinePaymentMethods),
            ),
        ]);
    }

    public function vendorInfo(Request $request): View|RedirectResponse
    {
        $order = $this->findCustomerOrder(id: $request['id'], relations: ['seller.shop']);

        if ($order === null) {
            return $this->redirectInvalidOrder();
        }

        $productIds = $this->productRetailService->getActiveIds(filters: [
            'added_by' => $order->seller_is === OrderService::SELLER_IS_ADMIN
                ? ProductRetailService::ADDED_BY_IN_HOUSE
                : ProductRetailService::ADDED_BY_SELLER,
            'seller_id' => $order->seller_id,
        ]);
        $rating = $this->reviewService->getRatingBreakdownForProducts(productIds: $productIds);
        $vendorShop = $order->seller_is === OrderService::SELLER_IS_SELLER ? $order->seller?->shop : null;

        return view(themeViewName(key: 'seller_info'), [
            'avg_rating' => $rating['average_rating'],
            'product_count' => count($productIds),
            'rating_count' => $rating['rating_count'],
            'order' => $order,
            'rating_percentage' => $rating['positive_percentage'],
            'isVendorShopAvailable' => $vendorShop !== null,
            'isVendorChatOpen' => $vendorShop !== null
                && ! checkVendorAbility(type: self::VENDOR_ABILITY_TYPE, status: self::TEMPORARY_CLOSE_STATUS, vendor: $vendorShop),
            'isVendorVerified' => $vendorShop !== null && $order->seller->hasVerifiedBadge(),
            'vendorCard' => $this->getVendorCard(order: $order, vendorShop: $vendorShop),
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function getVendorCard(Model $order, ?Model $vendorShop): ?array
    {
        $isInHouse = $order->seller_is === OrderService::SELLER_IS_ADMIN;

        if (! $isInHouse && $vendorShop === null) {
            return null;
        }

        $abilityType = $isInHouse ? self::IN_HOUSE_ABILITY_TYPE : self::VENDOR_ABILITY_TYPE;
        $isTemporaryClosed = (bool) checkVendorAbility(type: $abilityType, status: self::TEMPORARY_CLOSE_STATUS, vendor: $vendorShop);
        $isOnVacation = (bool) checkVendorAbility(type: $abilityType, status: self::VACATION_STATUS, vendor: $vendorShop);

        return [
            'name' => $isInHouse ? getInHouseShopConfig(key: 'name') : $vendorShop->name,
            'is_verified' => $isInHouse ? isInHouseShopVerified() : (bool) $order->seller->hasVerifiedBadge(),
            'image' => getStorageImages(path: $isInHouse ? getInHouseShopConfig(key: 'image_full_url') : $vendorShop->image_full_url, type: 'shop'),
            'shop_url' => route('vendor-shop', ['slug' => $isInHouse ? getInHouseShopConfig(key: 'slug') : $vendorShop->slug]),
            'closed_label' => match (true) {
                $isTemporaryClosed => translate('Temporary OFF'),
                $isOnVacation => translate('Closed Now'),
                default => null,
            },
            'is_chat_open' => $isInHouse || ! $isTemporaryClosed,
            'is_temporarily_closed' => $isTemporaryClosed,
            'ability_type' => $abilityType,
            'chat_shop' => $isInHouse ? 0 : $vendorShop,
            'chat_user_type' => $isInHouse ? 'admin' : 'seller',
        ];
    }

    public function deliveryManInfo(Request $request): View|RedirectResponse
    {
        $order = $this->orderService->findDeliveryManInfoBy(params: [
            'id' => $request['id'],
            'customer_id' => auth('customer')->id(),
            'is_guest' => self::IS_REGISTERED_CUSTOMER_ORDER,
        ]);

        if ($order === null) {
            return $this->redirectInvalidOrder();
        }

        $this->attachProductTypeCheck(order: $order);

        return view(themeViewName(key: 'delivery_man_info'), [
            'delivered_count' => $this->orderService->count(filters: [
                'order_status' => OrderService::STATUS_DELIVERED,
                'delivery_man_id' => $order->delivery_man_id,
                'delivery_type' => OrderService::DELIVERY_TYPE_SELF,
            ]),
            'order' => $order,
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
            'deliveryPartnerShipment' => deliveryPartnerShipment($order->id),
        ]);
    }

    public function reviews(Request $request): View|RedirectResponse
    {
        $order = $this->findCustomerOrder(id: $request['id'], relations: [
            'deliveryManReview', 'customer', 'offlinePayments', 'details', 'seller.shop',
        ]);

        if ($order === null) {
            return $this->redirectInvalidOrder();
        }

        $this->attachReviewData(order: $order, reviews: $this->getCustomerProductReviews(order: $order, relations: ['reply']), fallsBackToFirstOrderReview: true);

        return view(themeViewName(key: 'order_details_review'), [
            'order' => $order,
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
        ]);
    }

    /** @return array<int, string> */
    private function getOrderTotalAmounts(iterable $orders): array
    {
        $totalAmounts = [];

        foreach ($orders as $order) {
            $totalAmounts[$order['id']] = webCurrencyConverter(
                amount: OrderManager::getOrderTotalPriceSummary(order: $order)['totalAmount'],
            );
        }

        return $totalAmounts;
    }

    /** @return array<int, int|string> */
    private function pullOrderSuccessIds(): array
    {
        $orderSuccessIds = session('order_success_ids');

        session()->forget('order_success_ids');

        return is_array($orderSuccessIds) ? $orderSuccessIds : [];
    }

    private function findCustomerOrder(mixed $id, array $relations): ?Model
    {
        return $this->orderService->findBy(
            params: [
                'id' => $id,
                'customer_id' => auth('customer')->id(),
                'is_guest' => self::IS_REGISTERED_CUSTOMER_ORDER,
            ],
            relations: $relations,
        );
    }

    private function redirectInvalidOrder(): RedirectResponse
    {
        ToastMagic::warning(translate('Invalid order'));

        return redirect()->route(self::LIST_ROUTE);
    }

    private function getCustomerProductReviews(Model $order, array $relations): Collection
    {
        return $this->reviewService->getList(
            dataLimit: 'all',
            relations: $relations,
            filters: [
                'customer_id' => auth('customer')->id(),
                'product_id' => $order->details->pluck('product_id')->unique()->values()->all(),
                'delivery_man_id' => null,
            ],
            sortBy: ReviewService::SORT_OLDEST_ID,
        );
    }

    private function attachReviewData(Model $order, Collection $reviews, bool $fallsBackToFirstOrderReview): void
    {
        $reviewsByProduct = $reviews->groupBy('product_id');

        foreach ($order->details as $detail) {
            $order['total_qty'] += $detail->qty;

            $productReviews = $reviewsByProduct[$detail['product_id']] ?? collect();
            $reviewData = $productReviews->last(fn ($review) => $review->order_id == $detail->order_id);

            if ($reviewData === null && $fallsBackToFirstOrderReview && isset($productReviews[0]) && $productReviews[0]['order_id'] != null) {
                $reviewData = $productReviews[0];
            }

            $detail['reviewData'] = $reviewData;
        }
    }

    private function attachProductTypeCheck(Model $order): void
    {
        foreach ($order->details as $detail) {
            if ($detail->product === null) {
                continue;
            }

            $order['product_type_check'] = $detail->product->product_type;

            if ($detail->product->product_type === OrderService::PRODUCT_TYPE_PHYSICAL) {
                break;
            }
        }
    }

    private function getAvailablePaymentMethod(
        bool $isPhysicalProduct,
        int $paymentGatewayCount,
        mixed $offlinePaymentStatus,
        int $offlinePaymentMethodCount,
    ): array {
        $availablePaymentMethod = [];
        $cashOnDeliveryStatus = getWebConfig(name: 'cash_on_delivery');

        if ($cashOnDeliveryStatus && $cashOnDeliveryStatus['status'] && $isPhysicalProduct) {
            $availablePaymentMethod = [OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY];
        }

        if (getWebConfig(name: 'digital_payment') && $paymentGatewayCount > 0) {
            $availablePaymentMethod = ['payment_gateways'];
        }

        if ($offlinePaymentStatus && $offlinePaymentStatus['status'] == 1 && $offlinePaymentMethodCount > 0) {
            $availablePaymentMethod = [OrderService::PAYMENT_METHOD_OFFLINE];
        }

        if (getWebConfig(name: 'wallet_status')) {
            $availablePaymentMethod = ['wallet_status'];
        }

        return $availablePaymentMethod;
    }

    private function getActiveOfflinePaymentMethods(): Collection
    {
        return $this->offlinePaymentMethodService->getList(
            dataLimit: 'all',
            filters: ['status' => OfflinePaymentMethodService::STATUS_ACTIVE],
        );
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
