<?php

namespace Tests\Feature\Api\Support;

use App\Models\DeliveryMan;
use App\Models\DeliverymanNotification;
use App\Models\GuestUser;
use App\Models\OfflinePaymentMethod;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Seller;
use App\Models\SellerWallet;
use App\Models\ShippingAddress;
use App\Models\SupportTicket;
use App\Models\SupportTicketConv;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves the rows the snapshot tests exercise from the imported clone.
 *
 * Nothing here creates fixtures: the harness characterizes the current API
 * against real data. When a lookup returns null the test skips itself with an
 * explanation instead of failing, so a thinner clone still runs green.
 */
final class ApiTestData
{
    public static function activePhysicalProduct(): ?Product
    {
        return Product::where(['status' => 1, 'product_type' => 'physical'])
            ->where('current_stock', '>', 5)
            ->orderBy('id')
            ->first();
    }

    /**
     * An active product carrying at least one published review, so the overall-rating
     * endpoint answers a non-zero figure. Rated against an unreviewed product it answers
     * `0`, which no non-empty guard can tell apart from a broken response.
     */
    public static function activeProductWithPublishedReview(): ?Product
    {
        return Product::where(['status' => 1])
            ->whereIn('id', Review::where(['status' => 1])->whereNotNull('product_id')->distinct()->pluck('product_id'))
            ->orderBy('id')
            ->first();
    }

    public static function customerWithDeliveredOrder(): ?User
    {
        $order = Order::whereHas('details', function ($query) {
            return $query->where(['delivery_status' => 'delivered']);
        })->where('is_guest', 0)->whereNotNull('customer_id')->orderBy('id')->first();

        return $order === null ? null : User::find($order->customer_id);
    }

    public static function deliveredOrderDetailOf(User $customer): ?OrderDetail
    {
        return OrderDetail::whereHas('order', function ($query) use ($customer) {
            return $query->where(['customer_id' => $customer->id]);
        })->where(['delivery_status' => 'delivered'])->orderBy('id')->first();
    }

    public static function customer(): ?User
    {
        return User::where('id', '>', 0)->orderBy('id')->first();
    }

    public static function customerWithSupportTicket(): ?User
    {
        $customerId = SupportTicket::whereNotNull('customer_id')->orderBy('id')->value('customer_id');

        return $customerId === null ? null : User::find($customerId);
    }

    public static function supportTicketOf(User $customer): ?SupportTicket
    {
        return SupportTicket::where(['customer_id' => $customer->id])->orderBy('id')->first();
    }

    public static function supportTicketWithConversation(): ?SupportTicket
    {
        return SupportTicket::whereIn('id', SupportTicketConv::distinct()->pluck('support_ticket_id'))
            ->orderBy('id')
            ->first();
    }

    /**
     * Guest rows carry a `guest_users` id in `customer_id`, so the address list is resolved
     * from the users table inwards to guarantee the id belongs to a real customer.
     */
    public static function customerWithShippingAddress(): ?User
    {
        return User::whereIn('id', ShippingAddress::where(['is_guest' => 0])->distinct()->pluck('customer_id'))
            ->orderBy('id')
            ->first();
    }

    public static function shippingAddressOf(User $customer): ?ShippingAddress
    {
        return ShippingAddress::where(['customer_id' => $customer->id, 'is_guest' => 0])->orderBy('id')->first();
    }

    /**
     * A customer whose account deletion is refused because orders are still in flight.
     */
    public static function customerWithOngoingOrder(): ?User
    {
        $customerId = Order::where(['is_guest' => 0])
            ->whereIn('order_status', ['out_for_delivery', 'processing', 'confirmed', 'pending'])
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->value('customer_id');

        return $customerId === null ? null : User::find($customerId);
    }

    public static function sellerWithOrders(): ?Seller
    {
        return Seller::where(['status' => 'approved'])
            ->whereNotNull('auth_token')
            ->whereHas('orders')
            ->orderBy('id')
            ->first();
    }

    /**
     * A seller the `seller_api_auth` middleware accepts — it reads the bearer
     * token and rejects anything 30 characters or shorter before the route runs.
     */
    public static function sellerWithApiToken(): ?Seller
    {
        return Seller::where(['status' => 'approved'])
            ->whereRaw('LENGTH(auth_token) > 30')
            ->orderBy('id')
            ->first();
    }

