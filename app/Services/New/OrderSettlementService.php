<?php

namespace App\Services\New;

use App\Models\AdminWallet;
use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\OrderExpectedDeliveryHistory;
use App\Models\OrderTransaction;
use App\Models\SellerWallet;
use App\Models\Shop;
use App\Models\Transaction;
use App\Services\BaseService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderSettlementService extends BaseService
{
    public const FIELD_DELIVERYMAN_CHARGE = 'deliveryman_charge';

    public const FIELD_EXPECTED_DELIVERY_DATE = 'expected_delivery_date';

    public const DELIVERY_SCHEDULE_FIELDS = [self::FIELD_DELIVERYMAN_CHARGE, self::FIELD_EXPECTED_DELIVERY_DATE];

    public function __construct(
        Order $order,
        private readonly OrderExpectedDeliveryHistory $orderExpectedDeliveryHistory,
        private readonly AdminWallet $adminWallet,
        private readonly SellerWallet $sellerWallet,
        private readonly Transaction $transaction,
        private readonly OrderTransaction $orderTransaction,
        private readonly Shop $shop,
        private readonly OrderEditHistory $orderEditHistory,
    ) {
        parent::__construct($order);
    }

    public function manageWalletOnOrderStatusChange(object $order, string $receivedBy): bool
    {
        $order = $this->model->find($order['id']);
        $orderSummary = getOrderSummary(order: $order);
        $orderAmount = $orderSummary['subtotal'] - $orderSummary['total_discount_on_product'] - $order['discount_amount'];
        $commission = $order['admin_commission'];
        $shippingModel = $order->shipping_responsibility;

        $adminWallet = $this->adminWallet->where('admin_id', 1)->first();
        if (! $adminWallet) {
            $adminWalletData = [
                'admin_id' => 1,
                'withdrawn' => 0,
                'commission_earned' => 0,
                'inhouse_earning' => 0,
                'delivery_charge_earned' => 0,
                'pending_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->adminWallet->create($adminWalletData);
        }

        $sellerWallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
        if (! $sellerWallet) {
            $sellerWalletData = [
                'seller_id' => $order['seller_id'],
                'withdrawn' => 0,
                'commission_given' => 0,
                'total_earning' => 0,
                'pending_withdraw' => 0,
                'delivery_charge_earned' => 0,
                'collected_cash' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->sellerWallet->create($sellerWalletData);
        }

        if ($order['coupon_code'] && $order['coupon_code'] != '0' && $order['seller_is'] == 'seller' && $order['discount_type'] == 'coupon_discount') {
            if ($order['coupon_discount_bearer'] == 'inhouse') {
                $sellerWallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
                $sellerWallet['total_earning'] += $order['discount_amount'];
                $sellerWallet->save();

                $paidBy = 'admin';
                $payerId = 1;
                $paymentReceiverId = $order['seller_id'];
                $paidTo = 'seller';

            } elseif ($order->coupon_discount_bearer == 'seller') {
                $paidBy = 'seller';
                $payerId = $order['seller_id'];
                $paymentReceiverId = $order['seller_id'];
                $paidTo = 'admin';
            }

            $transaction = [
                'order_id' => $order->id,
                'payment_for' => 'coupon_discount',
                'payer_id' => $payerId,
                'payment_receiver_id' => $paymentReceiverId,
                'paid_by' => $paidBy,
                'paid_to' => $paidTo,
                'payment_status' => 'disburse',
                'amount' => $order['discount_amount'],
                'transaction_type' => 'expense',
            ];
            $this->transaction->create($transaction);
        }

        if ($order['refer_and_earn_discount'] > 0) {
            $paidBy = 'admin';
            $payerId = 1;
            $paymentReceiverId = $order['seller_id'];
            $paidTo = 'seller';

            $transaction = [
                'order_id' => $order->id,
                'payment_for' => 'referral_discount',
                'payer_id' => $payerId,
                'payment_receiver_id' => $paymentReceiverId,
                'paid_by' => $paidBy,
                'paid_to' => $paidTo,
                'payment_status' => 'disburse',
                'amount' => $order['refer_and_earn_discount'],
                'transaction_type' => 'expense',
            ];
            $this->transaction->create($transaction);
        }

        if ($order['is_shipping_free'] && $order['seller_is'] == 'seller') {

            $sellerWallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
            $adminWallet = $this->adminWallet->where('admin_id', 1)->first();

            if ($order['free_delivery_bearer'] == 'admin' && $order['shipping_responsibility'] == 'sellerwise_shipping') {
                $sellerWallet->delivery_charge_earned += $order->extra_discount;
                $sellerWallet->total_earning += $order->extra_discount;

                $adminWallet->delivery_charge_earned -= $order->extra_discount;

                $paidBy = 'admin';
                $payerId = 1;
                $paymentReceiverId = $order->seller_id;
                $paidTo = 'seller';

            } elseif ($order->free_delivery_bearer == 'seller' && $order->shipping_responsibility == 'inhouse_shipping') {
                $sellerWallet->delivery_charge_earned -= $order->extra_discount;
                $sellerWallet->total_earning -= $order->extra_discount;

                $adminWallet->delivery_charge_earned += $order->extra_discount;

                $paidBy = 'seller';
                $payerId = $order->seller_id;
                $paymentReceiverId = $order->seller_id;
                $paidTo = 'admin';
            } elseif ($order['free_delivery_bearer'] == 'admin' && $order['shipping_responsibility'] == 'inhouse_shipping') {
                $paidBy = 'admin';
                $payerId = 1;
                $paymentReceiverId = $order->seller_id;
                $paidTo = 'admin';
            } elseif ($order->free_delivery_bearer == 'seller' && $order->shipping_responsibility == 'sellerwise_shipping') {
                $paidBy = 'seller';
                $payerId = $order->seller_id;
                $paymentReceiverId = $order->seller_id;
                $paidTo = 'seller';
            }

            $sellerWallet->save();
            $adminWallet->save();

            $transaction = [
                'order_id' => $order->id,
                'payment_for' => 'free_shipping_over_order_amount',
                'payer_id' => $payerId,
                'payment_receiver_id' => $paymentReceiverId,
                'paid_by' => $paidBy,
                'paid_to' => $paidTo,
                'payment_status' => 'disburse',
                'amount' => $order['discount_amount'],
                'transaction_type' => 'expense',
            ];
            $this->transaction->create($transaction);
        }

        if ($order['seller_is'] == 'seller' &&
            $shippingModel == 'sellerwise_shipping'
        ) {
            $editHistoryAmount = $this->orderEditHistory->where([
                'order_id' => $order->id,
                'order_due_payment_status' => 'paid',
                'order_due_payment_method' => 'cash_on_delivery',
            ])?->sum('order_due_amount') ?? 0;
            $wallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
            $wallet->collected_cash += $editHistoryAmount;
            $wallet->save();
        }

        if ($order['payment_method'] == 'cash_on_delivery' || $order['payment_method'] == 'offline_payment') {
            $shop = $this->shop->when($order['seller_is'] == 'admin', function ($query) {
                return $query->where(['author_type' => 'admin']);
            })->when($order['seller_is'] == 'seller', function ($query) use ($order) {
                return $query->where(['author_type' => 'vendor', 'seller_id' => $order['seller_id']]);
            })->first();

            $transaction = [
                'transaction_id' => getUniqueId(),
                'customer_id' => $order['customer_id'],
                'seller_id' => $order['seller_id'],
                'shop_id' => $shop['id'],
                'seller_is' => $order['seller_is'],
                'order_id' => $order['id'],
                'order_amount' => $order['order_amount'],
                'seller_amount' => $orderAmount - $commission,
                'admin_commission' => $commission,
                'received_by' => $receivedBy,
                'status' => 'disburse',
                'delivery_charge' => $order['shipping_cost'] - ($order['is_shipping_free'] ? $order['extra_discount'] : 0),
                'tax' => $orderSummary['total_tax'],
                'delivered_by' => $receivedBy,
                'payment_method' => $order['payment_method'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->orderTransaction->create($transaction);

            $wallet = $this->adminWallet->where('admin_id', 1)->first();
            $wallet->commission_earned += $commission;
            if ($shippingModel == 'inhouse_shipping' && ! $order['is_shipping_free']) {
                $wallet->delivery_charge_earned += $order['shipping_cost'];
            }
            $wallet->save();

            if ($order['seller_is'] == 'admin') {
                $wallet = $this->adminWallet->where('admin_id', 1)->first();
                $wallet->inhouse_earning += $orderAmount;
                if ($shippingModel == 'sellerwise_shipping' && ! $order['is_shipping_free']) {
                    $wallet->delivery_charge_earned += $order['shipping_cost'];
                }
                $wallet->total_tax_collected += $orderSummary['total_tax'];
            } else {
                $wallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
                $wallet->commission_given += $commission;
                $wallet->total_tax_collected += $orderSummary['total_tax'];
                if ($shippingModel == 'sellerwise_shipping') {
                    if (! $order['is_shipping_free']) {
                        $wallet->delivery_charge_earned += $order['shipping_cost'];
                    }

                    $currentOrderAmount = $order['order_amount'];
                    if ($order['payment_method'] == 'offline_payment') {
                        $editHistoryAmount = $this->orderEditHistory->where([
                            'order_id' => $order->id,
                            'order_due_payment_status' => 'paid',
                            'order_due_payment_method' => 'cash_on_delivery',
                        ])?->sum('order_due_amount') ?? 0;
                        $currentOrderAmount -= $editHistoryAmount;
                    }

                    if ($order['payment_method'] == 'cash_on_delivery') {
                        $wallet->collected_cash += $currentOrderAmount;
                    }

                    if ($order['payment_method'] == 'offline_payment') {
                        $wallet->total_earning += ($currentOrderAmount - $commission);
                    }

                } else {
                    $wallet->total_earning += ($orderAmount - $commission) + $orderSummary['total_tax'];
                }
            }
            $wallet->save();
        } else {
            $transaction = $this->orderTransaction->where(['order_id' => $order['id']])->first();
            $transaction->status = 'disburse';
            $transaction->save();

            $wallet = $this->adminWallet->where('admin_id', 1)->first();
            $wallet->commission_earned += $commission;

            $currentOrderAmount = $order['order_amount'];
            if (
                $order?->latestEditHistory &&
                $order?->latestEditHistory?->order_due_payment_status == 'paid' &&
                $order?->latestEditHistory?->order_due_payment_method == 'cash_on_delivery'
            ) {
                $currentOrderAmount -= $order?->latestEditHistory?->order_due_amount ?? 0;
            }
            $wallet->pending_amount -= $currentOrderAmount;

            if ($shippingModel == 'inhouse_shipping' && ! $order['is_shipping_free']) {
                $wallet->delivery_charge_earned += $order['shipping_cost'];
            }
            $wallet->save();

            if ($order['seller_is'] == 'admin') {
                $wallet = $this->adminWallet->where('admin_id', 1)->first();
                $wallet->inhouse_earning += $orderAmount;
                if ($shippingModel == 'sellerwise_shipping' && ! $order['is_shipping_free']) {
                    $wallet->delivery_charge_earned += $order['shipping_cost'];
                }
            } else {
                $wallet = $this->sellerWallet->where('seller_id', $order['seller_id'])->first();
                $wallet->commission_given += $commission;
                if ($shippingModel == 'sellerwise_shipping') {
                    if (! $order['is_shipping_free']) {
                        $wallet->delivery_charge_earned += $order['shipping_cost'];
                    }

                    $currentOrderAmount = ($orderAmount - $commission) + $orderSummary['total_tax'] + ($order['is_shipping_free'] ? 0 : $order['shipping_cost']);
                    $editHistoryAmount = $this->orderEditHistory->where([
                        'order_id' => $order->id,
                        'order_due_payment_status' => 'paid',
                        'order_due_payment_method' => 'cash_on_delivery',
                    ])?->sum('order_due_amount') ?? 0;
                    $currentOrderAmount -= $editHistoryAmount;

                    $wallet->total_earning += $currentOrderAmount;
                } else {
                    $wallet->total_earning += ($orderAmount - $commission) + $orderSummary['total_tax'];
                }
            }
            $wallet->total_tax_collected += $orderSummary['total_tax'];
            $wallet->save();
        }

        return true;
    }

    public function updateAmountDate(object $request, string|int $userId, string $userType): bool
    {
        $fieldName = $request['field_name'];
        $fieldValues = $request['field_val'];

        if (! in_array($fieldName, self::DELIVERY_SCHEDULE_FIELDS, true)) {
            return false;
        }

        if ($fieldName == self::FIELD_DELIVERYMAN_CHARGE) {
            $fieldValues = currencyConverter(amount: $fieldValues);
        }

        try {
            DB::beginTransaction();

            if ($fieldName == self::FIELD_EXPECTED_DELIVERY_DATE) {
                $this->createExpectedDeliveryHistory(
                    orderId: $request['order_id'],
                    userId: $userId,
                    userType: $userType,
                    expectedDeliveryDate: $fieldValues,
                    cause: $request['cause'] ?? null,
                );
            }

            $this->model->where(['id' => $request['order_id']])->update([$fieldName => $fieldValues]);

            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();

            return false;
        }

        return true;
    }

    public function createExpectedDeliveryHistory(int|string $orderId, int|string $userId, string $userType, string $expectedDeliveryDate, ?string $cause = null): Model
    {
        return $this->orderExpectedDeliveryHistory->create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'user_type' => $userType,
            'expected_delivery_date' => $expectedDeliveryDate,
            'cause' => $cause,
        ]);
    }
}
