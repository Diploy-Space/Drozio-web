<?php

namespace App\Services\New;

use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\Currency;
use App\Models\WalletTransaction;
use App\Services\BaseService;
use App\Traits\Payment;
use App\Traits\PaymentGatewayTrait;
use App\Utils\Convert;
use Illuminate\Database\Eloquent\Model;

class WalletFundService extends BaseService
{
    use Payment, PaymentGatewayTrait;

    public const REASON_AMOUNT_OUT_OF_RANGE = 'amount-out-of-range';

    public const REASON_RESPONSE_KEYS = [
        self::REASON_AMOUNT_OUT_OF_RANGE => 'wallet_fund_amount_out_of_range',
    ];

    private const ATTRIBUTE = 'add_funds_to_wallet';

    private const SUCCESS_HOOK = 'add_fund_to_wallet_success';

    private const FAILURE_HOOK = 'add_fund_to_wallet_fail';

    private const RECEIVER_ID = '100';

    private const PLATFORM_WEB = 'web';

    private const MULTI_CURRENCY = 'multi_currency';

    private const PAYMENT_REQUEST_FROM_APP = 'app';

    private const CHECKOUT_ATTRIBUTE = 'order';

    private const CHECKOUT_SUCCESS_HOOK = 'retail_payment_success';

    private const CHECKOUT_FAILURE_HOOK = 'retail_payment_fail';

    private const CHECKOUT_GUEST_PAYER_NAME = 'Contact person name';

    private const EDIT_DUE_ATTRIBUTE = 'customer_order_edit_pay_due_amount';

    private const EDIT_DUE_SUCCESS_HOOK = 'customer_order_edit_pay_due_amount_success';

    private const EDIT_DUE_FAILURE_HOOK = 'customer_order_edit_pay_due_amount_failed';

    private const EDIT_DUE_GUEST_PAYER_NAME = 'Guest User';

    private const EDIT_DUE_GUEST_PAYER_EMAIL = 'customer@example.com';

    private const EDIT_DUE_GUEST_PAYER_PHONE = '000';

    public function __construct(WalletTransaction $walletTransaction)
    {
        parent::__construct($walletTransaction);
    }

    /** @return array{minimum: float, maximum: float} */
    public function getAmountRange(): array
    {
        return [
            'minimum' => (float) (getWebConfig(name: 'minimum_add_fund_amount') ?? 0),
            'maximum' => (float) (getWebConfig(name: 'maximum_add_fund_amount') ?? 0),
        ];
    }

    public function getBlockingReason(float $amount, ?string $currentCurrencyCode): ?string
    {
        $range = $this->getAmountRange();
        $requested = Convert::usdPaymentModule($amount, $currentCurrencyCode);

        return $requested >= Convert::usdPaymentModule($range['minimum'], 'USD')
            && $requested <= Convert::usdPaymentModule($range['maximum'], 'USD')
            ? null
            : self::REASON_AMOUNT_OUT_OF_RANGE;
    }

    public function generatePaymentLink(
        Model $customer,
        float $amount,
        string $paymentMethod,
        string $paymentPlatform,
        ?string $currentCurrencyCode = null,
        ?string $externalRedirectLink = null,
    ): mixed {
        [$currencyCode, $payableAmount] = $this->resolveCurrency($amount, $paymentMethod, $currentCurrencyCode);

        $paymentInfo = new PaymentInfo(
            success_hook: self::SUCCESS_HOOK,
            failure_hook: self::FAILURE_HOOK,
            currency_code: $currencyCode,
            payment_method: $paymentMethod,
            payment_platform: $paymentPlatform,
            payer_id: $customer->id,
            receiver_id: self::RECEIVER_ID,
            additional_data: [
                'business_name' => getWebConfig(name: 'company_name'),
                'business_logo' => getWebConfig(name: 'company_web_logo')['path'] ?? null,
                'payment_mode' => $paymentPlatform,
                'customer_id' => $customer->id,
                'payment_request_from' => $paymentPlatform,
            ],
            payment_amount: $payableAmount,
            external_redirect_link: $paymentPlatform === self::PLATFORM_WEB ? $externalRedirectLink : null,
            attribute: self::ATTRIBUTE,
            attribute_id: idate('U'),
        );

        return $this->generate_link(
            new Payer(trim($customer->f_name.' '.$customer->l_name), $customer->email, $customer->phone, ''),
            $paymentInfo,
            new Receiver('receiver_name', 'example.png'),
        );
    }

