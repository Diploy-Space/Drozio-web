<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Enums\GlobalConstant;
use App\Http\Controllers\Controller;
use App\Services\New\CustomerService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use App\Services\New\RobotsMetaContentService;
use App\Services\New\SettingService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    private const FROM_ORDER_DETAILS = 1;

    private const LAST_ORDER_VIEW = 'web-views.order.tracking';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly CustomerService $customerService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly SettingService $settingService,
    ) {}

    public function index(): View
    {
        return view(themeViewName(key: 'tracking-page'), [
            'robotsMetaContentData' => $this->robotsMetaContentService->findByPageNameOrDefault(
                pageName: RobotsMetaContentService::PAGE_NAME_TRACK_ORDER,
            ),
            'trackResultUrl' => route('track-order.result'),
            'trackClearUrl' => route('track-order.index'),
            'moduleTabs' => customerProfileModuleTabs(menuKey: 'track-order', activeModule: BusinessModule::RETAIL),
        ]);
    }

    public function result(Request $request): View|RedirectResponse
    {
        $customer = auth('customer')->user();

        $order = $customer === null
            ? $this->findOrderForVisitor(orderId: $request['order_id'], phoneNumber: $request['phone_number'])
            : $this->findOrderForCustomer(
                orderId: $request['order_id'],
                phoneNumber: $request['phone_number'],
                customer: $customer,
                isFromOrderDetails: $request['from_order_details'] == self::FROM_ORDER_DETAILS,
            );

        if ($order === null) {
            ToastMagic::error(translate('Invalid Order Id or Phone Number'));

            return redirect()->route('track-order.index', ['order_id' => $request['order_id'], 'phone_number' => $request['phone_number']]);
        }

        if ($order['order_type'] == OrderService::SOURCE_POS) {
            $orderOwner = $order['seller_is'] == OrderService::SELLER_IS_SELLER ? 'vendor' : 'admin';
            ToastMagic::error(translate('This order is created by').$orderOwner.translate('From POS').','.translate('Please contact with').$orderOwner.translate('To know more details').'.');

            return redirect()->back();
        }

        return view(themeViewName(key: 'track_order'), [
            'orderDetails' => $order,
            'deliveryPartnerTracking' => deliveryPartnerTrackingInfo($order),
            'user_phone' => $request['phone_number'] ?? '',
            'order_verification_status' => getWebConfig(name: 'order_verification'),
            'orderEditPaymentHistory' => $this->orderEditHistoryService->getList(dataLimit: 'all', filters: ['order_id' => $order->id]),
            'isOrderOnlyDigital' => $this->orderService->isOnlyDigital($order),
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
            'isPhysicalProduct' => $this->orderDetailService->hasPhysicalProduct(orderId: $order->id),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
            'digitalPayment' => getWebConfig(name: 'digital_payment')['status'],
            'walletStatus' => getWebConfig(name: 'wallet_status'),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'availablePaymentMethod' => [],
        ]);
    }

    public function last(): View|RedirectResponse
    {
        $order = auth('customer')->check()
            ? $this->orderService->findLatestForCustomer(customerId: (int) auth('customer')->id())
            : null;

        if ($order === null) {
            return redirect()->route('track-order.index')->with('Error', translate('Invalid Order Id or Phone Number'));
        }

        return view(self::LAST_ORDER_VIEW, ['orderDetails' => $order]);
    }

    public function orderWiseResult(Request $request): View|RedirectResponse
    {
        if (! auth('customer')->check()) {
            return back();
        }

        $order = blank($request['order_id'])
            ? null
            : $this->orderService->findByDetailCustomer(
                id: $request['order_id'],
                customerId: (int) auth('customer')->id(),
                relations: ['orderDetails', 'seller.shop'],
            );

        if ($order === null) {
            ToastMagic::warning(translate('Invalid order'));

            return redirect()->route('account-oder');
        }

        return view(themeViewName(key: 'track_order_wise_result'), [
            'orderDetails' => $order,
            'isOrderOnlyDigital' => $this->orderService->isOnlyDigital($order),
            'paymentGatewayList' => $this->getPaymentGatewayList(),
            'cashOnDeliveryStatus' => getWebConfig(name: 'cash_on_delivery'),
            'offlinePaymentMethods' => $this->getActiveOfflinePaymentMethods(),
            'offlinePaymentStatus' => getWebConfig(name: 'offline_payment'),
        ]);
    }

    private function findOrderForVisitor(mixed $orderId, mixed $phoneNumber): ?Model
    {
        $order = $this->orderService->findBy(
            params: ['id' => $orderId, 'order_type' => OrderService::ORDER_TYPE_DEFAULT],
            relations: ['shippingAddress', 'billingAddress', 'details', 'latestEditHistory', 'orderDetails'],
        );

        if ($order !== null && $order->is_guest) {
            return $this->orderService->matchesGuestPhone(order: $order, phoneNumber: $phoneNumber) ? $order : null;
        }

        $registeredCustomer = filled($phoneNumber) ? $this->customerService->findBy(params: ['phone' => $phoneNumber]) : null;

        if ($registeredCustomer === null || blank($orderId)) {
            return null;
        }

        return $this->orderService->findByDetailCustomer(
            id: $orderId,
            customerId: (int) $registeredCustomer->id,
            relations: ['latestEditHistory', 'orderDetails'],
        );
    }

    private function findOrderForCustomer(mixed $orderId, mixed $phoneNumber, Authenticatable $customer, bool $isFromOrderDetails): ?Model
    {
        if (blank($orderId)) {
            return null;
        }

        $relations = ['details', 'latestEditHistory', 'orderDetails'];

        if ($isFromOrderDetails) {
            return $this->orderService->findByDetailCustomer(id: $orderId, customerId: (int) $customer->id, relations: $relations);
        }

        $order = $this->orderService->findBy(
            params: ['id' => $orderId],
            relations: ['shippingAddress', 'billingAddress', ...$relations],
        );

        if ($order !== null && $order->is_guest) {
            return $this->orderService->matchesGuestPhone(order: $order, phoneNumber: $phoneNumber) ? $order : null;
        }

        if ($customer->phone == $phoneNumber) {
            return $this->orderService->findByDetailCustomer(id: $orderId, customerId: (int) $customer->id, relations: $relations);
        }

        return null;
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
