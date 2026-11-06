<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\DigitalPaymentRequest;
use App\Http\Requests\Customer\Order\DuePaymentRequest;
use App\Http\Requests\Customer\Wallet\AddFundRequest;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Services\New\AdminWalletService;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\CouponService;
use App\Services\New\CurrencyService;
use App\Services\New\CustomerService;
use App\Services\New\OfflinePaymentMethodService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\ShippingAddressService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Services\New\VendorService;
use App\Services\New\WalletTransactionService;
use App\Traits\CheckoutBlockerRedirect;
use App\Traits\Payment;
use App\Traits\PaymentGatewayTrait;
use App\Utils\Convert;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentRequestController extends Controller
{
    use CheckoutBlockerRedirect, Payment, PaymentGatewayTrait;

    private const APP_PAYMENT_REQUEST = 'app';

    private const PAYMENT_PLATFORM_WEB = 'web';

    private const MULTI_CURRENCY = 'multi_currency';

    private const INHOUSE_SHIPPING = 'inhouse_shipping';

    private const SELLER_IS_ADMIN = 'admin';

    private const PAYMENT_METHOD_WALLET = 'wallet';

    private const PAYMENT_METHOD_OFFLINE = 'offline_payment';

    private const PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';

    private const RECEIVER_ID = '100';

    private const RECEIVER_NAME = 'receiver_name';

    private const RECEIVER_IMAGE = 'example.png';

    private const GUEST_PAYER_NAME = 'Contact person name';

    private const WALLET_REFERENCE_REDUCE_AMOUNT = 'reduce_wallet_amount';

    private const CHECKOUT_SUCCESS_HOOK = 'retail_payment_success';

    private const CHECKOUT_FAILURE_HOOK = 'retail_payment_fail';

    private const CHECKOUT_ATTRIBUTE = 'order';

    private const ADD_FUND_SUCCESS_HOOK = 'add_fund_to_wallet_success';

    private const ADD_FUND_FAILURE_HOOK = 'add_fund_to_wallet_fail';

    private const ADD_FUND_ATTRIBUTE = 'add_funds_to_wallet';

    private const DUE_PAYMENT_SUCCESS_HOOK = 'customer_order_edit_pay_due_amount_success';

    private const DUE_PAYMENT_FAILURE_HOOK = 'customer_order_edit_pay_due_amount_failed';

    private const DUE_PAYMENT_ATTRIBUTE = 'customer_order_edit_pay_due_amount';

    private const COMPANY_NAME_SETTING = 'company_name';

    public function __construct(
        private readonly CartService $cartService,
        private readonly CartShippingService $cartShippingService,
        private readonly ShippingTypeService $shippingTypeService,
        private readonly ShippingMethodService $shippingMethodService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly CustomerService $customerService,
        private readonly CustomerAuthService $customerAuthService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly CurrencyService $currencyService,
        private readonly OfflinePaymentMethodService $offlinePaymentMethodService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly WalletTransactionService $walletTransactionService,
        private readonly AdminWalletService $adminWalletService,
        private readonly VendorService $vendorService,
    ) {}

    public function checkout(DigitalPaymentRequest $request): JsonResponse|RedirectResponse
    {
        $isAppRequest = $this->isAppRequest(request: $request);
        $customer = auth('customer')->user();
        $cartCustomerId = $this->resolveCartCustomerId(request: $request, isAppRequest: $isAppRequest);

        $blockerResponse = $this->getCheckoutBlockerResponse(request: $request, customerId: $cartCustomerId, isAppRequest: $isAppRequest);
        if ($blockerResponse['status'] == CartService::STATUS_FAILED) {
            if ($isAppRequest) {
                return response()->json(['errors' => $this->formatAppBlockerErrors(messages: $blockerResponse['message'])], 403);
            }

            return $this->redirectWithCheckoutBlockers($blockerResponse);
        }

        $cartItems = $this->getCheckedCartItemsWithProduct(request: $request, customerId: $cartCustomerId);
        if (! $this->cartService->hasEnoughStock(cartItems: $cartItems)) {
            if ($isAppRequest) {
                return response()->json(['errors' => ['code' => 'product-stock', 'message' => 'The following items in your cart are currently out of stock']], 403);
            }

            ToastMagic::error(translate('The following items in your cart are currently out of stock'));

            return redirect()->route('shop-cart');
        }

        $minimumOrderAmount = $this->cartService->verifyCartListMinimumOrderAmount(
            customerId: $cartCustomerId,
            sessionGuestId: session('guest_id'),
            requestGuestId: $request['guest_id'],
        );
        if ($minimumOrderAmount['status'] == CartService::STATUS_FAILED) {
            if ($isAppRequest) {
                return response()->json(['errors' => ['code' => 'Check the minimum order amount requirement']], 403);
            }

            ToastMagic::info('Check the minimum order amount requirement');

            return redirect()->route('shop-cart');
        }

        $orderAdditionalData = [];
        if ($isAppRequest) {
            if ($this->hasOrderWiseGroupWithoutShipping(cartItems: $cartItems)) {
                return response()->json(['errors' => ['code' => 'shipping-method', 'message' => 'Data not found']], 403);
            }

            if ($customer === null && $request['is_check_create_account']) {
                $registration = $this->getAppCheckoutRegistration(request: $request);
                if ($registration !== null && $registration['status'] == 0) {
                    return response()->json(['message' => translate('Already registered')], 403);
                }

                if ($registration !== null) {
                    $orderAdditionalData += ['new_customer_info' => $registration['data']];
                }
            }
        }

        $redirectLink = $this->createCheckoutPaymentLink(
            request: $request,
            customer: $customer,
            cartCustomerId: $cartCustomerId,
            isAppRequest: $isAppRequest,
            orderAdditionalData: $orderAdditionalData,
        );

        if ($isAppRequest) {
            return response()->json([
                'redirect_link' => $redirectLink,
                'new_user' => isset($orderAdditionalData['new_customer_info']) && $orderAdditionalData['new_customer_info'] != null ? 1 : 0,
            ], 200);
        }

        return redirect($redirectLink);
    }

    public function addFund(AddFundRequest $request): JsonResponse|RedirectResponse
    {
        $isAppRequest = $this->isAppRequest(request: $request);

        if (getWebConfig(name: 'add_funds_to_wallet') != 1) {
            if ($isAppRequest) {
                return response()->json(['message' => 'Add funds to wallet is deactivated'], 403);
            }

            ToastMagic::error(translate('Add funds to wallet is deactivated'));

            return back();
        }

        $paymentAmount = $request['amount'];
        if (getWebConfig(name: 'currency_model') == self::MULTI_CURRENCY) {
            $currentCurrency = $request['current_currency_code'] ?? session('currency_code');
            $currencyCode = $this->resolvePaymentGatewayCurrencyCode(paymentMethod: $request['payment_method'], currentCurrency: $currentCurrency);
            $paymentAmount = usdToAnotherCurrencyConverter(currencyCode: $currencyCode, amount: Convert::usdPaymentModule($request['amount'], $currentCurrency));
        } else {
            $currencyCode = $this->getDefaultCurrencyCode();
            if ($currencyCode === null) {
                return $this->respondDefaultCurrencyMissing(isAppRequest: $isAppRequest);
            }
            $currentCurrency = $currencyCode;
        }

        $minimumAddFundAmount = getWebConfig(name: 'minimum_add_fund_amount') ?? 0;
        $maximumAddFundAmount = getWebConfig(name: 'maximum_add_fund_amount') ?? 0;
        $requestedUsdAmount = Convert::usdPaymentModule($request['amount'], $currentCurrency);

        if (! ($requestedUsdAmount >= Convert::usdPaymentModule($minimumAddFundAmount, 'USD')) || ! ($requestedUsdAmount <= Convert::usdPaymentModule($maximumAddFundAmount, 'USD'))) {
            if ($isAppRequest) {
                return response()->json([
                    'minimum_amount' => $minimumAddFundAmount ?? 0,
                    'maximum_amount' => $maximumAddFundAmount ?? 1000,
                ], 202);
            }

            ToastMagic::error(translate('The amount needs to be between').' '.webCurrencyConverter($minimumAddFundAmount).' - '.webCurrencyConverter($maximumAddFundAmount));

            return back();
        }

        $customer = auth('customer')->user();
        if ($customer === null) {
            if ($isAppRequest) {
                return response()->json(['message' => translate('Please login your account')], 403);
            }

            ToastMagic::error(translate('Please login your account'));

            return back();
        }

        $additionalData = [
            'business_name' => $this->businessSettingService->findBy(params: ['type' => self::COMPANY_NAME_SETTING])?->value,
            'business_logo' => getWebConfig('company_web_logo')['path'],
            'payment_mode' => $request->has('payment_platform') ? $request['payment_platform'] : self::PAYMENT_PLATFORM_WEB,
        ];

        if ($isAppRequest) {
            $additionalData['customer_id'] = $customer->id;
            $additionalData['payment_request_from'] = $request['payment_request_from'];
        }

        $redirectLink = $this->generate_link(
            new Payer($customer->f_name.' '.$customer->l_name, $customer['email'], $customer->phone, ''),
            new PaymentInfo(
                success_hook: self::ADD_FUND_SUCCESS_HOOK,
                failure_hook: self::ADD_FUND_FAILURE_HOOK,
                currency_code: $currencyCode,
                payment_method: $request['payment_method'],
                payment_platform: $request['payment_platform'],
                payer_id: $customer->id,
                receiver_id: self::RECEIVER_ID,
                additional_data: $additionalData,
                payment_amount: $paymentAmount,
                external_redirect_link: $request['payment_platform'] == self::PAYMENT_PLATFORM_WEB ? $request['external_redirect_link'] : null,
                attribute: self::ADD_FUND_ATTRIBUTE,
                attribute_id: idate('U'),
            ),
            new Receiver(self::RECEIVER_NAME, self::RECEIVER_IMAGE),
        );

        if ($isAppRequest) {
            return response()->json(['redirect_link' => $redirectLink], 200);
        }

        return redirect($redirectLink);
    }

    public function payDueAmount(DuePaymentRequest $request): JsonResponse|RedirectResponse
    {
        $customer = auth('customer')->user();
        $order = $customer === null ? null : $this->orderService->findBy(
            params: ['id' => $request['order_id'], 'customer_id' => $customer->id, 'is_guest' => '0'],
            relations: ['latestEditHistory'],
        );

        if ($order === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        if ($request['payment_method'] === self::PAYMENT_METHOD_WALLET) {
            return $this->payDueAmountFromWallet(order: $order, customer: $customer);
        }

        $orderEditHistory = $this->orderEditHistoryService->findLatestByOrder(orderId: $order->id);

        if ($request['payment_method'] === self::PAYMENT_METHOD_OFFLINE) {
            if ($orderEditHistory === null) {
                ToastMagic::error(translate('Not found'));

                return back();
            }

            $this->orderEditHistoryService->update(id: $orderEditHistory->id, data: [
                'order_due_payment_method' => self::PAYMENT_METHOD_OFFLINE,
                'order_due_payment_info' => $this->getOfflineDuePaymentInfo(request: $request),
            ]);
            $this->dispatchDuePaymentNotification(order: $order);
            ToastMagic::success(translate('Payment successful'));

            return back();
        }

        if ($request['payment_method'] === self::PAYMENT_METHOD_CASH_ON_DELIVERY) {
            return $this->payDueAmountOnDelivery(request: $request, order: $order, orderEditHistory: $orderEditHistory);
        }

        return $this->payDueAmountDigitally(request: $request, order: $order, customer: $customer);
    }

    private function payDueAmountFromWallet(Model $order, Authenticatable $customer): RedirectResponse
    {
        if (getWebConfig('wallet_status') != 1) {
            ToastMagic::error(translate('Wallet is deactivated'));

            return back();
        }

        if (! $this->orderService->canPayEditDueFromWallet(order: $order, customer: $customer)) {
            ToastMagic::error(translate('Insufficient Wallet Balance'));

            return back();
        }

        if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) == 1) {
            $this->debitCustomerWallet(customerId: $customer->id, amount: (float) $order['edit_due_amount']);
        }

        $this->adminWalletService->incrementInHousePendingAmount(amount: $order['edit_due_amount']);
        $this->orderEditHistoryService->markDuePaymentPaid(id: $order?->latestEditHistory?->id, paymentMethod: OrderEditHistoryService::DUE_PAYMENT_METHOD_WALLET);
        $this->orderService->settleEditDueAmount(order: $order);
        $this->dispatchDuePaymentNotification(order: $order);
        ToastMagic::success(translate('Payment successful'));

        return back();
    }

    private function debitCustomerWallet(int $customerId, float $amount): void
    {
        $walletCustomer = $this->customerService->find(id: $customerId);

        DB::transaction(function () use ($walletCustomer, $amount): void {
            $walletTransaction = $this->walletTransactionService->createWalletEntry(
                customerId: $walletCustomer->id,
                currentBalance: (float) $walletCustomer->wallet_balance,
                amount: $amount,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_DUE_PAYMENT_FOR_ORDER,
                reference: self::WALLET_REFERENCE_REDUCE_AMOUNT,
                paymentMethod: self::PAYMENT_METHOD_WALLET,
            );

            if ($walletTransaction !== null) {
                $this->customerService->updateWalletBalance(
                    id: $walletCustomer->id,
                    walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
                );
            }
        });
    }

    private function payDueAmountOnDelivery(DuePaymentRequest $request, Model $order, ?Model $orderEditHistory): RedirectResponse
    {
        if ($orderEditHistory === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        if ($request->filled('bring_change_amount_input')) {
            if (getWebConfig('currency_model') === self::MULTI_CURRENCY) {
                $bringChangeCurrency = $this->resolvePaymentGatewayCurrencyCode(
                    paymentMethod: self::PAYMENT_METHOD_CASH_ON_DELIVERY,
                    currentCurrency: $request['current_currency_code'] ?? session('currency_code'),
                );
            } else {
                $bringChangeCurrency = $this->getDefaultCurrencyCode();
                if ($bringChangeCurrency === null) {
                    return $this->respondDefaultCurrencyMissing(isAppRequest: false);
                }
            }

            $this->orderService->update(id: $order->id, data: [
                'bring_change_amount' => $request['bring_change_amount_input'],
                'bring_change_amount_currency' => $bringChangeCurrency,
            ]);
        }

        $this->orderEditHistoryService->update(id: $orderEditHistory->id, data: ['order_due_payment_method' => self::PAYMENT_METHOD_CASH_ON_DELIVERY]);
        $this->dispatchDuePaymentNotification(order: $order);
        ToastMagic::success(translate('Payment method updated'));

        return back();
    }

    private function payDueAmountDigitally(DuePaymentRequest $request, Model $order, Authenticatable $customer): JsonResponse|RedirectResponse
    {
        if (getWebConfig('currency_model') === self::MULTI_CURRENCY) {
            $currencyCode = $this->resolvePaymentGatewayCurrencyCode(
                paymentMethod: $request['payment_method'],
                currentCurrency: $request['current_currency_code'] ?? session('currency_code'),
            );
            $paymentAmount = usdToAnotherCurrencyConverter(currencyCode: $currencyCode, amount: $order['edit_due_amount']);
        } else {
            $currencyCode = $this->getDefaultCurrencyCode();
            if ($currencyCode === null) {
                return $this->respondDefaultCurrencyMissing(isAppRequest: false);
            }
            $paymentAmount = $order['edit_due_amount'];
        }

        $additionalData = [
            'business_name' => getWebConfig(name: 'company_name'),
            'business_logo' => getWebConfig('company_web_logo')['path'],
            'payment_mode' => $request['payment_platform'] ?? self::PAYMENT_PLATFORM_WEB,
            'payment_method' => $request['payment_method'],
            'order_id' => $order['id'],
            'order_due_payment_note' => $request['order_due_payment_note'] ?? '',
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
        ];
        if ($request['payment_request_from'] === self::APP_PAYMENT_REQUEST) {
            $additionalData['payment_request_from'] = self::APP_PAYMENT_REQUEST;
        }

        try {
            $redirectLink = $this->generate_link(
                new Payer(name: "{$customer['f_name']} {$customer['l_name']}", email: $customer['email'], phone: $customer['phone'], address: ''),
                new PaymentInfo(
                    success_hook: self::DUE_PAYMENT_SUCCESS_HOOK,
                    failure_hook: self::DUE_PAYMENT_FAILURE_HOOK,
                    currency_code: $currencyCode,
                    payment_method: $request['payment_method'],
                    payment_platform: $request['payment_platform'],
                    payer_id: $customer->id,
                    receiver_id: self::RECEIVER_ID,
                    additional_data: $additionalData,
                    payment_amount: $paymentAmount,
                    external_redirect_link: $request['payment_platform'] === self::PAYMENT_PLATFORM_WEB ? route('web-payment-success') : null,
                    attribute: self::DUE_PAYMENT_ATTRIBUTE,
                    attribute_id: now()->timestamp,
                ),
                new Receiver(self::RECEIVER_NAME, self::RECEIVER_IMAGE),
            );
        } catch (Exception $exception) {
            ToastMagic::error($exception->getMessage());

            return back();
        }

        if ($redirectLink) {
            return $request['payment_request_from'] === self::APP_PAYMENT_REQUEST
                ? response()->json(['redirect_link' => $redirectLink])
                : redirect($redirectLink);
        }

        return back();
    }

    private function getOfflineDuePaymentInfo(DuePaymentRequest $request): array
    {
        $method = $this->offlinePaymentMethodService->findBy(params: [
            'id' => $request['method_id'],
            'status' => OfflinePaymentMethodService::STATUS_ACTIVE,
        ]);

        if ($method === null) {
            return [];
        }

        return [
            'method_id' => $request['method_id'],
            'method_name' => $method->method_name,
            'payment_note' => $request['payment_note'] ?? '',
        ] + $this->offlinePaymentMethodService->getCustomerInputValues(method: $method, input: $request->all());
    }

    private function dispatchDuePaymentNotification(Model $order): void
    {
        $this->orderService->dispatchEditDuePaymentNotification(
            order: $order,
            seller: $order['seller_is'] == OrderService::SELLER_IS_SELLER ? $this->vendorService->find(id: $order['seller_id']) : null,
        );
    }

    private function createCheckoutPaymentLink(Request $request, ?Authenticatable $customer, ?int $cartCustomerId, bool $isAppRequest, array $orderAdditionalData): string
    {
        $additionalData = [
            'business_name' => getWebConfig(name: 'company_name'),
            'business_logo' => getStorageImages(path: getWebConfig('company_web_logo'), type: 'shop'),
            'payment_mode' => $request->has('payment_platform') ? $request['payment_platform'] : self::PAYMENT_PLATFORM_WEB,
        ];

        $guestId = $request['is_guest'] ? $request['guest_id'] : (session('guest_id') ?? 0);
        $isGuestUser = $customer === null ? 1 : 0;
        $newCustomerId = null;
        $isGuestUserInOrder = $isGuestUser;

        if ($customer === null && session('newCustomerRegister')) {
            $additionalData['new_customer_info'] = session('newCustomerRegister') ?? null;
            $additionalData['customer_id'] = $guestId;
            $additionalData['address_id'] = session('newCustomerRegister')['address_id'] ?? null;
            $additionalData['billing_address_id'] = session('newCustomerRegister')['billing_address_id'] ?? null;
            $newCustomerId = $guestId;
            $isGuestUserInOrder = 0;
        } elseif ($customer === null && isset($orderAdditionalData['new_customer_info'])) {
            $additionalData['new_customer_info'] = $orderAdditionalData['new_customer_info'];
            $newCustomerId = $guestId;
        } elseif ($customer !== null) {
            $newCustomerId = 0;
            $isGuestUserInOrder = 0;
        }

        $additionalData['is_guest'] = $isGuestUser;
        if ($isAppRequest) {
            $additionalData['customer_id'] = $request['customer_id'];
            $additionalData['guest_id'] = $request['guest_id'];
            $additionalData['is_guest'] = $request['is_guest'];
            $additionalData['order_note'] = $request['order_note'];
            $additionalData['address_id'] = $request['address_id'];
            $additionalData['billing_address_id'] = $request['billing_address_id'];
            $additionalData['coupon_code'] = $request['coupon_code'];
            $additionalData['coupon_discount'] = $request['coupon_discount'];
            $additionalData['payment_request_from'] = $request['payment_request_from'];
        } else {
            $additionalData['customer_id'] = $customer !== null ? $customer->id : ($newCustomerId ?? $guestId);
            $additionalData['order_note'] = session('order_note') ?? null;
            $additionalData['address_id'] = session('address_id') ?? 0;
            $additionalData['billing_address_id'] = session('billing_address_id') ?? 0;
            $additionalData['coupon_code'] = session('coupon_code') ?? null;
            $additionalData['coupon_discount'] = session('coupon_discount') ?? 0;
            $additionalData['payment_request_from'] = $request['payment_mode'] ?? self::PAYMENT_PLATFORM_WEB;
        }
        $additionalData['new_customer_id'] = $newCustomerId;
        $additionalData['is_guest_in_order'] = $isGuestUserInOrder;

        $couponCode = $isAppRequest ? ($request['coupon_code'] ?? '') : (session()->has('coupon_code') ? session('coupon_code') : '');
        $paymentAmount = $this->cartService->getPayableAmount(vendorWiseCartList: $this->getOrderGenerateCartList(request: $request, customerId: $cartCustomerId, couponCode: $couponCode));

        $additionalData['guest_id'] = $additionalData['guest_id'] ?? $guestId;
        $additionalData[OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT] = $paymentAmount;
        $additionalData[OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS] = $this->cartService->getCartGroupIds(
            customerId: $cartCustomerId,
            guestId: $cartCustomerId === null ? $guestId : 0,
            type: CartService::TYPE_CHECKED,
        );

        if ($customer === null) {
            $payer = $this->getGuestPayer(customerId: $request['customer_id']);
        } else {
            $payer = new Payer($customer['f_name'].' '.$customer['l_name'], $customer['email'], $customer['phone'], '');
            if (empty($customer['phone'])) {
                ToastMagic::error(translate('Please update your phone number'));

                return route('checkout-payment');
            }
        }

        if (getWebConfig(name: 'currency_model') == self::MULTI_CURRENCY) {
            $currencyCode = $this->resolvePaymentGatewayCurrencyCode(
                paymentMethod: $request['payment_method'],
                currentCurrency: $request['current_currency_code'] ?? session('currency_code'),
            );
            $paymentAmount = usdToAnotherCurrencyConverter(currencyCode: $currencyCode, amount: $paymentAmount);
        } else {
            $currencyCode = $this->getDefaultCurrencyCode();
            if ($currencyCode === null) {
                ToastMagic::error(translate('Something went wrong'));

                return route('checkout-payment');
            }
        }

        return $this->generate_link(
            $payer,
            new PaymentInfo(
                success_hook: self::CHECKOUT_SUCCESS_HOOK,
                failure_hook: self::CHECKOUT_FAILURE_HOOK,
                currency_code: $currencyCode,
                payment_method: $request['payment_method'],
                payment_platform: $request['payment_platform'],
                payer_id: $customer === null ? $request['customer_id'] : $customer['id'],
                receiver_id: self::RECEIVER_ID,
                additional_data: $additionalData,
                payment_amount: $paymentAmount,
                external_redirect_link: $request['payment_platform'] == self::PAYMENT_PLATFORM_WEB ? $request['external_redirect_link'] : null,
                attribute: self::CHECKOUT_ATTRIBUTE,
                attribute_id: idate('U'),
            ),
            new Receiver(self::RECEIVER_NAME, self::RECEIVER_IMAGE),
        );
    }

    private function getGuestPayer(mixed $customerId): Payer
    {
        $address = $this->shippingAddressService->getList(
            dataLimit: 1,
            filters: ['customer_id' => $customerId, 'is_guest' => 1],
            sortBy: ShippingAddressService::SORT_LATEST,
        )->first();

        if ($address === null) {
            return new Payer(self::GUEST_PAYER_NAME, '', '', '');
        }

        return new Payer($address->contact_person_name, $address->email, $address->phone, '');
    }

    /** @return array{status: int, data?: array<string, mixed>}|null */
    private function getAppCheckoutRegistration(Request $request): ?array
    {
        $registration = null;

        $shippingAddress = $request['address_id']
            ? $this->shippingAddressService->find(id: $request['address_id'], ownerId: (string) $request['guest_id'], isGuest: true)
            : null;
        if ($request->has('address_id') && $request['address_id'] && $shippingAddress) {
            if ($this->customerService->existsByEmailOrPhone(email: $shippingAddress['email'], phone: $shippingAddress['phone'])) {
                return ['status' => 0];
            }

            $registration = [
                'status' => 1,
                'data' => $this->buildAppCheckoutRegistration(
                    request: $request,
                    address: $shippingAddress,
                    shippingId: $request['address_id'],
                    billingId: $request->has('billing_address_id') && $request['billing_address_id'] ? $request['billing_address_id'] : null,
                ),
            ];
        }

        $billingAddress = $request['billing_address_id']
            ? $this->shippingAddressService->find(id: $request['billing_address_id'], ownerId: (string) $request['guest_id'], isGuest: true)
            : null;
        if ($request['address_id'] == null && $request->has('billing_address_id') && $request['billing_address_id'] && $billingAddress) {
            if ($this->customerService->existsByEmailOrPhone(email: $billingAddress['email'], phone: $billingAddress['phone'])) {
                return ['status' => 0];
            }

            $registration = [
                'status' => 1,
                'data' => $this->buildAppCheckoutRegistration(
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
    private function buildAppCheckoutRegistration(Request $request, Model $address, mixed $shippingId, mixed $billingId): array
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

    private function isAppRequest(Request $request): bool
    {
        return in_array($request['payment_request_from'], [self::APP_PAYMENT_REQUEST]);
    }

    private function resolveCartCustomerId(Request $request, bool $isAppRequest): ?int
    {
        if (auth('customer')->check()) {
            return auth('customer')->id();
        }

        if ($isAppRequest) {
            return $request['is_guest'] ? null : (int) $request['customer_id'];
        }

        if (session()->has('customer_id') && ! session('is_guest')) {
            return $this->customerService->find(id: session('customer_id'))?->id;
        }

        return null;
    }

    private function getCheckedCartItemsWithProduct(Request $request, ?int $customerId): Collection
    {
        return $this->cartService->getList(
            dataLimit: 'all',
            relations: ['product'],
            filters: [
                'cart_group_id' => $this->cartService->getCartGroupIds(
                    customerId: $customerId,
                    guestId: session('guest_id') ?? $request['guest_id'] ?? 0,
                    type: CartService::TYPE_CHECKED,
                ),
                'is_checked' => 1,
                CartService::FILTER_ACTIVE_PRODUCT => true,
            ],
        );
    }

    private function resolvePaymentGatewayCurrencyCode(?string $paymentMethod, mixed $currentCurrency): string
    {
        return $this->currencyService->resolvePaymentGatewayCurrencyCode(
            supportedCurrencies: $this->getPaymentGatewaySupportedCurrencies(key: $paymentMethod),
            currentCurrency: $currentCurrency === null ? null : (string) $currentCurrency,
            gatewayKey: $paymentMethod,
        );
    }

    private function getDefaultCurrencyCode(): ?string
    {
        return $this->currencyService->find(id: getWebConfig(name: 'system_default_currency'))?->code;
    }

    private function respondDefaultCurrencyMissing(bool $isAppRequest): JsonResponse|RedirectResponse
    {
        if ($isAppRequest) {
            return response()->json(['message' => translate('Something went wrong')], 403);
        }

        ToastMagic::error(translate('Something went wrong'));

        return back();
    }

    /** @return array{status: int, physical_product_view: bool, message: array<int, string>, errorType?: string, redirect?: string} */
    private function getCheckoutBlockerResponse(Request $request, ?int $customerId, bool $isAppRequest): array
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
                fn (array $blocker): string => $this->checkoutBlockerMessage(blocker: $blocker, isAppRequest: $isAppRequest),
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

    /** @return array<int, array{code: int, message: string}> */
    private function formatAppBlockerErrors(array $messages): array
    {
        $errors = [];
        foreach ($messages as $index => $message) {
            $errors[] = ['code' => $index, 'message' => $message];
        }

        return $errors;
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
    private function getOrderGenerateCartList(Request $request, ?int $customerId, mixed $couponCode): array
    {
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
}
