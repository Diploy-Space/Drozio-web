<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Events\CheckoutCartStateRequestedEvent;
use App\Events\CheckoutOrdersPlacingEvent;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\OrderPlacement\CashOnDeliveryRequest;
use App\Http\Requests\Customer\OrderPlacement\OfflinePaymentRequest;
use App\Http\Requests\Customer\OrderPlacement\WalletRequest;
use App\Http\Resources\V6\Customer\OfflinePaymentMethodResource;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CurrencyService;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryCountryCodeService;
use App\Services\New\DeliveryZipCodeService;
use App\Services\New\GuestUserService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use App\Traits\CustomerOwnerTrait;
use App\Utils\Convert;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Throwable;

class OrderPlacementController extends BaseApiController
{
    use CustomerOwnerTrait;

    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly CustomerService $customerService,
        private readonly CustomerAuthService $customerAuthService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly CurrencyService $currencyService,
        private readonly DeliveryCountryCodeService $deliveryCountryCodeService,
        private readonly DeliveryZipCodeService $deliveryZipCodeService,
        private readonly GuestUserService $guestUserService,
        private readonly ShippingMethodService $shippingMethodService,
    ) {}

    #[ApiDoc(
        summary: 'Place the selected cart items as cash on delivery orders',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. A guest may send is_check_create_account with a password to open an account from the checkout address.',
        tag: 'Order placement',
        status: [401, 403, 422, 500],
        auth: false,
        guard: 'api',
    )]
    public function placeByCashOnDelivery(CashOnDeliveryRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $customer = $this->resolveOwnerCustomer(owner: $owner);
        $guestId = $this->ownerGuestId(owner: $owner);

        $blockerResponseKey = $this->getCartBlockerResponseKey(customerId: $customer?->id, guestId: $guestId);
        if ($blockerResponseKey !== null) {
            return $this->responseFormatter(config: config('response.'.$blockerResponseKey));
        }

        $addressCheck = $this->checkOrderAddresses(request: $request, owner: $owner, isCreateAccountRequested: $request->isCreateAccountRequested());
        if ($addressCheck['response_key'] !== null) {
            return $this->responseFormatter(config: config('response.'.$addressCheck['response_key']));
        }

        $bringChangeAmountCurrency = $this->currencyService->resolveBringChangeAmountCurrencyCode(currentCurrencyCode: $request['current_currency_code']);

        return $this->respondWithPlacement(placement: function () use ($request, $customer, $guestId, $addressCheck, $bringChangeAmountCurrency): array {
            $newCustomer = $this->registerCheckoutCustomer(request: $request, address: $addressCheck['registration_address'], guestId: $guestId);

            return [
                'order_ids' => $this->placeCheckoutOrders(
                    customer: $customer,
                    guestId: $guestId,
                    newCustomer: $newCustomer,
                    orderData: [
                        'order_status' => OrderService::STATUS_PENDING,
                        'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
                        'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
                        'transaction_ref' => '',
                        'bring_change_amount' => $request['bring_change_amount'] ?? 0,
                        'bring_change_amount_currency' => $bringChangeAmountCurrency,
                        'order_note' => $request['order_note'] ?? '',
                    ],
                    checkoutSession: $this->buildCheckoutSession(request: $request, couponCode: $request->couponCode()),
                )['order_ids'],
                'new_user' => $newCustomer !== null,
            ];
        });
    }

    #[ApiDoc(
        summary: 'Place the selected cart items as offline payment orders',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. method_informations is a base64 encoded JSON object keyed by the method\'s customer input names.',
        tag: 'Order placement',
        status: [401, 403, 422, 500],
        auth: false,
        guard: 'api',
    )]
    public function placeByOfflinePayment(OfflinePaymentRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $customer = $this->resolveOwnerCustomer(owner: $owner);
        $guestId = $this->ownerGuestId(owner: $owner);

        $blockerResponseKey = $this->getCartBlockerResponseKey(customerId: $customer?->id, guestId: $guestId);
        if ($blockerResponseKey !== null) {
            return $this->responseFormatter(config: config('response.'.$blockerResponseKey));
        }

        $addressCheck = $this->checkOrderAddresses(request: $request, owner: $owner, isCreateAccountRequested: $request->isCreateAccountRequested());
        if ($addressCheck['response_key'] !== null) {
            return $this->responseFormatter(config: config('response.'.$addressCheck['response_key']));
        }

        $offlinePaymentInfo = $this->buildOfflinePaymentInfo(request: $request);

        return $this->respondWithPlacement(placement: function () use ($request, $customer, $guestId, $addressCheck, $offlinePaymentInfo): array {
            $newCustomer = $this->registerCheckoutCustomer(request: $request, address: $addressCheck['registration_address'], guestId: $guestId);

            return [
                'order_ids' => $this->placeCheckoutOrders(
                    customer: $customer,
                    guestId: $guestId,
                    newCustomer: $newCustomer,
                    orderData: [
                        'order_status' => OrderService::STATUS_PENDING,
                        'payment_method' => OrderService::PAYMENT_METHOD_OFFLINE,
                        'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
                        'transaction_ref' => '',
                        'offline_payment_info' => $offlinePaymentInfo,
                        'payment_note' => $request['payment_note'],
                        'order_note' => $request['order_note'],
                    ],
                    checkoutSession: $this->buildCheckoutSession(request: $request, couponCode: $request->couponCode()),
                )['order_ids'],
                'new_user' => $newCustomer !== null,
            ];
        });
    }

    #[ApiDoc(
        summary: 'Pay for the selected cart items from the customer wallet',
        description: 'The placement and the wallet debit share one transaction, so a failed debit places nothing.',
        tag: 'Order placement',
        status: [401, 403, 422, 500],
    )]
    public function placeByWallet(WalletRequest $request): JsonResponse
    {
        $customer = auth('api')->user();
        $owner = ['id' => (string) $customer->id, 'is_guest' => false];
        $checkoutSession = $this->buildCheckoutSession(request: $request, couponCode: $request->couponCode());

        $blockerResponseKey = $this->getCartBlockerResponseKey(customerId: $customer->id, guestId: null);
        if ($blockerResponseKey !== null) {
            return $this->responseFormatter(config: config('response.'.$blockerResponseKey));
        }

        $cartState = new CheckoutCartStateRequestedEvent(
            customer: $customer,
            requestGuestId: null,
            checkoutSession: $checkoutSession,
            couponCode: $request->couponCode(),
            shippingAddressId: $request['address_id'],
            billingAddressId: $request['billing_address_id'],
        );

        event($cartState);

        $paymentAmount = $this->cartService->getPayableAmount(vendorWiseCartList: $cartState->cartState['vendor_carts']);

        if ($paymentAmount > $customer->wallet_balance) {
            return $this->responseFormatter(config: config('response.order_wallet_insufficient_balance'));
        }

        $addressCheck = $this->checkOrderAddresses(request: $request, owner: $owner, isCreateAccountRequested: false);
        if ($addressCheck['response_key'] !== null) {
            return $this->responseFormatter(config: config('response.'.$addressCheck['response_key']));
        }

        if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) != 1) {
            return $this->responseFormatter(config: config('response.order_wallet_disabled'));
        }

        return $this->respondWithPlacement(placement: fn (): array => [
            'order_ids' => $this->placeCheckoutOrders(
                customer: $customer,
                guestId: null,
                newCustomer: null,
                orderData: [
                    'order_status' => OrderService::STATUS_CONFIRMED,
                    'payment_method' => OrderService::PAYMENT_METHOD_WALLET,
                    'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                    'transaction_ref' => '',
                    'payment_note' => $request['payment_note'],
                    'order_note' => $request['order_note'],
                ],
                checkoutSession: $checkoutSession,
                walletDebitAmount: Convert::default($paymentAmount),
            )['order_ids'],
        ]);
    }

    #[ApiDoc(
        summary: 'Active offline payment methods',
        tag: 'Order placement',
        auth: false,
        guard: 'api',
    )]
    public function getOfflinePaymentMethods(): JsonResponse
    {
        $methods = $this->offlinePaymentMethodService->getList(
            dataLimit: 'all',
            filters: ['status' => OfflinePaymentMethodService::STATUS_ACTIVE],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => OfflinePaymentMethodResource::collection($methods)],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    /**
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $checkoutSession
     * @return array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null}
     */
    private function placeCheckoutOrders(?Model $customer, ?string $guestId, ?Model $newCustomer, array $orderData, array $checkoutSession, ?float $walletDebitAmount = null): array
    {
        $placing = new CheckoutOrdersPlacingEvent(
            customer: $customer,
            requestGuestId: $guestId,
            orderCustomer: $this->resolveOrderCustomer(customer: $customer, guestId: $guestId, newCustomer: $newCustomer),
            orderData: $orderData,
            checkoutSession: $checkoutSession,
            walletDebitAmount: $walletDebitAmount,
        );

        event($placing);

        return $placing->placement;
    }

    private function respondWithPlacement(Closure $placement): JsonResponse
    {
        try {
            $placed = $placement();
        } catch (InsufficientStockException) {
            return $this->responseFormatter(config: config('response.order_cart_out_of_stock'));
        } catch (Throwable $exception) {
            report($exception);

            return $this->responseFormatter(config: config('response.order_placement_failed'));
        }

        return $this->responseFormatter(config: config('response.order_placed'), content: ['data' => $placed]);
    }

    /** @return array<string, mixed> */
    private function buildCheckoutSession(Request $request, string $couponCode): array
    {
        return [
            'guest_id' => null,
            'coupon_code' => $couponCode === '' ? null : $couponCode,
            'address_id' => $request['address_id'],
            'billing_address_id' => $request['billing_address_id'],
            'order_note' => $request['order_note'],
        ];
    }

    /** @return array{id: int|string|null, is_guest: int} */
    private function resolveOrderCustomer(?Model $customer, ?string $guestId, ?Model $newCustomer): array
    {
        if ($customer !== null) {
            return ['id' => $customer['id'], 'is_guest' => 0];
        }

        if ($newCustomer !== null) {
            return ['id' => $newCustomer['id'], 'is_guest' => 0];
        }

        return ['id' => $guestId, 'is_guest' => 1];
    }

    private function resolveOwnerCustomer(array $owner): ?Model
    {
        return $owner['is_guest'] ? null : auth('api')->user();
    }

    private function ownerGuestId(array $owner): ?string
    {
        return $owner['is_guest'] ? $owner['id'] : null;
    }

    private function getCartBlockerResponseKey(?int $customerId, ?string $guestId): ?string
    {
        $checkedCartItems = $this->getCheckedCartItemsWithProduct(customerId: $customerId, guestId: $guestId);

        if (! $this->cartService->hasEnoughStock(cartItems: $checkedCartItems)) {
            return 'order_cart_out_of_stock';
        }

        $minimumOrderAmount = $this->cartService->verifyCartListMinimumOrderAmount(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
        );

        if ($minimumOrderAmount['status'] == CartService::STATUS_FAILED) {
            return 'order_minimum_amount_required';
        }

        return $this->isShippingMethodMissing(customerId: $customerId, guestId: $guestId) ? 'order_shipping_method_required' : null;
    }

    private function isShippingMethodMissing(?int $customerId, ?string $guestId): bool
    {
        $checkoutBlockers = $this->cartService->getCheckoutBlockers(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            activeShippingMethodCounts: $this->shippingMethodService->getActiveMethodCountsByCreator(),
        );

        return collect($checkoutBlockers['blockers'])
            ->contains(static fn (array $blocker): bool => in_array($blocker['reason'], CartService::SHIPPING_METHOD_REQUIRED_REASONS, true));
    }

    private function getCheckedCartItemsWithProduct(?int $customerId, ?string $guestId): Collection
    {
        return $this->cartService->getList(
            dataLimit: 'all',
            relations: ['product'],
            filters: [
                'cart_group_id' => $this->cartService->getCartGroupIds(customerId: $customerId, guestId: $guestId ?? 0, type: CartService::TYPE_CHECKED),
                'is_checked' => 1,
                CartService::FILTER_ACTIVE_PRODUCT => true,
            ],
        );
    }

    /** @return array{response_key: ?string, registration_address: ?Model} */
    private function checkOrderAddresses(Request $request, array $owner, bool $isCreateAccountRequested): array
    {
        $registrationAddress = null;

        if ($owner['is_guest'] && $request['address_id']) {
            $shippingAddress = $this->findOwnerAddress(owner: $owner, id: $request['address_id']);

            if ($isCreateAccountRequested && $shippingAddress !== null) {
                if ($this->customerService->existsByEmailOrPhone(email: $shippingAddress['email'], phone: $shippingAddress['phone'])) {
                    return $this->addressCheckResult(responseKey: 'order_customer_already_registered');
                }

                $registrationAddress = $shippingAddress;
            }
        }

        if ($request['address_id'] && $this->findOwnerAddress(owner: $owner, id: $request['address_id']) === null) {
            return $this->addressCheckResult(responseKey: 'order_address_not_found');
        }

        $hasPhysicalProduct = $this->getCheckedCartItemsWithProduct(
            customerId: $owner['is_guest'] ? null : (int) $owner['id'],
            guestId: $this->ownerGuestId(owner: $owner),
        )->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)->isNotEmpty();

        if (! $hasPhysicalProduct || ! $request['billing_address_id']) {
            return $this->addressCheckResult(registrationAddress: $registrationAddress);
        }

        $billingAddress = $this->findOwnerAddress(owner: $owner, id: $request['billing_address_id']);

        if ($owner['is_guest'] && $isCreateAccountRequested && $billingAddress !== null && $request['address_id'] == null) {
            if ($this->customerService->existsByEmailOrPhone(email: $billingAddress['email'], phone: $billingAddress['phone'])) {
                return $this->addressCheckResult(responseKey: 'order_customer_already_registered');
            }

            $registrationAddress = $billingAddress;
        }

        if ($billingAddress === null) {
            return $this->addressCheckResult(responseKey: 'order_address_not_found');
        }

        if (getWebConfig(name: 'delivery_country_restriction') && ! $this->deliveryCountryCodeService->hasCountryName(countryName: $billingAddress['country'])) {
            return $this->addressCheckResult(responseKey: 'order_delivery_country_unavailable');
        }

        if (getWebConfig(name: 'delivery_zip_code_area_restriction') && ! $this->deliveryZipCodeService->hasZipCode(zipCode: $billingAddress['zip'])) {
            return $this->addressCheckResult(responseKey: 'order_delivery_zip_code_unavailable');
        }

        return $this->addressCheckResult(registrationAddress: $registrationAddress);
    }

    /** @return array{response_key: ?string, registration_address: ?Model} */
    private function addressCheckResult(?string $responseKey = null, ?Model $registrationAddress = null): array
    {
        return ['response_key' => $responseKey, 'registration_address' => $registrationAddress];
    }

    private function findOwnerAddress(array $owner, mixed $id): ?Model
    {
        return $this->shippingAddressService->findBy(params: ['id' => $id] + $this->customerOwnerFilters($owner));
    }

    private function buildOfflinePaymentInfo(OfflinePaymentRequest $request): array
    {
        $method = $this->offlinePaymentMethodService->findBy(params: [
            'id' => $request['method_id'],
            'status' => OfflinePaymentMethodService::STATUS_ACTIVE,
        ]);

        if ($method === null) {
            return [];
        }

        return array_replace(
            ['method_id' => $request['method_id'], 'method_name' => $method->method_name],
            $this->offlinePaymentMethodService->getCustomerInputValues(method: $method, input: $request->methodInformations()),
        );
    }

    private function registerCheckoutCustomer(Request $request, ?Model $address, ?string $guestId): ?Model
    {
        if ($address === null) {
            return null;
        }

        $customer = $this->customerService->createFromCheckoutRegistration(registration: [
            'name' => $address['contact_person_name'],
            'l_name' => '',
            'email' => $address['email'],
            'phone' => $address['phone'],
            'password' => $request['password'],
            'referral_code' => $this->customerAuthService->generateReferralCode(),
        ]);

        if ($customer !== null && $guestId !== null) {
            $this->shippingAddressService->moveGuestAddressesToCustomer(
                guestId: $guestId,
                customerId: $customer['id'],
                addressIds: [$request['address_id'], $request['billing_address_id']],
            );
        }

        return $customer;
    }
}