    public function generateCheckoutPaymentLink(
        array $data,
        ?Model $customer,
        ?Model $guestPayerAddress,
        mixed $orderAmount,
        ?string $defaultCurrencyCode,
        ?array $newCustomerInfo = null,
    ): mixed {
        $additionalData = [
            'business_name' => getWebConfig(name: 'company_name'),
            'business_logo' => getStorageImages(path: getWebConfig(name: 'company_web_logo'), type: 'shop'),
            'payment_mode' => array_key_exists('payment_platform', $data) ? $data['payment_platform'] : self::PLATFORM_WEB,
        ];

        $guestId = ! empty($data['is_guest']) ? ($data['guest_id'] ?? null) : 0;
        $newCustomerId = null;
        $isGuestInOrder = $customer === null ? 1 : 0;

        if ($customer === null && $newCustomerInfo !== null) {
            $additionalData['new_customer_info'] = $newCustomerInfo;
            $newCustomerId = $guestId;
        } elseif ($customer !== null) {
            $newCustomerId = 0;
        }

        $additionalData['is_guest'] = $data['is_guest'] ?? null;
        $additionalData['customer_id'] = $data['customer_id'] ?? null;
        $additionalData['guest_id'] = $data['guest_id'] ?? null;
        $additionalData['order_note'] = $data['order_note'] ?? null;
        $additionalData['address_id'] = $data['address_id'] ?? null;
        $additionalData['billing_address_id'] = $data['billing_address_id'] ?? null;
        $additionalData['coupon_code'] = $data['coupon_code'] ?? null;
        $additionalData['coupon_discount'] = $data['coupon_discount'] ?? null;
        $additionalData['payment_request_from'] = self::PAYMENT_REQUEST_FROM_APP;
        $additionalData['new_customer_id'] = $newCustomerId;
        $additionalData['is_guest_in_order'] = $isGuestInOrder;
        $additionalData[OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT] = (float) $orderAmount;
        $additionalData[OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS] = $data[OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS] ?? null;

        [$currencyCode, $paymentAmount] = $this->resolveGatewayCurrency(
            amount: $orderAmount,
            paymentMethod: $data['payment_method'] ?? null,
            currentCurrencyCode: $data['current_currency_code'] ?? null,
            defaultCurrencyCode: $defaultCurrencyCode,
        );

        return $this->generate_link(
            $this->checkoutPayer(customer: $customer, guestPayerAddress: $guestPayerAddress),
            new PaymentInfo(
                success_hook: self::CHECKOUT_SUCCESS_HOOK,
                failure_hook: self::CHECKOUT_FAILURE_HOOK,
                currency_code: $currencyCode,
                payment_method: $data['payment_method'] ?? null,
                payment_platform: $data['payment_platform'] ?? null,
                payer_id: $customer === null ? ($data['customer_id'] ?? null) : $customer['id'],
                receiver_id: self::RECEIVER_ID,
                additional_data: $additionalData,
                payment_amount: $paymentAmount,
                external_redirect_link: ($data['payment_platform'] ?? null) == self::PLATFORM_WEB ? ($data['external_redirect_link'] ?? null) : null,
                attribute: self::CHECKOUT_ATTRIBUTE,
                attribute_id: idate('U'),
            ),
            new Receiver('receiver_name', 'example.png'),
        );
    }

