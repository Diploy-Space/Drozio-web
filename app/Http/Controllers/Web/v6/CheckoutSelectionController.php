<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutSelectionController extends Controller
{
    private const COUPON_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'coupon_seller_id',
    ];

    private const ALL_CART_GROUPS = 'all_cart_group';

    private const CHECKBOX_CHECKED = 'on';

    private const PHYSICAL_PRODUCT_IN_CART = 'yes';

    private const BILLING_DIFFERS_FROM_SHIPPING = 'false';

    private const SHIPPING_REQUIRED_FIELDS = ['contact_person_name', 'address', 'city', 'zip', 'country'];

    private const OTHER_SHIPPING_REQUIRED_FIELDS = ['contact_person_name', 'address', 'city', 'zip', 'country', 'phone'];

    private const BILLING_REQUIRED_FIELDS = ['billing_contact_person_name', 'billing_address', 'billing_city', 'billing_zip', 'billing_country'];

    private const OTHER_BILLING_REQUIRED_FIELDS = ['billing_contact_person_name', 'billing_address', 'billing_city', 'billing_zip', 'billing_country', 'billing_phone'];

    private const PHONE_MIN_DIGITS = 4;

    private const PHONE_MAX_DIGITS = 20;

    private const PASSWORD_MIN_LENGTH = 7;

    public function __construct(
        private readonly CartService $cartService,
        private readonly CartShippingService $cartShippingService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly CustomerService $customerService,
        private readonly CustomerAuthService $customerAuthService,
    ) {}

    public function setPaymentMethod(string $name): JsonResponse
    {
        if (auth('customer')->check() || session()->has('mobile_app_payment_customer_id')) {
            session()->put('payment_method', $name);

            return response()->json(['status' => 1]);
        }

        return response()->json(['status' => 0]);
    }

    public function setShippingMethod(Request $request): JsonResponse
    {
        $customerId = auth('customer')->id();

        $cartGroupIds = $request['cart_group_id'] === self::ALL_CART_GROUPS
            ? $this->cartService->getCartGroupIds(customerId: $customerId, guestId: session('guest_id') ?? $request['guest_id'] ?? 0)
            : [$request['cart_group_id']];

        if ($cartGroupIds === []) {
            return response()->json(['status' => 1]);
        }

        $shippingMethod = is_scalar($request['id'])
            ? $this->shippingMethodService->findBy(params: ['id' => $request['id'], 'context' => BusinessModule::RETAIL])
            : null;

        if ($shippingMethod === null || ! is_string($cartGroupIds[0])) {
            return response()->json(['status' => 0]);
        }

        foreach (array_filter($cartGroupIds, 'is_string') as $cartGroupId) {
            $this->cartShippingService->saveForCartGroup(
                cartGroupId: $cartGroupId,
                shippingMethodId: $shippingMethod->id,
                shippingCost: $shippingMethod->cost,
            );

            $this->revalidateAppliedCoupon(request: $request, customerId: $customerId);
        }

        return response()->json(['status' => 1]);
    }

    public function chooseShippingAddress(Request $request): JsonResponse
    {
        $shipping = $this->parseSerializedForm(serializedForm: $request['shipping']);
        $billing = $this->parseSerializedForm(serializedForm: $request['billing']);
        $isGuest = ! auth('customer')->check();
        $hasPhysicalProduct = $request['physical_product'] == self::PHYSICAL_PRODUCT_IN_CART;
        $isBillingSeparate = $request['billing_addresss_same_shipping'] == self::BILLING_DIFFERS_FROM_SHIPPING;

        $isNewShippingAddress = ($shipping['save_address'] ?? null) == self::CHECKBOX_CHECKED;
        $isEnteredShippingAddress = ! $isNewShippingAddress && isset($shipping['shipping_method_id']) && $shipping['shipping_method_id'] == 0;
        $isSavedShippingAddress = ! $isNewShippingAddress && ! $isEnteredShippingAddress && isset($shipping['shipping_method_id']);

        $isNewBillingAddress = ($billing['save_address_billing'] ?? null) == self::CHECKBOX_CHECKED;
        $isEnteredBillingAddress = ! $isNewBillingAddress && ($billing['billing_method_id'] ?? null) == 0;
        $isSavedBillingAddress = $isBillingSeparate && ! $isNewBillingAddress && ! $isEnteredBillingAddress;

        $savedShippingAddress = $isSavedShippingAddress ? $this->findOwnedAddress(id: $shipping['shipping_method_id']) : null;

        if ($isSavedShippingAddress && $savedShippingAddress === null) {
            return $this->respondWithAddressError(message: translate('Please update country and zip for this shipping address'));
        }

        $savedBillingAddress = $isSavedBillingAddress ? $this->findOwnedAddress(id: $billing['billing_method_id']) : null;

        if ($isSavedBillingAddress && $savedBillingAddress === null) {
            return $this->respondWithAddressError(message: translate('Update country and zip for this billing address'));
        }

        if ($isNewShippingAddress || $isEnteredShippingAddress) {
            if ($this->hasMissingFields(fields: $shipping, requiredFields: self::SHIPPING_REQUIRED_FIELDS) || ($isGuest && ($shipping['email'] ?? null) == null)) {
                return $this->respondWithAddressError(message: $isNewShippingAddress
                    ? translate('Fill in all required fields of the shipping address')
                    : translate('Fill in all required fields of the shipping/billing address'));
            }

            $restrictionError = $this->getDeliveryRestrictionError(
                country: $shipping['country'],
                zip: $shipping['zip'],
                countryUnavailableMessage: $isNewShippingAddress
                    ? translate('Delivery unavailable in this country.')
                    : translate('Delivery unavailable in this country'),
            );

            if ($restrictionError !== null) {
                return $this->respondWithAddressError(message: $restrictionError);
            }

            $addressId = $this->shippingAddressService->createCheckoutAddress(columns: $this->getAddressOwnerColumns() + [
                'contact_person_name' => $shipping['contact_person_name'],
                'address_type' => $shipping['address_type'] ?? null,
                'address' => $shipping['address'],
                'city' => $shipping['city'],
                'zip' => $shipping['zip'],
                'country' => $shipping['country'],
                'phone' => $shipping['phone'] ?? null,
                'email' => $isGuest ? $shipping['email'] : null,
                'latitude' => $shipping['latitude'] ?? null,
                'longitude' => $shipping['longitude'] ?? null,
                'is_billing' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($savedShippingAddress !== null) {
            if (! $savedShippingAddress->country || ! $savedShippingAddress->zip) {
                return $this->respondWithAddressError(message: translate('Please update country and zip for this shipping address'));
            }

            $restrictionError = $this->getDeliveryRestrictionError(
                country: $savedShippingAddress->country,
                zip: $savedShippingAddress->zip,
                countryUnavailableMessage: translate('Delivery unavailable in this country'),
            );

            if ($restrictionError !== null) {
                return $this->respondWithAddressError(message: $restrictionError);
            }

            $addressId = $shipping['shipping_method_id'];
        } else {
            $addressId = 0;
        }

        if (! $isBillingSeparate) {
            $billingAddressId = $addressId;
        } elseif ($isNewBillingAddress || $isEnteredBillingAddress) {
            if ($this->hasMissingFields(fields: $billing, requiredFields: self::BILLING_REQUIRED_FIELDS) || ($isGuest && ($billing['billing_contact_email'] ?? null) == null)) {
                return $this->respondWithAddressError(message: $isNewBillingAddress
                    ? translate('Billing address is currently disabled').'. '.translate('To purchase digital products, please include a physical product in your order').'.'
                    : translate('Fill in all required fields of the billing address'));
            }

            $restrictionError = $this->getDeliveryRestrictionError(
                country: $billing['billing_country'],
                zip: $billing['billing_zip'],
                countryUnavailableMessage: translate('Delivery unavailable in this country'),
            );

            if ($restrictionError !== null) {
                return $this->respondWithAddressError(message: $restrictionError);
            }

            $billingAddressId = $this->shippingAddressService->createCheckoutAddress(columns: $this->getAddressOwnerColumns() + [
                'contact_person_name' => $billing['billing_contact_person_name'],
                'address_type' => $billing['billing_address_type'] ?? null,
                'address' => $billing['billing_address'],
                'city' => $billing['billing_city'],
                'zip' => $billing['billing_zip'],
                'country' => $billing['billing_country'],
                'phone' => $billing['billing_phone'] ?? null,
                'email' => $isGuest ? $billing['billing_contact_email'] : null,
                'latitude' => $billing['billing_latitude'] ?? null,
                'longitude' => $billing['billing_longitude'] ?? null,
                'is_billing' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            if ($hasPhysicalProduct) {
                if (! $savedBillingAddress->country || ! $savedBillingAddress->zip) {
                    return $this->respondWithAddressError(message: translate('Update country and zip for this billing address'));
                }

                $restrictionError = $this->getDeliveryRestrictionError(
                    country: $savedBillingAddress->country,
                    zip: $savedBillingAddress->zip,
                    countryUnavailableMessage: translate('Delivery unavailable in this country'),
                );

                if ($restrictionError !== null) {
                    return $this->respondWithAddressError(message: $restrictionError);
                }
            }

            $billingAddressId = $billing['billing_method_id'];
        }

        session()->put('address_id', $addressId);
        session()->put('billing_address_id', $billingAddressId);

        return response()->json([], 200);
    }

    public function chooseOtherShippingAddress(Request $request): JsonResponse
    {
        $customerId = auth('customer')->id();
        $isGuest = $customerId === null;
        $isBillingInputByCustomer = getWebConfig(name: 'billing_input_by_customer');
        $cartProductTypes = $this->getCheckedCartProductTypes(request: $request, customerId: $customerId);

        if (! $isBillingInputByCustomer && ! in_array(CartService::PRODUCT_TYPE_PHYSICAL, $cartProductTypes)) {
            return $this->respondWithAddressError(message: translate('Billing address is currently disabled').'. '.translate('To purchase digital products, please include a physical product in your order').'.');
        }

        $shipping = $this->parseSerializedForm(serializedForm: $request['shipping']);
        $billing = $this->parseSerializedForm(serializedForm: $request['billing']);
        $isBillingSeparate = $request['billing_addresss_same_shipping'] == self::BILLING_DIFFERS_FROM_SHIPPING;

        $contactError = $this->getOtherAddressContactError(
            shipping: $shipping,
            billing: $billing,
            isGuest: $isGuest,
            isBillingEmailRequired: $isBillingInputByCustomer && $isBillingSeparate,
            isBillingSeparate: $isBillingSeparate,
        );

        if ($contactError !== null) {
            return $this->respondWithAddressError(message: $contactError);
        }

        $addressId = $shipping['shipping_method_id'] ?? 0;
        $hasShippingForm = isset($shipping['shipping_method_id']);

        if ($hasShippingForm) {
            if ($this->hasMissingFields(fields: $shipping, requiredFields: self::OTHER_SHIPPING_REQUIRED_FIELDS) || ! isset($shipping['address_type']) || ($isGuest && ($shipping['email'] ?? null) == null)) {
                return $this->respondWithAddressError(message: translate('Fill in all required fields of the shipping address'));
            }

            $restrictionError = $this->getDeliveryRestrictionError(
                country: $shipping['country'],
                zip: $shipping['zip'],
                countryUnavailableMessage: translate('Delivery unavailable in this country.'),
            );

            if ($restrictionError !== null) {
                return $this->respondWithAddressError(message: $restrictionError);
            }
        }

        $isNewShippingAddress = ($shipping['save_address'] ?? null) == self::CHECKBOX_CHECKED;
        $isUpdatedShippingAddress = ! $isNewShippingAddress && ($shipping['update_address'] ?? null) == self::CHECKBOX_CHECKED;
        $isOneOffShippingAddress = ! $isNewShippingAddress && ! $isUpdatedShippingAddress && $hasShippingForm && ! isset($shipping['update_address']) && ! isset($shipping['save_address']);
        $keepsPostedShippingAddress = ! $isNewShippingAddress && ! $isOneOffShippingAddress && ($isUpdatedShippingAddress || ! empty($addressId));

        $isBillingFormApplicable = $isBillingSeparate && isset($billing['billing_method_id']) && $isBillingInputByCustomer;
        $billingMethodId = $billing['billing_method_id'] ?? null;
        $isNewBillingAddress = ($billing['save_address_billing'] ?? null) == self::CHECKBOX_CHECKED;
        $isUpdatedBillingAddress = ! $isNewBillingAddress && ($billing['update_billing_address'] ?? null) == self::CHECKBOX_CHECKED;
        $isOneOffBillingAddress = ! $isNewBillingAddress && ! $isUpdatedBillingAddress && ! isset($billing['update_billing_address']) && ! isset($billing['save_address_billing']);
        $keepsPostedBillingAddress = $isBillingFormApplicable && ! $isNewBillingAddress && ! $isOneOffBillingAddress && ($isUpdatedBillingAddress || ! empty($billingMethodId));

        $savedShippingAddress = $keepsPostedShippingAddress ? $this->findOwnedAddress(id: $addressId) : null;
        $savedBillingAddress = $keepsPostedBillingAddress ? $this->findOwnedAddress(id: $billingMethodId) : null;

        if (($keepsPostedShippingAddress && $savedShippingAddress === null) || ($keepsPostedBillingAddress && $savedBillingAddress === null)) {
            return $this->respondWithAddressError(message: translate('Please update address information'));
        }

        if ($isNewShippingAddress) {
            $addressId = $this->shippingAddressService->createCheckoutAddress(
                columns: $this->getAddressOwnerColumns() + $this->getOtherShippingAddressColumns(shipping: $shipping, isGuest: $isGuest),
            );
        } elseif ($isUpdatedShippingAddress) {
            $this->shippingAddressService->update(id: $savedShippingAddress->id, data: $this->getOtherShippingAddressUpdateData(shipping: $shipping));
        } elseif ($isOneOffShippingAddress) {
            $addressId = $this->shippingAddressService->createCheckoutAddress(
                columns: $this->getOneOffAddressOwnerColumns() + $this->getOtherShippingAddressColumns(shipping: $shipping, isGuest: $isGuest),
            );
        }

        $billingAddressId = $addressId ?? 0;

        if ($isBillingFormApplicable) {
            $billingAddressId = $billingMethodId;

            if ($this->hasMissingFields(fields: $billing, requiredFields: self::OTHER_BILLING_REQUIRED_FIELDS) || ! isset($billing['billing_address_type']) || ($isGuest && ($billing['billing_contact_email'] ?? null) == null)) {
                return $this->respondWithAddressError(message: translate('Fill in all required fields of the billing address'));
            }

            $restrictionError = $this->getDeliveryRestrictionError(
                country: $billing['billing_country'],
                zip: $billing['billing_zip'],
                countryUnavailableMessage: translate('Delivery unavailable in this country'),
            );

            if ($restrictionError !== null) {
                return $this->respondWithAddressError(message: $restrictionError);
            }

            if ($isNewBillingAddress) {
                $billingAddressId = $this->shippingAddressService->createCheckoutAddress(
                    columns: $this->getAddressOwnerColumns() + $this->getOtherBillingAddressColumns(billing: $billing, isGuest: $isGuest),
                );
            } elseif ($isUpdatedBillingAddress) {
                $this->shippingAddressService->update(id: $savedBillingAddress->id, data: $this->getOtherBillingAddressUpdateData(billing: $billing));
            } elseif ($isOneOffBillingAddress) {
                $billingAddressId = $this->shippingAddressService->createCheckoutAddress(
                    columns: $this->getOneOffAddressOwnerColumns() + $this->getOtherBillingAddressColumns(billing: $billing, isGuest: $isGuest),
                );
            }
        } elseif ($isBillingSeparate && ! isset($billing['billing_method_id']) && $request['physical_product'] != self::PHYSICAL_PRODUCT_IN_CART) {
            return $this->respondWithAddressError(message: translate('Fill in all required fields of the billing address'));
        }

        session()->put('address_id', $addressId);
        session()->put('billing_address_id', $billingAddressId);

        if ($request['is_check_create_account'] && $isGuest) {
            $registrationError = $this->getCheckoutRegistrationError(request: $request, shipping: $shipping, billing: $billing);

            if ($registrationError !== null) {
                return $this->respondWithAddressError(message: $registrationError);
            }
        } else {
            session()->forget('newCustomerRegister');
            session()->forget('newRegisterCustomerInfo');
        }

        if (! session('address_id') && ! session('billing_address_id')) {
            return $this->respondWithAddressError(message: translate('Please update address information'));
        }

        return response()->json([], 200);
    }

    private function revalidateAppliedCoupon(Request $request, ?int $customerId): void
    {
        if (! session('coupon_code') || ! session('coupon_discount')) {
            return;
        }

        $result = $this->calculateCartCouponDiscount(
            request: $request,
            customerId: $customerId,
            couponCode: is_scalar(session('coupon_code')) ? (string) session('coupon_code') : null,
        );

        if ($result['reason'] !== null) {
            foreach (self::COUPON_SESSION_KEYS as $sessionKey) {
                session()->forget($sessionKey);
            }

            return;
        }

        session()->put('coupon_code', $result['coupon']->code);
        session()->put('coupon_type', $result['coupon']->coupon_type);
        session()->put('coupon_discount', $result['discount']);
        session()->put('coupon_bearer', $result['coupon']->coupon_bearer);
        session()->put('coupon_seller_id', $result['coupon']->seller_id);
    }

    private function calculateCartCouponDiscount(Request $request, ?int $customerId, ?string $couponCode): array
    {
        $customerCouponOrderGroupCount = $customerId === null ? 0 : $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]);
        $customerOrderCount = $customerId === null ? 0 : $this->orderService->count(filters: ['customer_id' => $customerId]);

        $cartIndependentResult = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: [],
        );

        if ($cartIndependentResult['reason'] !== CouponService::REASON_CART_EMPTY) {
            return $cartIndependentResult;
        }

        $this->cartService->refreshPrices(customerId: $customerId, guestId: session('guest_id') ?? $request['guest_id'] ?? 0);

        $cartItems = $this->cartService->getCartItemList(
            customerId: $customerId,
            guestId: session('guest_id'),
            type: CartService::TYPE_CHECKED,
        );

        $isFreeDeliveryCoupon = $cartIndependentResult['coupon']->coupon_type === CouponService::TYPE_FREE_DELIVERY;

        return $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: $cartItems,
            groupShippingCosts: $isFreeDeliveryCoupon && $cartItems->isNotEmpty()
                ? $this->cartService->getGroupShippingCosts(
                    customerId: $customerId,
                    requestGuestId: $request['guest_id'],
                    cartGroupIds: $cartItems->pluck('cart_group_id')->unique()->map(fn ($cartGroupId) => (string) $cartGroupId)->values()->all(),
                )
                : [],
        );
    }

    private function getCheckedCartProductTypes(Request $request, ?int $customerId): array
    {
        $this->cartService->refreshPrices(customerId: $customerId, guestId: session('guest_id') ?? 0);
        $this->cartService->refreshShippingCosts(customerId: $customerId, guestId: $request['guest_id'], type: CartService::TYPE_CHECKED);

        return $this->cartService->getCartItemList(
            customerId: $customerId,
            guestId: session('guest_id') ?? $request['guest_id'] ?? 0,
            type: CartService::TYPE_CHECKED,
        )->pluck('product_type')->toArray();
    }

    private function getOtherAddressContactError(array $shipping, array $billing, bool $isGuest, bool $isBillingEmailRequired, bool $isBillingSeparate): ?string
    {
        if ($isGuest) {
            if (empty($shipping['email'])) {
                return translate('Shipping email is required.');
            }

            if (! filter_var($shipping['email'], FILTER_VALIDATE_EMAIL)) {
                return translate('Shipping email must be a valid email address.');
            }

            if ($isBillingEmailRequired) {
                if (empty($billing['billing_contact_email'])) {
                    return translate('Billing email is required.');
                }

                if (! filter_var($billing['billing_contact_email'], FILTER_VALIDATE_EMAIL)) {
                    return translate('Billing email must be a valid email address.');
                }
            }
        }

        if (isset($shipping['phone'])) {
            $phoneLengthError = $this->getPhoneLengthError(phone: $shipping['phone']);

            if ($phoneLengthError !== null) {
                return $phoneLengthError;
            }
        }

        if ($isBillingSeparate && isset($billing['billing_phone'])) {
            return $this->getPhoneLengthError(phone: $billing['billing_phone']);
        }

        return null;
    }

    private function getPhoneLengthError(mixed $phone): ?string
    {
        $phoneDigitCount = strlen(preg_replace('/[^0-9]/', '', $phone));

        if ($phoneDigitCount < self::PHONE_MIN_DIGITS) {
            return translate('The phone number must be at least 4 characters');
        }

        if ($phoneDigitCount > self::PHONE_MAX_DIGITS) {
            return translate('The phone number may not be greater than 20 characters');
        }

        return null;
    }

    private function getCheckoutRegistrationError(Request $request, array $shipping, array $billing): ?string
    {
        if (empty($request['customer_password']) || empty($request['customer_confirm_password'])) {
            return translate('The password or confirm password cannot be empty');
        }

        if ($request['customer_password'] != $request['customer_confirm_password']) {
            return translate('The password and confirm password must match');
        }

        if (strlen($request['customer_password']) < self::PASSWORD_MIN_LENGTH || strlen($request['customer_confirm_password']) < self::PASSWORD_MIN_LENGTH) {
            return translate('The password must be at least 8 characters');
        }

        $registrant = $request['shipping']
            ? ['name' => $shipping['contact_person_name'] ?? null, 'email' => $shipping['email'] ?? null, 'phone' => $shipping['phone'] ?? null]
            : ['name' => $billing['billing_contact_person_name'] ?? null, 'email' => $billing['billing_contact_email'] ?? null, 'phone' => $billing['billing_phone'] ?? null];

        if ($this->customerService->existsByEmailOrPhone(email: $registrant['email'], phone: $registrant['phone'])) {
            return translate('Already registered');
        }

        session()->put('newCustomerRegister', [
            'name' => $registrant['name'],
            'f_name' => $registrant['name'],
            'l_name' => '',
            'email' => $registrant['email'],
            'phone' => $registrant['phone'],
            'is_active' => 1,
            'password' => $request['customer_password'],
            'referral_code' => $this->customerAuthService->generateReferralCode(),
            'shipping_id' => session('address_id'),
            'billing_id' => session('billing_address_id'),
        ]);

        return null;
    }

    private function getOtherShippingAddressColumns(array $shipping, bool $isGuest): array
    {
        return [
            'contact_person_name' => $shipping['contact_person_name'],
            'address_type' => $shipping['address_type'],
            'address' => $shipping['address'],
            'city' => $shipping['city'],
            'zip' => $shipping['zip'],
            'country' => $shipping['country'],
            'phone' => $shipping['phone'],
            'email' => $isGuest ? $shipping['email'] : null,
            'latitude' => $shipping['latitude'] ?? '',
            'longitude' => $shipping['longitude'] ?? '',
            'is_billing' => 0,
        ];
    }

    private function getOtherShippingAddressUpdateData(array $shipping): array
    {
        return [
            'contact_person_name' => $shipping['contact_person_name'],
            'address_type' => $shipping['address_type'],
            'address' => $shipping['address'],
            'city' => $shipping['city'],
            'zip' => $shipping['zip'],
            'country' => $shipping['country'],
            'phone' => $shipping['phone'],
            'latitude' => $shipping['latitude'] ?? '',
            'longitude' => $shipping['longitude'] ?? '',
        ];
    }

    private function getOtherBillingAddressColumns(array $billing, bool $isGuest): array
    {
        return [
            'contact_person_name' => $billing['billing_contact_person_name'],
            'address_type' => $billing['billing_address_type'],
            'address' => $billing['billing_address'],
            'city' => $billing['billing_city'],
            'zip' => $billing['billing_zip'],
            'country' => $billing['billing_country'],
            'phone' => $billing['billing_phone'],
            'email' => $isGuest ? $billing['billing_contact_email'] : null,
            'latitude' => $billing['billing_latitude'] ?? '',
            'longitude' => $billing['billing_longitude'] ?? '',
            'is_billing' => 1,
        ];
    }

    private function getOtherBillingAddressUpdateData(array $billing): array
    {
        return [
            'contact_person_name' => $billing['billing_contact_person_name'],
            'address_type' => $billing['billing_address_type'],
            'address' => $billing['billing_address'],
            'city' => $billing['billing_city'],
            'zip' => $billing['billing_zip'],
            'country' => $billing['billing_country'],
            'phone' => $billing['billing_phone'],
            'latitude' => $billing['billing_latitude'] ?? '',
            'longitude' => $billing['billing_longitude'] ?? '',
        ];
    }

    private function getAddressOwnerColumns(): array
    {
        $customerId = auth('customer')->id();

        return [
            'customer_id' => $customerId ?? (session()->has('guest_id') ? session('guest_id') : 0),
            'is_guest' => $customerId !== null ? 0 : (session()->has('guest_id') ? 1 : 0),
        ];
    }

    private function getOneOffAddressOwnerColumns(): array
    {
        $isSignedIn = auth('customer')->check();

        return [
            'customer_id' => $isSignedIn ? 0 : (session()->has('guest_id') ? session('guest_id') : 0),
            'is_guest' => $isSignedIn ? 0 : (session()->has('guest_id') ? 1 : 0),
        ];
    }

    private function findOwnedAddress(mixed $id): ?Model
    {
        if (! is_scalar($id)) {
            return null;
        }

        $owner = $this->getAddressOwnerColumns();

        return $this->shippingAddressService->find(
            id: $id,
            ownerId: (string) $owner['customer_id'],
            isGuest: (bool) $owner['is_guest'],
        );
    }

    private function getDeliveryRestrictionError(mixed $country, mixed $zip, string $countryUnavailableMessage): ?string
    {
        if (getWebConfig(name: 'delivery_country_restriction') && ! $this->deliveryCountryCodeService->hasCountryName(countryName: is_string($country) ? $country : null)) {
            return $countryUnavailableMessage;
        }

        if (getWebConfig(name: 'delivery_zip_code_area_restriction') && ! $this->deliveryZipCodeService->hasZipCode(zipCode: $zip)) {
            return translate('Delivery unavailable in this zip code area');
        }

        return null;
    }

    private function hasMissingFields(array $fields, array $requiredFields): bool
    {
        foreach ($requiredFields as $requiredField) {
            if (($fields[$requiredField] ?? null) == null) {
                return true;
            }
        }

        return false;
    }

    private function parseSerializedForm(mixed $serializedForm): array
    {
        $fields = [];

        parse_str(is_string($serializedForm) ? $serializedForm : '', $fields);

        return $fields;
    }

    private function respondWithAddressError(string $message): JsonResponse
    {
        return response()->json(['errors' => $message], 403);
    }
}
