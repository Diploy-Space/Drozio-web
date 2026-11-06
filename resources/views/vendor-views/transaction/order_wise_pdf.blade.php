<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Order Transaction Statement - '.$transaction->order_id }}</title>
    <meta http-equiv="Content-Type" content="text/html;"/>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css')}}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor/order-transaction.css') }}">
</head>

<?php
    $companyLogo = getWebConfig(name: 'company_web_logo');
?>

<body>
<table class="content-position">
    <tr>
        <td>
            <table class="bs-0">
                <tr>
                    <th class="h3 p-0 text-left">
                        {{translate('Order Transaction Statement')}}
                    </th>
                    <th class="p-0 text-right">
                        <img class="logo" src="{{ getStorageImages(path: $companyLogo, type: 'backend-logo') }}" alt="">
                    </th>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="pt-0">
            <table class="bs-0">
                <tr>
                    <td class="p-0 text-left">
                        <b class="bold black">{{translate('Date')}}</b> : {{ date('F d, Y') }} <span
                                class="block h-5"></span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="content-position">
    <tr>
        <td class="pt-0">
            <table class="bs-0">
                <tr>
                    <td class="p-0 text-left">
                        <table>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Order ID')}}</th>
                                <td class="p-0 p-3">: {{ $transaction['order_id'] }}</td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Date')}}</th>
                                <td class="p-0 p-3">
                                    : {{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Vendor Information')}}</th>
                                <td class="p-0 p-3">:
                                    @if($transaction['seller_is'] == 'admin')
                                        {{ getInHouseShopConfig(key:'name') }}
                                    @else
                                        @if (isset($transaction->seller))
                                            {{ $transaction->seller->shop->name }}
                                        @else
                                            {{translate('Not found')}}
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Customer Information')}}</th>
                                <td class="p-0 p-3">:
                                    @if (isset($transaction->customer))
                                        {{ $transaction->customer->f_name}} {{ $transaction->customer->l_name }}
                                    @elseif($transaction->order->is_guest)
                                        {{translate('Guest customer')}}
                                    @else
                                        {{translate('Not found')}}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="p-0 text-left">
                        <table>

                                <tr>
                                    <th class="bold black p-0 text-left">{{ translate('Delivered by') }}</th>
                                    <td class="p-0 p-3">:
                                       {{ $transaction['delivered_by'] }}
                                    </td>
                                </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Payment Method')}}</th>
                                <td class="p-0 p-3">:
                                    @if(in_array($transaction->order->payment_method, ['cash', 'cash_on_delivery', 'pay_by_wallet', 'offline_payment']))
                                        {{ ucfirst(str_replace('_', ' ', $transaction->order->payment_method)) }}
                                    @else
                                        {{translate('Digital Payment')}}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Payment Status')}}</th>
                                <td class="p-0 p-3">
                                    : {{ ucfirst($transaction->order->payment_status) }}</td>
                            </tr>
                            <tr>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td class="pt-0">
            <table class="bs-0 __product-table inter">
                <tbody>
                <tr>
                    <td class="pl-0 pr-0 text-center"
                        style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Serial')}}</td>
                    <td style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Details')}}</td>
                    <td class="text-right"
                        style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Amount')}}</td>
                </tr>
                <tr>
                    <td class="text-center">1</td>
                    <td>{{translate('Total Product Amount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->order_details_sum_price * $transaction->order_details_sum_qty), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>{{translate('Product Discount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->order_details_sum_discount), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>{{translate('Coupon Discount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction?->order?->discount_amount ?? 0), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">4</td>
                    <td>{{ translate('Referral Discount') }}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction?->order?->refer_and_earn_discount), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">4</td>
                    <td>{{translate('Discounted Amount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($transaction->order_details_sum_price * $transaction->order_details_sum_qty) - $transaction->order_details_sum_discount - (isset($transaction->order->coupon) && $transaction->order->coupon->coupon_type != 'free_delivery'?$transaction->order->discount_amount:0)), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">5</td>
                    <td>{{translate('VAT/TAX')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['tax']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">6</td>
                    <td>{{translate('Shipping Charge')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->order->shipping_cost), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">7</td>
                    <td>{{translate('Delivery man incentive')}}</td>
                    <td class="text-right">
                        {{ ($transaction->order->delivery_type=='self_delivery' && $transaction->order->shipping_responsibility=='sellerwise_shipping' && $transaction->order->delivery_man_id) ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->order->deliveryman_charge), currencyCode: getCurrencyCode()) : setCurrencySymbol(amount: usdToDefaultCurrency(amount: 0), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">8</td>
                    <td>{{translate('Order Amount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->order->order_amount), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="content-position">
    <tr>
        <th class="text-left black bold"><b>{{translate('Additional Information')}}</b></th>
        <th class="text-right black bold"><b>{{translate('Totals')}}</b></th>
    </tr>
    <tbody class="bs-0 __product-table inter add-info-border-top-bottom">
    <tr>
        <td>
            {{translate('Admin Discount')}}
        </td>
        <td class="text-right">
            @php($admin_coupon_discount = ($transaction->order->coupon_discount_bearer == 'inhouse' && $transaction->order->discount_type == 'coupon_discount') ? $transaction->order->discount_amount : 0)
            @php($admin_referral_Discount = $transaction?->order?->refer_and_earn_discount ?? 0)
            @php($admin_shipping_discount = ($transaction->order->free_delivery_bearer=='admin' && $transaction->order->is_shipping_free) ? $transaction->order->extra_discount : 0)
            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $admin_coupon_discount+$admin_shipping_discount), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{ translate('Vendor Discount') }}
        </td>
        <td class="text-right">
            @php($seller_coupon_discount = ($transaction->order->coupon_discount_bearer == 'seller' && $transaction->order->discount_type == 'coupon_discount') ? $transaction->order->discount_amount : 0)
            @php($seller_shipping_discount = ($transaction->order->free_delivery_bearer=='seller' && $transaction->order->is_shipping_free) ? $transaction->order->extra_discount : 0)
            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_coupon_discount+$seller_shipping_discount), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{ translate('Admin Commission') }}
        </td>
        <td class="text-right">
            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_commission']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <?php
        $orderDetailsSumPrice = $transaction?->orderDetails[0]?->order_details_sum_price ?? 0;
        $orderDetailsSumDiscount = $transaction?->orderDetails[0]?->order_details_sum_discount ?? 0;
        $referAndEarnDiscount = $transaction?->order?->refer_and_earn_discount ?? 0;
        $couponDiscount = isset($transaction->order->coupon) && $transaction->order->coupon->coupon_type != 'free_delivery' ? $transaction->order->discount_amount : 0;
        $discountAmount = $orderDetailsSumPrice - $orderDetailsSumDiscount - $couponDiscount - ($referAndEarnDiscount);
        $deliveryManIncentive = $transaction->order->delivery_type == 'self_delivery' && $transaction->order->delivery_man_id ? $transaction->order->deliveryman_charge : 0;

        $adminCouponDiscount = ($transaction->order->coupon_discount_bearer == 'inhouse' && $transaction->order->discount_type == 'coupon_discount') ? $transaction->order->discount_amount : 0;
        $adminShippingDiscount = ($transaction->order->free_delivery_bearer == 'admin' && $transaction->order->is_shipping_free) ? $transaction->order->extra_discount : 0;
        $adminDiscount = $adminCouponDiscount + $adminShippingDiscount + $referAndEarnDiscount;

        $sellerCouponDiscount = ($transaction->order->coupon_discount_bearer == 'seller' && $transaction->order->discount_type == 'coupon_discount') ? $transaction->order->discount_amount : 0;
        $sellerShippingDiscount = ($transaction->order->free_delivery_bearer == 'seller' && $transaction->order->is_shipping_free) ? $transaction->order->extra_discount : 0;
        $vendorDiscount = $sellerCouponDiscount + $sellerShippingDiscount;
    ?>
    <tr>
        <td>
            {{translate('Vendor Net Income')}}
        </td>
        <td class="text-right">
            <?php
                $vendorNetIncome = 0;
                if ($transaction['seller_is'] == 'seller') {
                    $vendorNetIncome += $transaction['order_amount'] - $transaction['admin_commission'];
                    if (isset($transaction->order->deliveryMan) && $transaction->order->deliveryMan->seller_id != 0) {
                        $vendorNetIncome += $transaction['delivery_charge'];
                    }
                }

                if ((empty($transaction?->order?->delivery_type) || $transaction->order->delivery_type == 'self_delivery') && $transaction->order->shipping_responsibility == 'sellerwise_shipping' && $transaction->order->seller_is == 'seller') {
                    $vendorNetIncome -= $transaction->order->deliveryman_charge;
                }

                if ($transaction['seller_is'] == 'seller') {
                    $vendorNetIncome += $transaction->order->coupon_discount_bearer == 'inhouse' ? $adminCouponDiscount : 0;
                }

                if ($transaction['seller_is'] == 'seller') {
                    if ($transaction->order->shipping_responsibility == 'inhouse_shipping') {
                        $vendorNetIncome -= $transaction->order->shipping_cost;
                        $vendorNetIncome += $transaction->order->free_delivery_bearer == 'admin' && $transaction->order->is_shipping_free ? $transaction->order->shipping_cost : 0;
                    } elseif ($transaction->order->shipping_responsibility == 'sellerwise_shipping') {
                        $vendorNetIncome += $transaction->order->free_delivery_bearer == 'admin' ? $transaction->order->shipping_cost : 0;
                        $vendorNetIncome -= ($transaction->order->free_delivery_bearer == 'seller') ? $transaction->order->shipping_cost : 0;
                    }
                }
            ?>

            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorNetIncome), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    </tbody>
</table>
<br><br><br><br><br><br><br><br>
<table class="">
    <tr>
        <th class="content-position-y bg-light py-4 footer">
            <div class="d-flex justify-content-center gap-2">
                <div class="mb-2">
                    <i class="fa fa-phone"></i>
                    {{translate('Phone')}}
                    : {{ $company_phone }}
                </div>
                <div class="mb-2">
                    <i class="fa fa-envelope" aria-hidden="true"></i>
                    {{translate('Email')}}
                    : {{ $company_email }}
                </div>
            </div>
            <div class="mb-2">
                {{url('/')}}
            </div>
            <div>
                {{translate('All copy right reserved').' © '.date('Y').' '.$company_name}}
            </div>
        </th>
    </tr>
</table>
</body>
</html>
