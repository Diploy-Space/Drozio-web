<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Order Transaction Statement - '.$duration }}</title>
    <meta http-equiv="Content-Type" content="text/html;"/>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css')}}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/order-transaction.css') }}">
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
                        @php($invoiceSettings = getWebConfig(name: 'invoice_settings'))
                        @if(isset($invoiceSettings['invoice_logo_status']) && $invoiceSettings['invoice_logo_status'] == 1)
                            @if(isset($invoiceSettings['invoice_logo_type']) && $invoiceSettings['invoice_logo_type'] == 'default')
                                <img height="40"
                                     src="{{ getStorageImages(path: getWebConfig(name: 'company_web_logo_png'), type:'backend-logo') }}"
                                     alt="" style="margin-bottom:5px;object-fit: contain">
                            @elseif(isset($invoiceSettings['invoice_logo_type']) && $invoiceSettings['invoice_logo_type'] == 'custom' && isset($invoiceSettings['image']))
                                <img height="40"
                                     src="{{ getStorageImages(path: imagePathProcessing(imageData:  $invoiceSettings['image'], path:'company'), type: 'backend-logo') }}"
                                     alt="" style="margin-bottom:5px;object-fit: contain">
                            @endif
                        @endif
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
                        <b class="bold black">
                            {{ translate('Date')}}
                        </b> : {{ date('F d, Y') }}
                        <span class="block h-5"></span>
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
                                <th class="bold black p-0 text-left p-3">{{translate('Duration')}}</th>
                                <td class="p-0 p-3 text-capitalize">: {{ ucwords($duration) }}</td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Vendor Information')}}</th>
                                <td class="p-0 p-3">:
                                    {{ ucwords($seller_info) }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Customer Information')}}</th>
                                <td class="p-0 p-3">:
                                    {{ ucwords($customer_info) }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Status')}}</th>
                                <td class="p-0 p-3">:
                                    {{ (ucwords($status)) }}
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
                                <th class="bold black p-0 text-left">{{translate('Total Order')}} </th>
                                <td class="p-0p-3">:
                                    {{ $data['total_orders'] }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('In-house Order')}}</th>
                                <td class="p-0p-3">:
                                    {{ $data['in_house_orders'] }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Vendor Order')}}</th>
                                <td class="p-0p-3">:
                                    {{ $data['seller_orders'] }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Total In House Products')}}</th>
                                <td class="p-0p-3">:
                                    {{ $data['total_in_house_products'] }}
                                </td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Total Stores')}}</th>
                                <td class="p-0p-3">:
                                    {{ $data['total_stores'] }}
                                </td>
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
                    <td class="pl-0 pr-0" style="background: #0177CD !important;color: white;font-weight: bold;">{{translate('Serial')}}</td>
                    <td style="background: #0177CD !important;color: white;font-weight: bold;">{{translate('Details')}}</td>
                    <td class="text-right" style="background: #0177CD !important;color: white;font-weight: bold;">{{translate('Amount')}}</td>
                </tr>
                <tr>
                    <td class="text-center">1</td>
                    <td>{{translate('Total Ordered Product Price')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_ordered_product_price']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>{{translate('Total Product Discount')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_product_discount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>{{translate('Total Coupon Discount')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_coupon_discount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>{{translate('Total Referral Discount')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_referral_discount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">4</td>
                    <td>{{translate('Total Discounted Amount')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_discounted_amount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">5</td>
                    <td>{{translate('Total')}} {{translate('VAT/TAX')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_tax']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">6</td>
                    <td>{{translate('Total Delivery Charge')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_delivery_charge']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">6</td>
                    <td>{{translate('Total Deliveryman incentive')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_deliveryman_incentive']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-center">7</td>
                    <td>{{translate('Total Order Amount')}}</td>
                    <td class="text-right">
                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_order_amount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="content-position">
    <tr>
        <th class="text-left black bold"><b>{{translate('Additional information')}}</b></th>
        <th class="text-right black bold"><b>{{translate('Totals')}}</b></th>
    </tr>
    <tbody class="bs-0 __product-table inter add-info-border-top-bottom">
    <tr>
        <td>
            {{translate('Admin Discount')}}
        </td>
        <td class="text-right">
            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_admin_discount']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{ translate('Vendor Discount') }}
        </td>
        <td class="text-right">
            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_seller_discount']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{ translate('Admin Commission') }}
        </td>
        <td class="text-right">
            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_admin_commission']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{translate('Admin Net Income')}}
        </td>
        <td class="text-right">
            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_admin_net_income']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    <tr>
        <td>
            {{translate('Vendor Net Income')}}
        </td>
        <td class="text-right">
            {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_seller_net_income']), currencyCode: getCurrencyCode()) }}
        </td>
    </tr>
    </tbody>
</table>
<br>
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
