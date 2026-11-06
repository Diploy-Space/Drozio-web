<?php

namespace App\Listeners;

use App\Events\CheckoutOrdersPlacingEvent;
use App\Events\OrderPaymentReceivedEvent;
use App\Services\New\CustomerService;
use App\Services\New\OrderPlacementService;
use App\Services\New\OrderService;
use App\Services\New\ShippingAddressService;
use App\Traits\OrderPlacementSessionTrait;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentReceivedListener
{
    use OrderPlacementSessionTrait;

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly ShippingAddressService $shippingAddressService,
    ) {}

    public function handle(OrderPaymentReceivedEvent $event): void
    {
        $paymentRequest = $event->paymentRequest;

        if ((int) $paymentRequest['is_paid'] !== 1) {
            return;
        }

        $paymentPayload = json_decode((string) $paymentRequest['additional_data'], true);
        $paymentPayload = is_array($paymentPayload) ? $paymentPayload : [];

        $newCustomer = $this->registerPaymentCustomer(paymentPayload: $paymentPayload);

        if ($newCustomer !== null) {
            session()->put('newRegisterCustomerInfo', $newCustomer);
            $this->moveGuestPaymentAddressesToCustomer(paymentPayload: $paymentPayload, customer: $newCustomer);
        }

        session()->put('payment_mode', $paymentPayload['payment_mode'] ?? OrderPlacementService::PAYMENT_MODE_WEB);

        $checkoutSession = $this->getCheckoutSession();
        $customer = array_key_exists('is_guest', $paymentPayload) && $paymentPayload['is_guest'] == 0
            ? $this->customerService->find(id: $paymentPayload['customer_id'])
            : null;
        $requestGuestId = ($paymentPayload['is_guest_in_order'] ?? 0) ? $paymentPayload['customer_id'] : null;

        $placing = new CheckoutOrdersPlacingEvent(
            customer: $customer,
            requestGuestId: $requestGuestId,
            orderCustomer: $this->resolvePaidOrderCustomer(
                paymentPayload: $paymentPayload,
                customer: $customer,
                newCustomer: $newCustomer,
                requestGuestId: $requestGuestId,
                checkoutSession: $checkoutSession,
            ),
            orderData: [
                'order_status' => OrderService::STATUS_CONFIRMED,
                'payment_method' => (string) $paymentRequest['payment_method'],
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                'transaction_ref' => (string) $paymentRequest['transaction_id'],
                'coupon_code' => $paymentPayload['coupon_code'] ?? ($checkoutSession['coupon_code'] ?? ''),
                'address_id' => $paymentPayload['address_id'] ?? $checkoutSession['address_id'],
                'billing_address_id' => $paymentPayload['billing_address_id'] ?? $checkoutSession['billing_address_id'],
                'order_note' => $paymentPayload['order_note'] ?? null,
            ],
            checkoutSession: $checkoutSession,
            quotedAmount: isset($paymentPayload[OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT]) ? (float) $paymentPayload[OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT] : null,
            quotedCartGroupIds: isset($paymentPayload[OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS]) ? (array) $paymentPayload[OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS] : null,
        );

        event($placing);

        $this->applyPlacementSession(placement: $placing->placement);
    }

    /** @param  array<string, mixed>  $paymentPayload */
    private function registerPaymentCustomer(array $paymentPayload): ?Model
    {
        $registration = $paymentPayload['new_customer_info'] ?? null;

        if (empty($registration)) {
            return null;
        }

        return $this->customerService->findByEmailOrPhone(email: $registration['email'], phone: $registration['phone'])
            ?? $this->customerService->createFromCheckoutRegistration(registration: $registration);
    }

    /** @param  array<string, mixed>  $paymentPayload */
    private function moveGuestPaymentAddressesToCustomer(array $paymentPayload, Model $customer): void
    {
        if (empty($paymentPayload['is_guest'])) {
            return;
        }

        $this->shippingAddressService->moveGuestAddressesToCustomer(
            guestId: $paymentPayload['customer_id'],
            customerId: $customer['id'],
            addressIds: [$paymentPayload['address_id'] ?? null, $paymentPayload['billing_address_id'] ?? null],
        );
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     * @param  array<string, mixed>  $checkoutSession
     * @return array{id: int|string|null, is_guest: int}
     */
    private function resolvePaidOrderCustomer(array $paymentPayload, ?Model $customer, ?Model $newCustomer, int|string|null $requestGuestId, array $checkoutSession): array
    {
        $isGuestInOrder = (int) ($paymentPayload['is_guest_in_order'] ?? 0);

        if ($customer !== null) {
            return ['id' => $customer['id'], 'is_guest' => $isGuestInOrder];
        }

        if ($newCustomer !== null) {
            return ['id' => $newCustomer['id'], 'is_guest' => 0];
        }

        $newCustomerId = $paymentPayload['new_customer_id'] ?? null;

        if ($newCustomerId !== null) {
            return ['id' => $newCustomerId, 'is_guest' => $newCustomerId ? 0 : 1];
        }

        return [
            'id' => $checkoutSession['guest_id'] ? $checkoutSession['guest_id'] : ($isGuestInOrder ? $requestGuestId : 0),
            'is_guest' => $isGuestInOrder,
        ];
    }
}
