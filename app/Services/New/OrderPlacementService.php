<?php

namespace App\Services\New;

use App\Events\OrderedCartClearingEvent;
use App\Events\VendorOrderPlacedEvent;
use App\Events\VendorOrderPlacingEvent;
use App\Events\VendorOrderTransactionEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderPlacementService extends OrderService
{
    public const CHECKOUT_SESSION_KEYS = ['guest_id', 'coupon_code', 'address_id', 'billing_address_id', 'order_note'];

    public const PAYMENT_MODE_WEB = 'web';

    public const LOCK_SECONDS = 30;

    public const PLACED_ORDER_COUPON_SESSION_KEYS = ['coupon_code', 'coupon_type', 'coupon_bearer', 'coupon_discount', 'coupon_seller_id'];

    private const LOCK_KEY_PREFIX = 'retail_order_placement';

    private const MAIL_USER_TYPE_CUSTOMER = 'customer';

    private const MAIL_USER_TYPE_ADMIN = 'admin';

    private const MAIL_USER_TYPE_VENDOR = 'vendor';

    private const MAIL_TEMPLATE_ORDER_PLACE = 'order-place';

    private const MAIL_TEMPLATE_ORDER_RECEIVED = 'order-received';

    private const MAIL_ACTIVE_STATUS = 1;

    private const MAIL_INACTIVE_STATUS = 0;

    /** @param  array<int, int|string>  $cartGroupIds */
    public function getPlacementLockKey(int|string $ownerId, array $cartGroupIds): string
    {
        $sortedCartGroupIds = array_map('strval', $cartGroupIds);
        sort($sortedCartGroupIds);

        return self::LOCK_KEY_PREFIX.':'.$ownerId.':'.sha1(implode(',', $sortedCartGroupIds));
    }

    public function isTransactionPlaced(string $transactionRef): bool
    {
        return $transactionRef !== '' && $this->count(filters: ['transaction_ref' => $transactionRef]) > 0;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $vendorWiseCartList
     * @param  array{id: int|string|null, is_guest: int}  $orderCustomer
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $loyaltyPointSettings
     * @return array{order_ids: array<int, int>, order_group_id: string, forget_session_keys: array<int, string>}
     */
    public function placeOrders(
        array $vendorWiseCartList,
        array $orderCustomer,
        ?Model $customer,
        array $orderData,
        array $loyaltyPointSettings,
        ?Model $coupon,
        ?Model $shippingAddress,
        ?Model $billingAddress,
        ?Model $inHouseAdmin,
    ): array {
        $vendorWiseCartList = array_values($vendorWiseCartList);

        return DB::transaction(function () use ($vendorWiseCartList, $orderCustomer, $customer, $orderData, $loyaltyPointSettings, $coupon, $shippingAddress, $billingAddress, $inHouseAdmin): array {
            $orderGroupId = $this->generateUniqueReferenceId();
            $orderIds = [];
            $notifications = [];
            $mails = [];

            foreach ($vendorWiseCartList as $vendorCart) {
                $cartShippingAddress = $this->getVendorCartAddress(address: $shippingAddress, addressId: $vendorCart['shipping_address_id']);
                $cartBillingAddress = $this->getVendorCartAddress(address: $billingAddress, addressId: $vendorCart['billing_address_id']);

                $order = $this->placeVendorOrder(
                    vendorCart: $vendorCart,
                    orderCustomer: $orderCustomer,
                    orderGroupId: $orderGroupId,
                    orderData: $orderData,
                    loyaltyPointSettings: $loyaltyPointSettings,
                    coupon: empty($vendorCart['coupon_code']) ? null : $coupon,
                    shippingAddress: $cartShippingAddress,
                    billingAddress: $cartBillingAddress,
                );

                $orderIds[] = (int) $order['id'];
                array_push($notifications, ...$this->getPlacedOrderNotifications(order: $order, seller: $order->seller, isCustomerNotified: $customer !== null));
                array_push($mails, ...$this->getPlacedOrderMails(
                    order: $order,
                    customer: $customer,
                    inHouseAdmin: $inHouseAdmin,
                    guestAddress: $cartShippingAddress ?? $cartBillingAddress,
                ));
            }

            $cartClearing = new OrderedCartClearingEvent(
                cartGroupIds: array_map(static fn (array $vendorCart): string => (string) $vendorCart['cart_group_id'], $vendorWiseCartList),
            );

            event($cartClearing);

            DB::afterCommit(fn () => $this->dispatchPlacedOrderEvents(notifications: $notifications, mails: $mails));

            return [
                'order_ids' => $orderIds,
                'order_group_id' => $orderGroupId,
                'forget_session_keys' => [...$cartClearing->forgetSessionKeys, ...self::PLACED_ORDER_COUPON_SESSION_KEYS],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $vendorCart
     * @param  array{id: int|string|null, is_guest: int}  $orderCustomer
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $loyaltyPointSettings
     */
    private function placeVendorOrder(
        array $vendorCart,
        array $orderCustomer,
        string $orderGroupId,
        array $orderData,
        array $loyaltyPointSettings,
        ?Model $coupon,
        ?Model $shippingAddress,
        ?Model $billingAddress,
    ): Model {
        $orderId = $this->generateNewOrderId(latestOrderDetailOrderId: $this->getLatestOrderDetailOrderId());

        event(new VendorOrderPlacingEvent(orderId: $orderId, vendorCart: $vendorCart));

        $orderColumns = $this->createPlacedOrder(
            orderId: $orderId,
            orderGroupId: $orderGroupId,
            customerId: $orderCustomer['id'],
            isGuest: $orderCustomer['is_guest'],
            vendorCart: $vendorCart,
            orderData: $orderData,
            adminCommission: (float) $vendorCart['admin_commission'],
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
        );

        if ($orderData['payment_method'] == self::PAYMENT_METHOD_OFFLINE) {
            $this->createOfflinePaymentRecord(orderId: $orderId, paymentInfo: $orderData['offline_payment_info']);
        }

        event(new VendorOrderPlacedEvent(
            orderId: $orderId,
            vendorCart: $vendorCart,
            customerId: $orderCustomer['id'],
            initialStatus: $orderData['payment_status'] == self::PAYMENT_STATUS_PAID ? self::STATUS_CONFIRMED : self::STATUS_PENDING,
            coupon: $coupon,
            loyaltyPointSettings: $loyaltyPointSettings,
        ));

        $order = $this->find(id: $orderId, relations: ['customer', 'seller.shop', 'details']);

        event(new VendorOrderTransactionEvent(
            order: $order,
            adminCommission: (float) $orderColumns['admin_commission'],
            paymentMethod: $orderColumns['payment_method'],
            transactionId: $this->generateUniqueReferenceId(),
        ));

        return $order;
    }

    /** @return array<int, array{email: ?string, data: array<string, mixed>}> */
    public function getPlacedOrderMails(Model $order, ?Model $customer, ?Model $inHouseAdmin, ?Model $guestAddress): array
    {
        if (! $this->isMailActive()) {
            return [];
        }

        $isInHouseOrder = $order['seller_is'] == self::SELLER_IS_ADMIN;
        $vendor = $isInHouseOrder ? $this->getInHouseOrderAdmin(order: $order, inHouseAdmin: $inHouseAdmin) : $order->seller;
        $shopName = $isInHouseOrder ? getInHouseShopConfig(key: 'name') : ($vendor?->shop?->name ?? getWebConfig(name: BusinessSettingService::COMPANY_NAME));

        return [
            [
                'email' => $customer === null ? $guestAddress?->email : $customer['email'],
                'data' => [
                    'subject' => translate('Order Placed'),
                    'title' => translate('Order Placed'),
                    'userName' => $customer === null ? $guestAddress?->contact_person_name : $customer['f_name'],
                    'userType' => self::MAIL_USER_TYPE_CUSTOMER,
                    'templateName' => self::MAIL_TEMPLATE_ORDER_PLACE,
                    'order' => $order,
                    'orderId' => $order['id'],
                    'shopName' => $shopName,
                    'vendorName' => $shopName,
                    'shopId' => $vendor?->shop?->id ?? 0,
                    'invoiceOrderId' => $order['id'],
                ],
            ],
            [
                'email' => $vendor?->email,
                'data' => [
                    'subject' => translate('New order received'),
                    'title' => translate('New order received'),
                    'userType' => $isInHouseOrder ? self::MAIL_USER_TYPE_ADMIN : self::MAIL_USER_TYPE_VENDOR,
                    'templateName' => self::MAIL_TEMPLATE_ORDER_RECEIVED,
                    'order' => $order,
                    'orderId' => $order['id'],
                    'vendorName' => $vendor?->f_name,
                    'adminName' => $vendor?->name,
                ],
            ],
        ];
    }

    protected function isMailActive(): bool
    {
        $mailConfig = getWebConfig(name: BusinessSettingService::MAIL_CONFIG);

        if (($mailConfig['status'] ?? self::MAIL_INACTIVE_STATUS) == self::MAIL_INACTIVE_STATUS) {
            $mailConfig = getWebConfig(name: BusinessSettingService::MAIL_CONFIG_SENDGRID);
        }

        return ($mailConfig['status'] ?? self::MAIL_INACTIVE_STATUS) == self::MAIL_ACTIVE_STATUS;
    }

    private function getInHouseOrderAdmin(Model $order, ?Model $inHouseAdmin): ?Model
    {
        return $inHouseAdmin !== null && (int) $inHouseAdmin->id === (int) $order['seller_id'] ? $inHouseAdmin : null;
    }

    private function getLatestOrderDetailOrderId(): ?int
    {
        return $this->model->details()->getRelated()->newQuery()->orderBy('order_id', 'desc')->first()?->order_id;
    }

    private function getVendorCartAddress(?Model $address, mixed $addressId): ?Model
    {
        return $address !== null && $addressId !== null && (int) $address->id === (int) $addressId ? $address : null;
    }
}