    /**
     * The `delivery_man_auth` middleware applies the same token-length rule, and
     * the notification list is only meaningful for a delivery man who has rows.
     */
    public static function deliveryManWithNotifications(): ?DeliveryMan
    {
        return DeliveryMan::whereRaw('LENGTH(auth_token) > 30')
            ->whereIn('id', DeliverymanNotification::distinct()->pluck('delivery_man_id'))
            ->orderBy('id')
            ->first();
    }

    public static function refundRequest(): ?RefundRequest
    {
        return RefundRequest::orderBy('id')->first();
    }

    public static function customerWithRefundRequest(): ?User
    {
        $customerId = RefundRequest::whereNotNull('customer_id')->orderBy('id')->value('customer_id');

        return $customerId === null ? null : User::find($customerId);
    }

    public static function refundedOrderDetailOf(User $customer): ?OrderDetail
    {
        $orderDetailsId = RefundRequest::where(['customer_id' => $customer->id])->orderBy('id')->value('order_details_id');

        return $orderDetailsId === null ? null : OrderDetail::find($orderDetailsId);
    }

    /**
     * Delivered order details of the customer that carry no refund request yet —
     * what `refund-store` needs, one row per call so neither call hits the row
     * the other already flagged.
     *
     * @return Collection<int, OrderDetail>
     */
    public static function refundableOrderDetailsOf(User $customer, int $count): Collection
    {
        return OrderDetail::whereHas('order', function ($query) use ($customer) {
            return $query->where(['customer_id' => $customer->id]);
        })->where(['delivery_status' => 'delivered', 'refund_request' => 0])
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /**
     * A seller the `seller_api_auth` middleware accepts whose own orders carry
     * refund requests — the seller refund endpoints filter on `seller_is`.
     */
    public static function sellerWithRefundRequests(): ?Seller
    {
        $sellerId = Order::whereIn('id', RefundRequest::distinct()->pluck('order_id'))
            ->where(['seller_is' => 'seller'])
            ->whereIn('seller_id', Seller::where(['status' => 'approved'])->whereRaw('LENGTH(auth_token) > 30')->pluck('id'))
            ->orderBy('seller_id')
            ->value('seller_id');

        return $sellerId === null ? null : Seller::find($sellerId);
    }

    public static function refundRequestOf(Seller $seller): ?RefundRequest
    {
        return self::sellerRefundRequests($seller)->orderBy('id')->first();
    }

    /**
     * Refund requests of the seller that `refund-status-update` will actually
     * transition: a refunded request, or one the admin last touched, takes the
     * 403 branch instead.
     *
     * @return Collection<int, RefundRequest>
     */
    public static function updatableRefundRequestsOf(Seller $seller, int $count): Collection
    {
        return self::sellerRefundRequests($seller)
            ->where('status', '!=', 'refunded')
            ->where(function ($query) {
                return $query->whereNull('change_by')->orWhere('change_by', '!=', 'admin');
            })
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    private static function sellerRefundRequests(Seller $seller): Builder
    {
        return RefundRequest::whereHas('order', function ($query) use ($seller) {
            return $query->where(['seller_is' => 'seller', 'seller_id' => $seller->id]);
        });
    }

    public static function orderIdOf(Seller $seller): ?int
    {
        return OrderDetail::where(['seller_id' => $seller->id])->orderByDesc('id')->value('order_id');
    }

    public static function productOf(Seller $seller): ?Product
    {
        return Product::where(['added_by' => 'seller', 'user_id' => $seller->id])->orderBy('id')->first();
    }

    public static function guestId(): int
    {
        return (int) (GuestUser::orderBy('id')->value('id') ?? 999999);
    }

    public static function customerWithWalletTransactions(): ?User
    {
        $customerId = WalletTransaction::whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->orderBy('user_id')
            ->value('user_id');

        return $customerId === null ? null : User::find($customerId);
    }

    /** A delivery man the `delivery_man_auth` middleware accepts — the same token-length rule. */
    public static function deliveryManWithApiToken(): ?DeliveryMan
    {
        return DeliveryMan::whereRaw('LENGTH(auth_token) > 30')->orderBy('id')->first();
    }

    public static function deliveryManWithWithdrawRequests(): ?DeliveryMan
    {
        return DeliveryMan::whereRaw('LENGTH(auth_token) > 30')
            ->whereIn('id', WithdrawRequest::whereNotNull('delivery_man_id')->distinct()->pluck('delivery_man_id'))
            ->orderBy('id')
            ->first();
    }

    public static function sellerWithDeliveryMen(): ?Seller
    {
        $sellerId = DeliveryMan::whereIn('seller_id', self::authenticatableSellers()->pluck('id'))
            ->orderBy('seller_id')
            ->value('seller_id');

        return $sellerId === null ? null : Seller::find($sellerId);
    }

    public static function deliveryManOf(Seller $seller): ?DeliveryMan
    {
        return DeliveryMan::where(['seller_id' => $seller->id])->orderBy('id')->first();
    }

    /**
     * Withdraw requests the seller's own delivery men raised — what
     * `seller/delivery-man/withdraw/*` reads. A delivery man attached to the admin
     * rather than to a seller files against `admin_id`, so a clone can hold none.
     *
     * @return Collection<int, WithdrawRequest>
     */
    public static function deliveryManWithdrawRequestsOf(Seller $seller, int $count): Collection
    {
        return WithdrawRequest::where(['seller_id' => $seller->id, 'approved' => 0])
            ->whereNotNull('delivery_man_id')
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /** A seller whose wallet can cover a withdraw request of `$minimumEarning`. */
    public static function sellerWithEarnings(float $minimumEarning): ?Seller
    {
        $sellerId = SellerWallet::where('total_earning', '>=', $minimumEarning)
            ->whereIn('seller_id', self::authenticatableSellers()->pluck('id'))
            ->orderByDesc('total_earning')
            ->value('seller_id');

        return $sellerId === null ? null : Seller::find($sellerId);
    }

    /**
     * The seller's own open withdraw requests — the rows `balance-withdraw-update`
     * and `close-withdraw-request` act on, one per call.
     *
     * @return Collection<int, WithdrawRequest>
     */
    public static function openWithdrawRequestsOf(Seller $seller, int $count): Collection
    {
        return WithdrawRequest::where(['seller_id' => $seller->id, 'approved' => 0])
            ->whereNull('delivery_man_id')
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    public static function vendorWithdrawalMethod(): ?WithdrawalMethod
    {
        return WithdrawalMethod::where(['is_active' => 1, 'vendor_status' => 1])->orderBy('id')->first();
    }

    /**
     * A registered customer whose wallet can settle `$minimumBalance` — the
     * `place-by-wallet` guard refuses anything the balance cannot cover, and a
     * parity run pays for the same cart twice out of the same wallet.
     */
    public static function customerWithWalletBalance(float $minimumBalance): ?User
    {
        return User::where('wallet_balance', '>=', $minimumBalance)
            ->orderByDesc('wallet_balance')
            ->orderBy('id')
            ->first();
    }

    /** An offline payment method `place-by-offline-payment` accepts. */
    public static function activeOfflinePaymentMethod(): ?OfflinePaymentMethod
    {
        return OfflinePaymentMethod::where(['status' => 1])->orderBy('id')->first();
    }

    /** A registered customer who has placed at least one non-guest order. */
    public static function customerWithOrder(): ?User
    {
        $customerId = Order::where(['is_guest' => 0])
            ->whereNotNull('customer_id')
            ->whereHas('details')
            ->orderBy('id')
            ->value('customer_id');

        return $customerId === null ? null : User::find($customerId);
    }

    public static function orderOf(User $customer): ?Order
    {
        return Order::where(['customer_id' => $customer->id, 'is_guest' => 0])
            ->whereHas('details')
            ->orderBy('id')
            ->first();
    }

    /**
     * Orders `order/cancel-order` accepts — cash on delivery and still pending.
     * The endpoint writes, so a parity run needs one order per call.
     *
     * @return Collection<int, Order>
     */
    public static function cancellableOrdersOf(User $customer, int $count): Collection
    {
        return Order::where([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'payment_method' => 'cash_on_delivery',
            'order_status' => 'pending',
        ])->orderBy('id')->limit($count)->get();
    }

    /**
     * Orders carrying an edit history row — what the `edit-order/due-payment-by-*`
     * endpoints settle. They write, so a parity run needs one order per call.
     *
     * @return Collection<int, Order>
     */
    public static function ordersWithEditHistoryOf(User $customer, int $count): Collection
    {
        return Order::where(['customer_id' => $customer->id, 'is_guest' => 0])
            ->whereIn('id', OrderEditHistory::distinct()->pluck('order_id'))
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /** @return Collection<int, Order> */
    public static function ordersOf(Seller $seller, int $count): Collection
    {
        return Order::where(['seller_is' => 'seller', 'seller_id' => $seller->id])
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    public static function orderDetailIdOf(Seller $seller): ?int
    {
        return OrderDetail::where(['seller_id' => $seller->id])->orderBy('id')->value('id');
    }

    /**
     * Orders of the seller that `order-detail-status`, `order-detail-info-update` and
     * `edit-order-submit` transition rather than refuse: still pending, paid on delivery
     * so no offline-payment or edit-settlement guard fires, and owned by a customer that
     * still exists. Every one of those endpoints writes, so a parity run needs one order
     * per call — and both orders must sit in the same state, or the two calls answer from
     * different guard branches.
     *
     * @return Collection<int, Order>
     */
    public static function statusUpdatableOrdersOf(Seller $seller, int $count): Collection
    {
        return self::ordersWithResolvableProducts()
            ->where([
                'seller_is' => 'seller',
                'seller_id' => $seller->id,
                'is_guest' => 0,
                'order_status' => 'pending',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'unpaid',
                'edit_due_amount' => 0,
                'edit_return_amount' => 0,
            ])->whereHas('customer')
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /**
     * Orders whose every line still points at a product row that exists.
     * `OrderManager::getStockUpdateOnOrderStatusChange()` — which every status-changing
     * endpoint runs — dereferences the product without a null check, so an order left
     * behind by a deleted product answers 500 instead of the contract under test.
     */
    private static function ordersWithResolvableProducts(): Builder
    {
        return Order::whereHas('details')
            ->whereDoesntHave('details', function ($query) {
                return $query->whereNotIn('product_id', Product::query()->select('id'));
            });
    }

    /**
     * Order detail rows of the seller — what `order-wise-product-upload` attaches the
     * uploaded file to. It writes, so a parity run needs one row per call.
     *
     * @return Collection<int, OrderDetail>
     */
    public static function orderDetailsOf(Seller $seller, int $count): Collection
    {
        return OrderDetail::where(['seller_id' => $seller->id])->orderBy('id')->limit($count)->get();
    }

    /** An in-stock, published product of the seller — one an order edit can price. */
    public static function activeProductOf(Seller $seller): ?Product
    {
        return Product::where(['added_by' => 'seller', 'user_id' => $seller->id, 'status' => 1])
            ->where('current_stock', '>', 5)
            ->orderBy('id')
            ->first();
    }

    public static function deliveryManWithAssignedOrder(): ?DeliveryMan
    {
        return DeliveryMan::whereRaw('LENGTH(auth_token) > 30')
            ->whereIn('id', Order::whereNotNull('delivery_man_id')->distinct()->pluck('delivery_man_id'))
            ->orderBy('id')
            ->first();
    }

    /** @return Collection<int, Order> */
    public static function ordersOfDeliveryMan(DeliveryMan $deliveryMan, int $count): Collection
    {
        return Order::where(['delivery_man_id' => $deliveryMan->id])
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /**
     * A delivery man carrying enough orders `update-order-status` still moves — the
     * first delivery man with any assigned order usually has only one such row, and
     * the endpoint writes, so a parity run needs one order per call.
     */
    public static function deliveryManWithUndeliveredOrders(int $count): ?DeliveryMan
    {
        $deliveryManId = self::undeliveredAssignedOrders()
            ->whereIn('delivery_man_id', self::uniquelyAuthenticatableDeliveryMen()->pluck('id'))
            ->groupBy('delivery_man_id')
            ->havingRaw('COUNT(*) >= ?', [$count])
            ->orderBy('delivery_man_id')
            ->value('delivery_man_id');

        return $deliveryManId === null ? null : DeliveryMan::find($deliveryManId);
    }

    /** @return Collection<int, Order> */
    public static function undeliveredOrdersOfDeliveryMan(DeliveryMan $deliveryMan, int $count): Collection
    {
        return self::undeliveredAssignedOrders()
            ->where(['delivery_man_id' => $deliveryMan->id])
            ->orderBy('id')
            ->limit($count)
            ->get();
    }

    /**
     * Delivery men `delivery_man_auth` resolves back to themselves. It looks the bearer
     * token up with `first()`, so a token several rows share authenticates as whichever
     * row comes back first — and an endpoint scoped to `delivery_man_id` then answers for
     * a courier the test never chose.
     */
    private static function uniquelyAuthenticatableDeliveryMen(): Builder
    {
        return DeliveryMan::whereRaw('LENGTH(auth_token) > 30')
            ->whereIn('auth_token', DeliveryMan::query()
                ->select('auth_token')
                ->groupBy('auth_token')
                ->havingRaw('COUNT(*) = 1'));
    }

    /** Orders `update-order-status` still moves — a delivered one answers "already delivered". */
    private static function undeliveredAssignedOrders(): Builder
    {
        return self::ordersWithResolvableProducts()
            ->whereNotNull('delivery_man_id')
            ->where('order_status', '!=', 'delivered');
    }

    /**
     * A customer whose paid order already carries a courier review — what
     * `customer/order/deliveryman-review` reads back.
     */
    public static function customerWithCourierReview(): ?User
    {
        $customerId = self::paidOrdersWithCourier()
            ->whereIn('id', Review::whereNotNull('delivery_man_id')->distinct()->pluck('order_id'))
            ->value('customer_id');

        return $customerId === null ? null : User::find($customerId);
    }

    /**
     * Paid orders whose courier review already exists — the rows
     * `deliveryman-review/update` and `deliveryman-reviews/submit` rewrite. Both endpoints
     * write, so a parity run needs one order per call.
     *
     * @return Collection<int, Order>
     */
    public static function reviewedPaidOrdersWithCourierOf(User $customer, int $count): Collection
    {
        return self::paidOrdersWithCourier()
            ->where(['customer_id' => $customer->id])
            ->whereIn('id', Review::whereNotNull('delivery_man_id')->distinct()->pluck('order_id'))
            ->limit($count)
            ->get();
    }

    /** A paid order with no courier assigned — the review endpoints' 403 branch. */
    public static function paidOrderWithoutCourierOf(User $customer): ?Order
    {
        return Order::where(['customer_id' => $customer->id, 'is_guest' => 0, 'payment_status' => 'paid'])
            ->whereNull('delivery_man_id')
            ->orderBy('id')
            ->first();
    }

    /**
     * A seller whose own delivery men have delivered that seller's own orders — the only
     * couriers `seller/top-delivery-man` ranks.
     */
    public static function sellerWithTopDeliveryMen(): ?Seller
    {
        $sellerId = DeliveryMan::whereIn('seller_id', self::authenticatableSellers()->pluck('id'))
            ->whereHas('deliveredOrders', function ($query) {
                return $query->where(['seller_is' => 'seller'])
                    ->whereColumn('orders.seller_id', 'delivery_men.seller_id')
                    ->whereNotNull('delivery_man_id');
            })
            ->orderBy('seller_id')
            ->value('seller_id');

        return $sellerId === null ? null : Seller::find($sellerId);
    }

    /**
     * An approved seller the `seller_api_auth` middleware accepts that owns a shop and at
     * least one physical product of its own — everything the clearance-sale fixtures copy.
     */
    public static function sellerWithOwnPhysicalProduct(): ?Seller
    {
        return self::authenticatableSellers()
            ->whereHas('shop')
            ->whereHas('product', function ($query) {
                return $query->where(['added_by' => 'seller', 'product_type' => 'physical']);
            })
            ->orderBy('id')
            ->first();
    }

    private static function paidOrdersWithCourier(): Builder
    {
        return Order::where(['is_guest' => 0, 'payment_status' => 'paid'])
            ->whereNotNull('customer_id')
            ->whereNotNull('delivery_man_id')
            ->orderBy('id');
    }

    private static function authenticatableSellers(): Builder
    {
        return Seller::where(['status' => 'approved'])->whereRaw('LENGTH(auth_token) > 30');
    }
}