    public function generateEditDuePaymentLink(
        Model $order,
        array $data,
        ?Model $customer,
        int|string|null $guestId,
        ?string $defaultCurrencyCode,
    ): mixed {
        $customerId = $customer === null ? $guestId : $customer['id'];

        [$currencyCode, $paymentAmount] = $this->resolveGatewayCurrency(
            amount: $order['edit_due_amount'],
            paymentMethod: $data['payment_method'] ?? null,
            currentCurrencyCode: $data['current_currency_code'] ?? null,
            defaultCurrencyCode: $defaultCurrencyCode,
        );

        $additionalData = [
            'business_name' => getWebConfig(name: 'company_name'),
            'business_logo' => getWebConfig(name: 'company_web_logo')['path'] ?? null,
            'payment_mode' => $data['payment_platform'] ?? self::PLATFORM_WEB,
            'payment_method' => $data['payment_method'] ?? null,
            'order_id' => $order['id'],
            'order_due_payment_note' => $data['order_due_payment_note'] ?? '',
            'customer_id' => $customerId,
            'is_guest' => $customer === null ? 1 : 0,
            'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
        ];

        if (($data['payment_request_from'] ?? null) === self::PAYMENT_REQUEST_FROM_APP) {
            $additionalData['payment_request_from'] = self::PAYMENT_REQUEST_FROM_APP;
        }

        return $this->generate_link(
            $this->editDuePayer(order: $order, customer: $customer),
            new PaymentInfo(
                success_hook: self::EDIT_DUE_SUCCESS_HOOK,
                failure_hook: self::EDIT_DUE_FAILURE_HOOK,
                currency_code: $currencyCode,
                payment_method: $data['payment_method'] ?? null,
                payment_platform: $data['payment_platform'] ?? null,
                payer_id: $customerId,
                receiver_id: self::RECEIVER_ID,
                additional_data: $additionalData,
                payment_amount: $paymentAmount,
                external_redirect_link: ($data['payment_platform'] ?? null) === self::PLATFORM_WEB ? route('web-payment-success') : null,
                attribute: self::EDIT_DUE_ATTRIBUTE,
                attribute_id: now()->timestamp,
            ),
            new Receiver('receiver_name', 'example.png'),
        );
    }

    private function checkoutPayer(?Model $customer, ?Model $guestPayerAddress): Payer
    {
        if ($customer !== null) {
            return new Payer($customer['f_name'].' '.$customer['l_name'], $customer['email'], $customer['phone'], '');
        }

        return $guestPayerAddress === null
            ? new Payer(self::CHECKOUT_GUEST_PAYER_NAME, '', '', '')
            : new Payer($guestPayerAddress->contact_person_name, $guestPayerAddress->email, $guestPayerAddress->phone, '');
    }

    private function editDuePayer(Model $order, ?Model $customer): Payer
    {
        if ($customer !== null) {
            return new Payer(name: "{$customer['f_name']} {$customer['l_name']}", email: $customer['email'], phone: $customer['phone'], address: '');
        }

        $shippingAddress = (array) $order?->shipping_address_data;

        return new Payer(
            $shippingAddress['contact_person_name'] ?? self::EDIT_DUE_GUEST_PAYER_NAME,
            $shippingAddress['email'] ?? self::EDIT_DUE_GUEST_PAYER_EMAIL,
            $shippingAddress['phone'] ?? self::EDIT_DUE_GUEST_PAYER_PHONE,
            '',
        );
    }

    /** @return array{0: ?string, 1: mixed} */
    private function resolveGatewayCurrency(mixed $amount, ?string $paymentMethod, ?string $currentCurrencyCode, ?string $defaultCurrencyCode): array
    {
        if (getWebConfig(name: 'currency_model') !== self::MULTI_CURRENCY) {
            return [$defaultCurrencyCode, $amount];
        }

        $currencyCode = $this->getPaymentGatewayCurrencyCode(key: $paymentMethod, currentCurrency: $currentCurrencyCode);

        return [$currencyCode, usdToAnotherCurrencyConverter(currencyCode: $currencyCode, amount: $amount)];
    }

    /** @return array{0: ?string, 1: float} */
    private function resolveCurrency(float $amount, string $paymentMethod, ?string $currentCurrencyCode): array
    {
        if (getWebConfig(name: 'currency_model') !== 'multi_currency') {
            return [Currency::find(getWebConfig(name: 'system_default_currency'))?->code, $amount];
        }

        $currencyCode = $this->getPaymentGatewayCurrencyCode(key: $paymentMethod, currentCurrency: $currentCurrencyCode);

        return [
            $currencyCode,
            (float) usdToAnotherCurrencyConverter(
                currencyCode: $currencyCode,
                amount: Convert::usdPaymentModule($amount, $currentCurrencyCode),
            ),
        ];
    }
}
