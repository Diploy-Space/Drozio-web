<?php

namespace App\Http\Controllers\Web\v6;

use App\Events\CheckoutCartStateRequestedEvent;
use App\Events\CheckoutOrdersPlacingEvent;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CurrencyService;
use App\Services\New\CustomerService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use App\Traits\CheckoutBlockerRedirect;
use App\Traits\OrderPlacementSessionTrait;
use App\Utils\Convert;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class OrderPlacementController extends Controller
{
    use CheckoutBlockerRedirect;
    use OrderPlacementSessionTrait;

    private const APP_PAYMENT_MODE = 'app';

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly CustomerService $customerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly CurrencyService $currencyService,
    ) {}

    public function placeCashOnDeliveryOrder(Request $request): RedirectResponse|JsonResponse
    {
        if ($request['payment_method'] != OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY) {
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => translate('Something went wrong')]);
            }

            return back()->with('error', 'Something_went_wrong');
        }

        $customer = $this->resolveCustomer();

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customer: $customer);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'message' => $blockerResponse['message'][0] ?? translate('Something went wrong'),
                ]);
            }

            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customer?->id,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            type: CartService::TYPE_CHECKED,
        );
        $isStockAvailable = $this->cartService->hasEnoughStock(cartItems: $cartItems);
        $hasPhysicalProduct = (bool) $cartItems->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)->first();

        if (! $hasPhysicalProduct) {
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => translate('Something went wrong')]);
            }

            return back()->with('error', translate('Something went wrong'));
        }

        if (! $isStockAvailable) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'message' => translate('The following items in your cart are currently out of stock'),
                ]);
            }
            ToastMagic::error(translate('The following items in your cart are currently out of stock'));

            return redirect()->route('shop-cart');
        }

        if (! $this->meetsMinimumOrderAmount(request: $request, customer: $customer)) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'message' => translate('Check minimum order amount requirement'),
                    'redirect' => route('shop-cart'),
                ]);
            }
            ToastMagic::info(translate('Check minimum order amount requirement'));

            return redirect()->route('shop-cart');
        }

        if (session('newCustomerRegister')) {
            $registration = session('newCustomerRegister');
            if ($this->customerService->existsByEmailOrPhone(email: $registration['email'], phone: $registration['phone'])) {
                if ($request->ajax()) {
                    return response()->json(['status' => 0, 'message' => translate('Already registered')]);
                }
                ToastMagic::error(translate('Already registered'));

                return back();
            }

            $this->registerCheckoutCustomer(registration: $registration);
        }

        try {
            $placement = $this->placeCheckoutOrders(customer: $customer, requestGuestId: $request['guest_id'], orderData: [
                'order_status' => OrderService::STATUS_PENDING,
                'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
                'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
                'transaction_ref' => '',
                'coupon_code' => session('coupon_code'),
                'address_id' => session('address_id'),
                'billing_address_id' => session('billing_address_id'),
                'bring_change_amount' => $request['bring_change_amount'] ?? 0,
                'bring_change_amount_currency' => $this->currencyService->resolveBringChangeAmountCurrencyCode(currentCurrencyCode: session('currency_code')),
            ]);
        } catch (InsufficientStockException) {
            return $this->outOfStockResponse(request: $request);
        }

        $this->applyPlacementSession(placement: $placement);

        $this->storeOrderSuccessSession(orderIds: $placement['order_ids']);
        session()->forget('newRegisterCustomerInfo');

        $redirectUrl = auth()->guard('customer')->check() ? route('account-oder') : route('home');

        if ($request->ajax()) {
            return response()->json([
                'status' => 1,
                'message' => translate('Order placed successfully'),
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
    }

    public function placeOfflinePaymentOrder(Request $request): RedirectResponse|JsonResponse
    {
        if ($request['payment_method'] != OrderService::PAYMENT_METHOD_OFFLINE) {
            return back()->with('error', 'Something went wrong!');
        }

        if (! session('address_id') && ! session('billing_address_id')) {
            ToastMagic::error(translate('Please update address information'));

            return redirect()->route('checkout-details');
        }

        $customer = $this->resolveCustomer();

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customer: $customer);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        if (! $this->cartService->hasEnoughStock(cartItems: $this->getCheckedCartItemsWithProduct(request: $request, customer: $customer))) {
            ToastMagic::error(translate('The following items in your cart are currently out of stock'));

            return redirect()->route('shop-cart');
        }

        if (! $this->meetsMinimumOrderAmount(request: $request, customer: $customer)) {
            ToastMagic::info(translate('Check minimum order amount requirement'));

            return redirect()->route('shop-cart');
        }

        $method = $this->offlinePaymentMethodService->findBy(params: ['id' => $request['method_id'], 'status' => OfflinePaymentMethodService::STATUS_ACTIVE]);
        $offlinePaymentInfo = $method === null ? [] : [
            'method_id' => $request['method_id'],
            'method_name' => $method->method_name,
        ] + $this->offlinePaymentMethodService->getCustomerInputValues(method: $method, input: $request->all());

        if (session('newCustomerRegister')) {
            $registration = session('newCustomerRegister');
            if ($this->customerService->existsByEmailOrPhone(email: $registration['email'], phone: $registration['phone'])) {
                ToastMagic::error(translate('Already registered'));

                return back();
            }

            $this->registerCheckoutCustomer(registration: $registration);
        }

        try {
            $placement = $this->placeCheckoutOrders(customer: $customer, requestGuestId: $request['guest_id'], orderData: [
                'order_status' => OrderService::STATUS_PENDING,
                'payment_method' => OrderService::PAYMENT_METHOD_OFFLINE,
                'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
                'transaction_ref' => '',
                'coupon_code' => session('coupon_code'),
                'address_id' => session('address_id'),
                'billing_address_id' => session('billing_address_id'),
                'payment_note' => $request['payment_note'],
                'offline_payment_info' => $offlinePaymentInfo,
            ]);
        } catch (InsufficientStockException) {
            return $this->outOfStockResponse(request: $request);
        }

        $this->applyPlacementSession(placement: $placement);

        $this->storeOrderSuccessSession(orderIds: $placement['order_ids']);
        session()->forget('newRegisterCustomerInfo');

        return auth()->guard('customer')->check() ? redirect()->route('account-oder') : redirect(route('home'));
    }

    public function placeWalletOrder(Request $request): RedirectResponse|JsonResponse
    {
        if (! session('address_id') && ! session('billing_address_id')) {
            ToastMagic::error(translate('Please update address information'));

            return redirect()->route('checkout-details');
        }

        $customer = $this->resolveCustomer();

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customer: $customer);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        $orderGenerateCartList = $this->getCheckoutCartState(customer: $customer, requestGuestId: $request['guest_id']);
        $this->putCouponSession(couponSession: $orderGenerateCartList['coupon_session']);
        $paymentAmount = $this->cartService->getPayableAmount(vendorWiseCartList: $orderGenerateCartList['vendor_carts']);

        if ($paymentAmount > ($customer?->wallet_balance ?? 0)) {
            ToastMagic::warning(translate('Insufficient balance in your wallet to pay for this order').'!!');

            return back();
        }

        if (! $this->cartService->hasEnoughStock(cartItems: $this->getCheckedCartItemsWithProduct(request: $request, customer: $customer))) {
            ToastMagic::error(translate('The following items in your cart are currently out of stock'));

            return redirect()->route('shop-cart');
        }

        if (! $this->meetsMinimumOrderAmount(request: $request, customer: $customer)) {
            ToastMagic::info(translate('Check minimum order amount requirement'));

            return redirect()->route('shop-cart');
        }

        if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) != 1) {
            ToastMagic::error(translate('Wallet is deactivated'));

            return back();
        }

        try {
            $placement = $this->placeCheckoutOrders(
                customer: $customer,
                requestGuestId: $request['guest_id'],
                orderData: [
                    'order_status' => OrderService::STATUS_CONFIRMED,
                    'payment_method' => OrderService::PAYMENT_METHOD_WALLET,
                    'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                    'transaction_ref' => '',
                    'coupon_code' => session('coupon_code'),
                    'address_id' => session('address_id'),
                    'billing_address_id' => session('billing_address_id'),
                ],
                walletDebitAmount: Convert::default($paymentAmount),
            );
        } catch (InsufficientStockException) {
            return $this->outOfStockResponse(request: $request);
        } catch (Throwable $exception) {
            report($exception);
            ToastMagic::error(translate('Something went wrong'));

            return back();
        }

        $this->applyPlacementSession(placement: $placement);

        if (session()->has('payment_mode') && session('payment_mode') == self::APP_PAYMENT_MODE) {
            return redirect()->route('payment-success');
        }

        $this->storeOrderSuccessSession(orderIds: $placement['order_ids']);
        session()->forget('coupon_discount');

        return auth()->guard('customer')->check() ? redirect()->route('account-oder') : redirect()->route('home');
    }

    public function getOfflinePaymentMethodForm(Request $request): JsonResponse
    {
        $method = $this->offlinePaymentMethodService->findBy(params: ['id' => $request['method_id'], 'status' => OfflinePaymentMethodService::STATUS_ACTIVE]);

        $orderGenerateCartList = $this->getCheckoutCartState(customer: $this->resolveCustomer(), requestGuestId: $request['guest_id']);
        $this->putCouponSession(couponSession: $orderGenerateCartList['coupon_session']);
        $vendorWiseCartList = collect($orderGenerateCartList['vendor_carts']);

        $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $totalOfflineAmount = $vendorWiseCartList->sum('grand_total')
            - $vendorWiseCartList->sum('refer_and_earn_discount')
            - $vendorWiseCartList->sum('free_delivery_discount')
            - $couponDiscount
            + $vendorWiseCartList->sum('total_tax_amount');

        if ($request->filled('edit_due_amount')) {
            $totalOfflineAmount = $request['edit_due_amount'];
        }

        return response()->json([
            'methodHtml' => view(themeViewName(key: 'pay_offline_method_list_partials'), [
                'method' => $method,
                'totalOfflineAmount' => $totalOfflineAmount,
            ])->render(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $orderData
     * @return array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null}
     */
    private function placeCheckoutOrders(?Model $customer, int|string|null $requestGuestId, array $orderData, ?float $walletDebitAmount = null): array
    {
        $placing = new CheckoutOrdersPlacingEvent(
            customer: $customer,
            requestGuestId: $requestGuestId,
            orderCustomer: $this->resolveSessionOrderCustomer(customer: $customer),
            orderData: $orderData,
            checkoutSession: $this->getCheckoutSession(),
            walletDebitAmount: $walletDebitAmount,
        );

        event($placing);

        return $placing->placement;
    }

    /** @return array{vendor_carts: array<int, array<string, mixed>>, coupon_session: array<string, mixed>|null} */
    private function getCheckoutCartState(?Model $customer, int|string|null $requestGuestId): array
    {
        $state = new CheckoutCartStateRequestedEvent(
            customer: $customer,
            requestGuestId: $requestGuestId,
            checkoutSession: $this->getCheckoutSession(),
            couponCode: session('coupon_code') ?? '',
            shippingAddressId: session('address_id'),
            billingAddressId: session('billing_address_id'),
        );

        event($state);

        return $state->cartState;
    }

    private function outOfStockResponse(Request $request): RedirectResponse|JsonResponse
    {
        $message = translate('The following items in your cart are currently out of stock');

        if ($request->ajax()) {
            return response()->json(['status' => 0, 'message' => $message, 'redirect' => route('shop-cart')]);
        }

        ToastMagic::error($message);

        return redirect()->route('shop-cart');
    }

    /** @return array{status: int, physical_product_view: bool, message: array<int, string>, errorType?: string, redirect?: string} */
    private function getCheckoutBlockerResponse(Request $request, ?Model $customer): array
    {
        $blockers = $this->cartService->getCheckoutBlockers(
            customerId: $customer?->id,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            activeShippingMethodCounts: $this->shippingMethodService->getActiveMethodCountsByCreator(),
        );

        $response = [
            'status' => $blockers['status'],
            'physical_product_view' => $blockers['physical_product_view'],
            'message' => array_map(
                fn (array $blocker): string => $this->checkoutBlockerMessage(blocker: $blocker, isAppRequest: $request['payment_request_from'] == self::APP_PAYMENT_MODE),
                $blockers['blockers'],
            ),
        ];

        if (isset($blockers['errorType'])) {
            $response['errorType'] = $blockers['errorType'];
        }

        if (isset($blockers['redirect'])) {
            $response['redirect'] = $blockers['redirect'] === CartService::REDIRECT_HOME ? url('/') : route('shop-cart');
        }

        return $response;
    }

    private function checkoutBlockerMessage(array $blocker, bool $isAppRequest): string
    {
        return match ($blocker['reason']) {
            CartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT => $isAppRequest
                ? translate('Please complete the minimum order amount for :shop', ['shop' => $blocker['shop_name']])
                : translate('The minimum order amount for :shop is :amount', ['amount' => webCurrencyConverter(amount: $blocker['minimum_order_amount']), 'shop' => $blocker['shop_name']]),
            CartService::REASON_CART_EMPTY => translate('No items in basket'),
            CartService::REASON_NO_CHECKED_ITEMS => translate('Please add or check items before proceeding to checkout'),
            CartService::REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP => translate('Shipping Not Available for').' '.$blocker['shop_name'],
            CartService::REASON_SHIPPING_METHOD_REQUIRED => translate('Select shipping method'),
            CartService::REASON_SHOP_SHIPPING_METHOD_REQUIRED => translate('Select').' '.$blocker['shop_name'].' '.translate('Shipping Method'),
            CartService::REASON_UNAVAILABLE_VENDORS => translate('Please remove all products from unavailable vendors'),
            CartService::REASON_UNAVAILABLE_PRODUCT => translate('Please remove this unavailable product to continue'),
        };
    }

    private function meetsMinimumOrderAmount(Request $request, ?Model $customer): bool
    {
        return $this->cartService->verifyCartListMinimumOrderAmount(
            customerId: $customer?->id,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
        )['status'] != CartService::STATUS_FAILED;
    }

    private function getCheckedCartItemsWithProduct(Request $request, ?Model $customer): Collection
    {
        return $this->cartService->getList(
            dataLimit: 'all',
            relations: ['product'],
            filters: [
                'cart_group_id' => $this->cartService->getCartGroupIds(
                    customerId: $customer?->id,
                    guestId: session('guest_id') ?? $request['guest_id'] ?? 0,
                    type: CartService::TYPE_CHECKED,
                ),
                'is_checked' => 1,
                CartService::FILTER_ACTIVE_PRODUCT => true,
            ],
        );
    }

    /** @return array{id: int|string, is_guest: int} */
    private function resolveSessionOrderCustomer(?Model $customer): array
    {
        if ($customer !== null) {
            return ['id' => $customer['id'], 'is_guest' => 0];
        }

        if (session('newCustomerRegister') && session('newRegisterCustomerInfo')) {
            $newCustomerId = session('newRegisterCustomerInfo')['id'];
            $this->moveGuestCheckoutAddressesToCustomer(customerId: $newCustomerId);

            return ['id' => $newCustomerId, 'is_guest' => 0];
        }

        return ['id' => session('guest_id') ? session('guest_id') : 0, 'is_guest' => 1];
    }

    private function resolveCustomer(): ?Model
    {
        if (auth('customer')->check()) {
            return auth('customer')->user();
        }

        if (session()->has('customer_id') && ! session('is_guest')) {
            return $this->customerService->find(id: session('customer_id'));
        }

        return null;
    }

    private function registerCheckoutCustomer(array $registration): void
    {
        $customer = $this->customerService->createFromCheckoutRegistration(registration: $registration);
        session()->put('newRegisterCustomerInfo', $customer);

        $this->moveGuestCheckoutAddressesToCustomer(customerId: $customer['id']);
    }

    private function moveGuestCheckoutAddressesToCustomer(int|string $customerId): void
    {
        $this->shippingAddressService->moveGuestAddressesToCustomer(
            guestId: session()->has('guest_id') ? session('guest_id') : 0,
            customerId: $customerId,
            addressIds: [session('address_id'), session('billing_address_id')],
        );
    }

    private function storeOrderSuccessSession(array $orderIds): void
    {
        session(['order_success_ids' => $orderIds, 'isNewCustomerInSession' => session('newCustomerRegister')]);
        session()->forget('newCustomerRegister');
    }
}
