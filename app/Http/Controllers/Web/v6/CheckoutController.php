<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\CouponService;
use App\Services\New\CurrencyService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\SettingService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use App\Traits\CheckoutBlockerRedirect;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use CheckoutBlockerRedirect;

    private const DEFAULT_THEME = 'default';

    private const LOGIN_PATH = 'customer/auth/login';

    private const APP_PAYMENT_MODE = 'app';

    private const PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    private const PAYMENT_METHOD_GATEWAYS = 'payment_gateways';

    private const PAYMENT_METHOD_OFFLINE = 'offline_payment';

    private const PAYMENT_METHOD_WALLET = 'wallet_status';

    private const CURRENCY_SYMBOL_INR = '₹';

    private const CURRENCY_CODE_USD = 'USD';

    private const CURRENCY_CODE_MYR = 'MYR';

    private const UNOWNED_ADDRESS_OWNER_ID = '0';

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly CustomerService $customerService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly CurrencyService $currencyService,
        private readonly SettingService $settingService,
    ) {}

    public function details(Request $request): View|RedirectResponse
    {
        if (! $this->hasCustomerCart() && ! $this->isGuestCheckoutAvailable()) {
            ToastMagic::error(translate('Please login your account'));

            return $this->redirectToLogin();
        }

        $customerId = $this->resolveCustomerId();

        $this->cartService->refreshPrices(customerId: $customerId, guestId: session('guest_id') ?? $request['guest_id'] ?? 0);

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customerId: $customerId);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');
        $countries = $countryRestrictStatus ? $this->getDeliveryCountries() : COUNTRIES;
        $isGuest = ! auth('customer')->check();

        $shippingAddresses = $this->shippingAddressService->getList(
            dataLimit: 'all',
            filters: [
                'customer_id' => $isGuest ? session('guest_id') : auth('customer')->id(),
                'is_guest' => $isGuest ? 1 : '0',
            ],
            sortBy: ShippingAddressService::SORT_OLDEST_ID,
        );

        return view(VIEW_FILE_NAMES['order_shipping'], [
            'physical_product_view' => $blockerResponse['physical_product_view'],
            'zip_codes' => $zipRestrictStatus ? $this->deliveryZipCodeService->getList(dataLimit: 'all', sortBy: DeliveryZipCodeService::SORT_OLDEST) : 0,
            'country_restrict_status' => $countryRestrictStatus,
            'zip_restrict_status' => $zipRestrictStatus,
            'countries' => $countries,
            'countriesName' => array_values(array_column($countries, 'name')),
            'countriesCode' => array_values(array_column($countries, 'code')),
            'billing_input_by_customer' => getWebConfig(name: 'billing_input_by_customer'),
            'default_location' => getWebConfig(name: 'default_location'),
            'shipping_addresses' => $shippingAddresses,
            'billing_addresses' => $shippingAddresses,
        ]);
    }

    public function payment(Request $request): View|RedirectResponse
    {
        if ((! session('address_id') && ! session('billing_address_id')) || ! $this->ownsSessionCheckoutAddresses()) {
            ToastMagic::error(translate('Please update address information'));

            return redirect()->route('checkout-details');
        }

        if (! $this->hasCustomerCart() && ! $this->isGuestCheckoutAvailable()) {
            ToastMagic::warning(translate('Please login your account'));

            return $this->redirectToLogin();
        }

        $customerId = $this->resolveCustomerId();

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customerId: $customerId);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        $stockAvailability = $this->cartService->validateStockAvailability(
            customerId: $customerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
        );
        if ($stockAvailability['status'] == CartService::STATUS_FAILED) {
            ToastMagic::error("The product '{$stockAvailability['product_name']}' requires a minimum quantity of {$stockAvailability['minimum_order_qty']}.");

            return $stockAvailability['redirect'] === CartService::REDIRECT_SHOP_CART ? redirect()->route('shop-cart') : redirect('/');
        }

        $vendorWiseCartList = collect($this->getOrderGenerateCartList(request: $request, customerId: $customerId));

        $cashOnDeliveryBtnShow = $vendorWiseCartList->every(
            fn (array $vendorCart): bool => $vendorCart['cart_list']->contains('product_type', CartService::PRODUCT_TYPE_PHYSICAL),
        );

        $inr = $this->currencyService->findBy(params: ['symbol' => self::CURRENCY_SYMBOL_INR]);
        $usd = $this->currencyService->findBy(params: ['code' => self::CURRENCY_CODE_USD]);
        $myr = $this->currencyService->findBy(params: ['code' => self::CURRENCY_CODE_MYR]);

        $offlinePaymentMethods = $this->offlinePaymentMethodService->getList(
            dataLimit: 'all',
            filters: ['status' => OfflinePaymentMethodService::STATUS_ACTIVE],
        );
        $offlinePaymentStatus = getWebConfig(name: 'offline_payment');
        $cashOnDeliveryStatus = getWebConfig(name: 'cash_on_delivery');
        $activePaymentGateways = $this->settingService->getActivePaymentGateways();

        if (session()->has('address_id') && session()->has('billing_address_id')) {
            return view(VIEW_FILE_NAMES['payment_details'], [
                'cashOnDeliveryBtnShow' => $cashOnDeliveryBtnShow,
                'cash_on_delivery' => $cashOnDeliveryStatus,
                'digital_payment' => getWebConfig(name: 'digital_payment'),
                'wallet_status' => getWebConfig(name: 'wallet_status'),
                'offline_payment' => $offlinePaymentStatus,
                'coupon_discount' => $vendorWiseCartList->sum('coupon_discount'),
                'amount' => $this->cartService->getPayableAmount(vendorWiseCartList: $vendorWiseCartList),
                'inr' => $inr,
                'usd' => $usd,
                'myr' => $myr,
                'paymentGatewayPublishedStatus' => config('get_payment_publish_status') ?? 0,
                'payment_gateways_list' => $activePaymentGateways,
                'offline_payment_methods' => $offlinePaymentMethods,
                'activeMinimumMethods' => $this->getAvailablePaymentMethods(
                    cashOnDeliveryStatus: $cashOnDeliveryStatus,
                    isCashOnDeliveryAllowed: $cashOnDeliveryBtnShow,
                    offlinePaymentStatus: $offlinePaymentStatus,
                    activeOfflinePaymentMethodCount: $offlinePaymentMethods->count(),
                    activePaymentGatewayCount: $activePaymentGateways->count(),
                ) !== [],
            ]);
        }

        ToastMagic::error(translate('Incomplete info'));

        return back();
    }

    private function hasCustomerCart(): bool
    {
        return auth('customer')->check() && $this->cartService->count(filters: ['customer_id' => auth('customer')->id()]) > 0;
    }

    private function isGuestCheckoutAvailable(): bool
    {
        return getWebConfig(name: 'guest_checkout') && session()->has('guest_id') && session('guest_id');
    }

    private function redirectToLogin(): RedirectResponse
    {
        return theme_root_path() == self::DEFAULT_THEME ? redirect(self::LOGIN_PATH) : redirect('/');
    }

    private function resolveCustomerId(): ?int
    {
        if (auth('customer')->check()) {
            return auth('customer')->id();
        }

        if (session()->has('customer_id') && ! session('is_guest')) {
            return $this->customerService->find(id: session('customer_id'))?->id;
        }

        return null;
    }

    /** @return array<int|string, array{code: string, name: string}> */
    private function getDeliveryCountries(): array
    {
        $deliveryCountryCodes = $this->deliveryCountryCodeService->getList(dataLimit: 'all', sortBy: DeliveryCountryCodeService::SORT_OLDEST);

        $countries = [];
        foreach ($deliveryCountryCodes as $deliveryCountryCode) {
            foreach (COUNTRIES as $key => $country) {
                if ($country['code'] == $deliveryCountryCode['country_code']) {
                    $countries[$key] = ['code' => $country['code'], 'name' => $country['name']];
                }
            }
        }

        return $countries;
    }

    private function ownsSessionCheckoutAddresses(): bool
    {
        foreach ([session('address_id'), session('billing_address_id')] as $addressId) {
            if ($addressId && ! $this->isOwnedCheckoutAddress(addressId: $addressId)) {
                return false;
            }
        }

        return true;
    }

    private function isOwnedCheckoutAddress(mixed $addressId): bool
    {
        if (! is_scalar($addressId)) {
            return false;
        }

        $isSignedIn = auth('customer')->check();
        $hasGuestId = session()->has('guest_id');
        $ownerId = $isSignedIn ? (string) auth('customer')->id() : (string) ($hasGuestId ? session('guest_id') : self::UNOWNED_ADDRESS_OWNER_ID);

        if ($this->shippingAddressService->find(id: $addressId, ownerId: $ownerId, isGuest: ! $isSignedIn && $hasGuestId) !== null) {
            return true;
        }

        return $isSignedIn && $this->shippingAddressService->find(id: $addressId, ownerId: self::UNOWNED_ADDRESS_OWNER_ID, isGuest: false) !== null;
    }

    /** @return array{status: int, physical_product_view: bool, message: array<int, string>, errorType?: string, redirect?: string} */
    private function getCheckoutBlockerResponse(Request $request, ?int $customerId): array
    {
        $blockers = $this->cartService->getCheckoutBlockers(
            customerId: $customerId,
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

    /** @return array<int, array<string, mixed>> */
    private function getOrderGenerateCartList(Request $request, ?int $customerId): array
    {
        $couponCode = session('coupon_code') ?? '';

        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            type: CartService::TYPE_CHECKED,
        );
        $cartItemsByGroup = $cartItems->groupBy('cart_group_id');

        $groupShippingCosts = $this->cartService->getGroupShippingCosts(
            customerId: $customerId,
            requestGuestId: $request['guest_id'],
            cartGroupIds: $cartItemsByGroup->keys()->map(fn ($cartGroupId) => (string) $cartGroupId)->all(),
        );
        $couponOrderGroupCount = $customerId === null ? 0 : $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]);

        $cartCoupon = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $couponOrderGroupCount,
            customerOrderCount: $customerId === null ? 0 : $this->orderService->count(filters: ['customer_id' => $customerId]),
            cartItems: $cartItems,
            groupShippingCosts: $groupShippingCosts,
        );

        if ($cartCoupon['reason'] === null) {
            session()->put('coupon_code', $cartCoupon['coupon']->code);
            session()->put('coupon_type', $cartCoupon['coupon']->coupon_type);
            session()->put('coupon_discount', $cartCoupon['discount']);
            session()->put('coupon_bearer', $cartCoupon['coupon']->coupon_bearer);
            session()->put('coupon_seller_id', $cartCoupon['coupon']->seller_id);
        }

        $groupCouponDiscounts = [];
        foreach ($cartItemsByGroup as $cartGroupId => $groupCartItems) {
            $groupCouponDiscounts[$cartGroupId] = $this->couponService->calculateGroupCouponDiscount(
                couponCode: $couponCode,
                customerId: $customerId,
                customerCouponOrderGroupCount: $couponOrderGroupCount,
                applicableAmount: $cartCoupon['total_cart_amount'],
                discountAmount: $cartCoupon['discount'],
                groupCartItems: $groupCartItems,
                groupShippingCost: (float) ($groupShippingCosts[$cartGroupId] ?? 0),
            );
        }

        $referralCustomer = $this->referralCustomerService->findRedeemableForCustomer(
            customerId: $customerId,
            completedOrderCount: $customerId === null ? 0 : $this->orderService->count(filters: [
                'customer_id' => $customerId,
                'order_status' => OrderService::STATUS_DELIVERED,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
            ]),
        );
        $referralDiscountBases = $this->cartService->getReferralDiscountBases(
            cartItems: $cartItems,
            totalCouponDiscount: $cartCoupon['discount'],
            groupCouponDiscounts: $groupCouponDiscounts,
        );

        $groupReferralDiscounts = [];
        foreach ($referralDiscountBases['group_amounts'] as $cartGroupId => $groupAmount) {
            $groupReferralDiscounts[$cartGroupId] = $this->referralCustomerService->calculateReferralDiscount(
                referralCustomer: $referralCustomer,
                totalAmount: $referralDiscountBases['total_amount'],
                groupAmount: $groupAmount,
            );
        }

        return $this->cartService->getOrderGenerateCartList(
            customerId: $customerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
            groupCouponDiscounts: $groupCouponDiscounts,
            groupReferralDiscounts: $groupReferralDiscounts,
            shippingAddressId: session('address_id') ?: 0,
            billingAddressId: session('billing_address_id') ?: 0,
        );
    }

    /** @return array<int, string> */
    private function getAvailablePaymentMethods(mixed $cashOnDeliveryStatus, bool $isCashOnDeliveryAllowed, mixed $offlinePaymentStatus, int $activeOfflinePaymentMethodCount, int $activePaymentGatewayCount): array
    {
        $availablePaymentMethods = [];

        if ($cashOnDeliveryStatus && $cashOnDeliveryStatus['status'] && $isCashOnDeliveryAllowed) {
            $availablePaymentMethods = [self::PAYMENT_METHOD_CASH_ON_DELIVERY];
        }

        if (getWebConfig(name: 'digital_payment') && $activePaymentGatewayCount > 0) {
            $availablePaymentMethods = [self::PAYMENT_METHOD_GATEWAYS];
        }

        if ($offlinePaymentStatus && $offlinePaymentStatus['status'] == 1 && $activeOfflinePaymentMethodCount > 0) {
            $availablePaymentMethods = [self::PAYMENT_METHOD_OFFLINE];
        }

        if (auth('customer')->check() && getWebConfig(name: 'wallet_status')) {
            $availablePaymentMethods = [self::PAYMENT_METHOD_WALLET];
        }

        return $availablePaymentMethods;
    }
}
