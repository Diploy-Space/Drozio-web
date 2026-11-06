<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Order\DigitalPaymentRequest;
use App\Http\Requests\Customer\Order\DuePaymentByCodRequest;
use App\Http\Requests\Customer\Order\DuePaymentByDigitalPaymentRequest;
use App\Http\Requests\Customer\Order\DuePaymentByOfflinePaymentRequest;
use App\Http\Requests\Customer\Order\DuePaymentByWalletRequest;
use App\Services\New\AdminWalletService;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\CouponService;
use App\Services\New\CurrencyService;
use App\Services\New\CustomerService;
use App\Services\New\GuestUserService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\WalletFundService;
use App\Services\New\WalletTransactionService;
use App\Traits\CustomerOwnerTrait;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;
use Throwable;

class OrderPaymentController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const CURRENCY_MODEL_MULTI = 'multi_currency';

    private const INHOUSE_SHIPPING = 'inhouse_shipping';

    private const SELLER_IS_ADMIN = 'admin';

    private const PAYMENT_METHOD_WALLET = 'wallet';

    private const WALLET_REFERENCE_REDUCE_AMOUNT = 'reduce_wallet_amount';

    private const PAYMENT_GATEWAY_ERROR_CODE = 'payment_gateway';

    private const CHECKOUT_PAYMENT_ROUTE = 'checkout-payment';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly CustomerService $customerService,
        private readonly CustomerAuthService $customerAuthService,
        private readonly WalletTransactionService $walletTransactionService,
        private readonly WalletFundService $walletFundService,
        private readonly AdminWalletService $adminWalletService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly CurrencyService $currencyService,
        private readonly CartService $cartService,
        private readonly CartShippingService $cartShippingService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly CouponService $couponService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Pay the due amount of an edited order from the customer wallet',
        description: 'The wallet debit, the admin wallet, the edit history and the order settle in one transaction, so a failed debit settles nothing.',
        tag: 'Order payment',
        status: [401, 403, 404, 422, 500],
        auth: false,
        guard: 'api',
    )]
    public function payDueByWallet(DuePaymentByWalletRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findOwnerOrder(owner: $owner, id: $id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        if (getWebConfig(name: 'wallet_status') != 1) {
            return $this->responseFormatter(config: config('response.order_wallet_disabled'));
        }

        if ($owner['is_guest']) {
            return $this->responseFormatter(config: config('response.order_due_wallet_customer_required'));
        }

        $customer = $this->customerService->find(id: $owner['id']);

        if (! $this->orderService->canPayEditDueFromWallet(order: $order, customer: $customer)) {
            return $this->responseFormatter(config: config('response.order_due_wallet_insufficient_balance'));
        }

        try {
            DB::transaction(function () use ($order, $customer): void {
                if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) == 1) {
                    $this->debitCustomerWallet(customer: $customer, amount: (float) $order['edit_due_amount']);
                }

                $this->adminWalletService->incrementInHousePendingAmount(amount: $order['edit_due_amount']);
                $this->orderEditHistoryService->markDuePaymentPaid(id: $order->latestEditHistory?->id, paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_WALLET);
                $this->orderService->settleEditDueAmount(order: $order);
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->responseFormatter(config: config('response.order_due_payment_failed'));
        }

        return $this->responseFormatter(config: config('response.order_due_payment_successful'));
    }

    #[ApiDoc(
        summary: 'Pay the due amount of an edited order as cash on delivery',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order payment',
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function payDueByCashOnDelivery(DuePaymentByCodRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findOwnerOrder(owner: $owner, id: $id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $bringChangeAmountCurrency = null;

        if ($request['bring_change_amount']) {
            $bringChangeAmountCurrency = $this->resolveBringChangeAmountCurrency(request: $request);

            if ($bringChangeAmountCurrency === null) {
                return $this->responseFormatter(config: config('response.payment_default_currency_unavailable'));
            }
        }

        $this->orderEditHistoryService->updateDuePaymentMethod(
            id: $order->latestEditHistory?->id,
            paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_CASH_ON_DELIVERY,
        );

        if ($bringChangeAmountCurrency !== null) {
            $this->orderService->updateWhere(params: ['id' => $order->id], data: [
                'bring_change_amount' => $request['bring_change_amount'] ?? 0,
                'bring_change_amount_currency' => $bringChangeAmountCurrency,
            ]);
        }

        return $this->responseFormatter(config: config('response.order_due_payment_method_updated'));
    }

    #[ApiDoc(
        summary: 'Pay the due amount of an edited order by an offline payment method',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. method_informations is a base64 encoded JSON object keyed by the method\'s customer input names.',
        tag: 'Order payment',
        status: [401, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function payDueByOfflinePayment(DuePaymentByOfflinePaymentRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findOwnerOrder(owner: $owner, id: $id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $this->orderEditHistoryService->updateDuePaymentMethod(
            id: $order->latestEditHistory?->id,
            paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_OFFLINE,
            paymentInfo: $this->buildOfflinePaymentInfo(request: $request),
            paymentNote: $request['order_due_payment_note'] ?? '',
        );

        return $this->responseFormatter(config: config('response.order_due_payment_method_updated'));
    }

    #[ApiDoc(
        summary: 'Create a payment gateway link for the due amount of an edited order',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. payment_method is the gateway key.',
        tag: 'Order payment',
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function payDueByDigitalPayment(DuePaymentByDigitalPaymentRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findOwnerOrder(owner: $owner, id: $id);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $defaultCurrencyCode = $this->getDefaultCurrencyCode();

        if (! $this->isMultiCurrency() && $defaultCurrencyCode === null) {
            return $this->responseFormatter(config: config('response.payment_default_currency_unavailable'));
        }

        try {
            $redirectLink = $this->walletFundService->generateEditDuePaymentLink(
                order: $order,
                data: $request->only(['payment_method', 'payment_platform', 'payment_request_from', 'current_currency_code', 'order_due_payment_note']),
                customer: $owner['is_guest'] ? null : $this->customerService->find(id: $owner['id']),
                guestId: $owner['is_guest'] ? $owner['id'] : 0,
                defaultCurrencyCode: $defaultCurrencyCode,
            );
        } catch (Exception $exception) {
            return $this->paymentLinkFailed(responseKey: 'order_due_payment_link_failed', message: $exception->getMessage());
        }

        if (! $redirectLink) {
            return $this->responseFormatter(config: config('response.order_due_payment_link_failed'));
        }

        return $this->responseFormatter(
            config: config('response.order_due_payment_link_created'),
            content: ['data' => ['redirect_link' => $redirectLink]],
        );
    }

    #[ApiDoc(
        summary: 'Create a payment gateway link for the selected cart items',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper. A guest may send is_check_create_account with a password to open an account from the checkout address.',
        tag: 'Order payment',
        status: [401, 403, 422],
        auth: false,
        guard: 'api',
    )]
    public function createDigitalPayment(DigitalPaymentRequest $request): JsonResponse
    {
        $owner = $this->resolveCustomerOwner(request: $request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $customer = $owner['is_guest'] ? null : $this->customerService->find(id: $owner['id']);
        $customerId = $customer?->id;
        $guestId = $owner['is_guest'] ? $owner['id'] : null;

        $blockers = $this->getCheckoutBlockers(customerId: $customerId, guestId: $guestId);
        if ($blockers !== []) {
            return $this->responseFormatter(
                config: config('response.'.$this->checkoutBlockedResponseKey(blockers: $blockers)),
                errors: $this->checkoutBlockerErrors(blockers: $blockers),
            );
        }

        $cartItems = $this->getCheckedCartItemsWithProduct(customerId: $customerId, guestId: $guestId);
        if (! $this->cartService->hasEnoughStock(cartItems: $cartItems)) {
            return $this->responseFormatter(config: config('response.order_cart_out_of_stock'));
        }

        $minimumOrderAmount = $this->cartService->verifyCartListMinimumOrderAmount(customerId: $customerId, sessionGuestId: null, requestGuestId: $guestId);
        if ($minimumOrderAmount['status'] == CartService::STATUS_FAILED) {
            return $this->responseFormatter(config: config('response.order_minimum_amount_required'));
        }

        if ($this->hasOrderWiseGroupWithoutShipping(cartItems: $cartItems)) {
            return $this->responseFormatter(config: config('response.digital_payment_shipping_method_required'));
        }

        $newCustomerInfo = null;
        if ($customer === null && $request->isCreateAccountRequested()) {
            $registration = $this->getCheckoutRegistration(request: $request, guestId: $guestId);

            if ($registration !== null && $registration['status'] == 0) {
                return $this->responseFormatter(config: config('response.order_customer_already_registered'));
            }

            $newCustomerInfo = $registration['data'] ?? null;
        }

        $orderAmount = $this->cartService->getPayableAmount(vendorWiseCartList: $this->getOrderGenerateCartList(customerId: $customerId, guestId: $guestId, couponCode: $request->couponCode()));

        if ($customer !== null && empty($customer['phone'])) {
            return $this->checkoutPaymentLinkCreated(redirectLink: route(self::CHECKOUT_PAYMENT_ROUTE), newCustomerInfo: $newCustomerInfo);
        }

        $defaultCurrencyCode = $this->getDefaultCurrencyCode();
        if (! $this->isMultiCurrency() && $defaultCurrencyCode === null) {
            return $this->responseFormatter(config: config('response.payment_default_currency_unavailable'));
        }

        try {
            $redirectLink = $this->walletFundService->generateCheckoutPaymentLink(
                data: $this->buildCheckoutPaymentData(request: $request, customerId: $customerId, guestId: $guestId),
                customer: $customer,
                guestPayerAddress: $guestId === null ? null : $this->shippingAddressService->getList(
                    dataLimit: 1,
                    filters: ['customer_id' => $guestId, 'is_guest' => 1],
                    sortBy: ShippingAddressService::SORT_LATEST,
                )->first(),
                orderAmount: $orderAmount,
                defaultCurrencyCode: $defaultCurrencyCode,
                newCustomerInfo: $newCustomerInfo,
            );
        } catch (Exception $exception) {
            return $this->paymentLinkFailed(responseKey: 'digital_payment_link_failed', message: $exception->getMessage());
        }

        return $this->checkoutPaymentLinkCreated(redirectLink: $redirectLink, newCustomerInfo: $newCustomerInfo);
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function checkoutPaymentLinkCreated(mixed $redirectLink, ?array $newCustomerInfo): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.digital_payment_link_created'),
            content: ['data' => [
                'redirect_link' => $redirectLink,
                'new_user' => $newCustomerInfo !== null ? 1 : 0,
            ]],
        );
    }

    private function findOwnerOrder(array $owner, int|string $id): ?Model
    {
        return $this->orderService->findForCustomerOwner(
            id: $id,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
            relations: ['latestEditHistory'],
        );
    }

    private function debitCustomerWallet(Model $customer, float $amount): void
    {
        DB::transaction(function () use ($customer, $amount): void {
            $walletTransaction = $this->walletTransactionService->createWalletEntry(
                customerId: $customer->id,
                currentBalance: (float) $customer->wallet_balance,
                amount: $amount,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER,
                reference: self::WALLET_REFERENCE_REDUCE_AMOUNT,
                paymentMethod: self::PAYMENT_METHOD_WALLET,
            );

            if ($walletTransaction !== null) {
                $this->customerService->updateWalletBalance(
                    id: $customer->id,
                    walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
                );
            }
        });
    }

    private function resolveBringChangeAmountCurrency(DuePaymentByCodRequest $request): ?string
    {
        $defaultCurrencyCode = $this->getDefaultCurrencyCode();

        if ($this->isMultiCurrency()) {
            return $request['current_currency_code'] ?? $defaultCurrencyCode;
        }

        return $defaultCurrencyCode;
    }

    private function buildOfflinePaymentInfo(DuePaymentByOfflinePaymentRequest $request): array
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

    private function paymentLinkFailed(string $responseKey, string $message): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.'.$responseKey),
            errors: [['error_code' => self::PAYMENT_GATEWAY_ERROR_CODE, 'message' => $message]],
        );
    }

    private function isMultiCurrency(): bool
    {
        return getWebConfig(name: 'currency_model') == self::CURRENCY_MODEL_MULTI;
    }

    private function getDefaultCurrencyCode(): ?string
    {
        return $this->currencyService->find(id: getWebConfig(name: 'system_default_currency'))?->code;
    }

    /** @return array<string, mixed> */
    private function buildCheckoutPaymentData(DigitalPaymentRequest $request, ?int $customerId, ?string $guestId): array
    {
        return [
            'payment_method' => $request['payment_method'],
            'payment_platform' => $request['payment_platform'],
            'customer_id' => $customerId ?? (int) $guestId,
            'guest_id' => $guestId === null ? null : (int) $guestId,
            'is_guest' => $guestId === null ? 0 : 1,
            'order_note' => $request['order_note'],
            'address_id' => $request['address_id'],
            'billing_address_id' => $request['billing_address_id'],
            'coupon_code' => $request['coupon_code'],
            'coupon_discount' => $request['coupon_discount'],
            'current_currency_code' => $request['current_currency_code'],
            'external_redirect_link' => $request['external_redirect_link'],
            OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS => $this->cartService->getCartGroupIds(
                customerId: $customerId,
                guestId: $guestId ?? 0,
                type: CartService::TYPE_CHECKED,
            ),
        ];
    }

    /** @return array<int, array{error_code: int, message: string}> */
    private function getCheckoutBlockers(?int $customerId, ?string $guestId): array
    {
        $checkout = $this->cartService->getCheckoutBlockers(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            activeShippingMethodCounts: $this->shippingMethodService->getActiveMethodCountsByCreator(),
        );

        return $checkout['status'] == CartService::STATUS_FAILED ? array_values($checkout['blockers']) : [];
    }

    private function checkoutBlockedResponseKey(array $blockers): string
    {
        $isShippingMethodMissing = collect($blockers)
            ->contains(static fn (array $blocker): bool => in_array($blocker['reason'], CartService::SHIPPING_METHOD_REQUIRED_REASONS, true));

        return $isShippingMethodMissing ? 'digital_payment_shipping_method_required' : 'digital_payment_checkout_blocked';
    }

    private function checkoutBlockerErrors(array $blockers): array
    {
        $errors = [];
        foreach ($blockers as $index => $blocker) {
            $errors[] = ['error_code' => $index, 'message' => $this->checkoutBlockerMessage(blocker: $blocker)];
        }

        return $errors;
    }

    private function checkoutBlockerMessage(array $blocker): string
    {
        return match ($blocker['reason']) {
            CartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT => translate('Please complete the minimum order amount for :shop', ['shop' => $blocker['shop_name']]),
            CartService::REASON_CART_EMPTY => translate('No items in basket'),
            CartService::REASON_NO_CHECKED_ITEMS => translate('Please add or check items before proceeding to checkout'),
            CartService::REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP => translate('Shipping Not Available for').' '.$blocker['shop_name'],
            CartService::REASON_SHIPPING_METHOD_REQUIRED => translate('Select shipping method'),
            CartService::REASON_SHOP_SHIPPING_METHOD_REQUIRED => translate('Select').' '.$blocker['shop_name'].' '.translate('Shipping Method'),
            CartService::REASON_UNAVAILABLE_VENDORS => translate('Please remove all products from unavailable vendors'),
            CartService::REASON_UNAVAILABLE_PRODUCT => translate('Please remove this unavailable product to continue'),
        };
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

    private function hasOrderWiseGroupWithoutShipping(Collection $cartItems): bool
    {
        $shippingMethod = getWebConfig(name: 'shipping_method');
        $physicalProductExist = false;

        foreach ($cartItems as $cartItem) {
            if ($cartItem->product_type == CartService::PRODUCT_TYPE_PHYSICAL) {
                $physicalProductExist = true;
            }

            $shippingTypeOwnerId = $shippingMethod == self::INHOUSE_SHIPPING || $cartItem->seller_is == self::SELLER_IS_ADMIN
                ? ShippingTypeService::ADMIN_SELLER_ID
                : $cartItem->seller_id;

            if ($this->shippingTypeService->resolveTypeForSeller(sellerId: $shippingTypeOwnerId) == ShippingTypeService::TYPE_ORDER_WISE
                && $physicalProductExist
                && ! $this->cartShippingService->exists(params: ['cart_group_id' => $cartItem->cart_group_id])) {
                return true;
            }
        }

        return false;
    }

    /** @return array{status: int, data?: array<string, mixed>}|null */
    private function getCheckoutRegistration(Request $request, ?string $guestId): ?array
    {
        $registration = null;

        $shippingAddress = $request['address_id']
            ? $this->shippingAddressService->find(id: $request['address_id'], ownerId: $guestId, isGuest: true)
            : null;
        if ($shippingAddress !== null) {
            if ($this->customerService->existsByEmailOrPhone(email: $shippingAddress['email'], phone: $shippingAddress['phone'])) {
                return ['status' => 0];
            }

            $registration = [
                'status' => 1,
                'data' => $this->buildCheckoutRegistration(
                    request: $request,
                    address: $shippingAddress,
                    shippingId: $request['address_id'],
                    billingId: $request['billing_address_id'] ?: null,
                ),
            ];
        }

        $billingAddress = $request['billing_address_id']
            ? $this->shippingAddressService->find(id: $request['billing_address_id'], ownerId: $guestId, isGuest: true)
            : null;
        if ($request['address_id'] == null && $billingAddress !== null) {
            if ($this->customerService->existsByEmailOrPhone(email: $billingAddress['email'], phone: $billingAddress['phone'])) {
                return ['status' => 0];
            }

            $registration = [
                'status' => 1,
                'data' => $this->buildCheckoutRegistration(
                    request: $request,
                    address: $billingAddress,
                    shippingId: null,
                    billingId: $request['billing_address_id'],
                ),
            ];
        }

        return $registration;
    }

    /** @return array<string, mixed> */
    private function buildCheckoutRegistration(Request $request, Model $address, mixed $shippingId, mixed $billingId): array
    {
        return [
            'name' => $address['contact_person_name'],
            'f_name' => $address['contact_person_name'],
            'l_name' => '',
            'email' => $address['email'],
            'phone' => $address['phone'],
            'is_active' => 1,
            'password' => $request['password'],
            'referral_code' => $this->customerAuthService->generateReferralCode(),
            'shipping_id' => $shippingId,
            'billing_id' => $billingId,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function getOrderGenerateCartList(?int $customerId, ?string $guestId, string $couponCode): array
    {
        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            type: CartService::TYPE_CHECKED,
        );
        $coupon = $couponCode === '' ? null : $this->couponService->findBy(params: ['code' => $couponCode, 'status' => CouponService::STATUS_ACTIVE, CouponService::FILTER_RUNNING => true]);
        $groupShippingCosts = $coupon?->coupon_type === CouponService::TYPE_FREE_DELIVERY
            ? $this->cartService->getGroupShippingCosts(customerId: $customerId, requestGuestId: $guestId, cartGroupIds: $cartItems->pluck('cart_group_id')->unique()->values()->all())
            : [];
        $couponOrderGroupCount = $customerId ? $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]) : 0;
        $cartCoupon = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $couponOrderGroupCount,
            customerOrderCount: $customerId ? $this->orderService->count(filters: ['customer_id' => $customerId]) : 0,
            cartItems: $cartItems,
            groupShippingCosts: $groupShippingCosts,
        );

        $groupCouponDiscounts = [];
        foreach ($cartItems->groupBy('cart_group_id') as $cartGroupId => $groupCartItems) {
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
            completedOrderCount: $customerId ? $this->orderService->count(filters: [
                'customer_id' => $customerId,
                'order_status' => OrderService::STATUS_DELIVERED,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
            ]) : 0,
        );
        $referralBases = $this->cartService->getReferralDiscountBases(
            cartItems: $cartItems,
            totalCouponDiscount: $cartCoupon['discount'],
            groupCouponDiscounts: $groupCouponDiscounts,
        );

        $groupReferralDiscounts = [];
        foreach ($referralBases['group_amounts'] as $cartGroupId => $groupAmount) {
            $groupReferralDiscounts[$cartGroupId] = $this->referralCustomerService->calculateReferralDiscount(
                referralCustomer: $referralCustomer,
                totalAmount: $referralBases['total_amount'],
                groupAmount: $groupAmount,
            );
        }

        return $this->cartService->getOrderGenerateCartList(
            customerId: $customerId,
            sessionGuestId: null,
            requestGuestId: $guestId,
            groupCouponDiscounts: $groupCouponDiscounts,
            groupReferralDiscounts: $groupReferralDiscounts,
            shippingAddressId: 0,
            billingAddressId: 0,
        );
    }
}
