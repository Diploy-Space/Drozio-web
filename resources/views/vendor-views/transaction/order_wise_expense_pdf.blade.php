<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Expense Transaction Statement '.$transaction->id }}</title>
    <meta http-equiv="Content-Type" content="text/html;"/>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="{{dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css')}}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor/order-transaction.css') }}">
</head>

<body>
<table class="content-position">
    <tr>
        <td>
            <table class="bs-0">
                <tr>
                    <th class="h3 p-0 text-left">
                        {{translate('Expense Transaction Statement')}}
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
                                <th class="bold black p-0 text-left p-3">{{translate('Transaction Date')}}</th>
                                <td class="p-0 p-3">
                                    : {{ date_format($transaction->orderTransaction->updated_at, 'd F Y') }}</td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left p-3">{{translate('Order ID')}}</th>
                                <td class="p-0 p-3">:
                                    {{ $transaction->id }}
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="p-0 text-left">
                        <table>
                            <tr>
                                <th class="bold black p-0 text-left">XID</th>
                                <td class="p-0 p-3">
                                    : {{ ucfirst($transaction->orderTransaction->transaction_id) }}</td>
                            </tr>
                            <tr>
                                <th class="bold black p-0 text-left">{{translate('Expense Type')}} </th>
                                <td class="p-0 p-3">:
                                    @if ($transaction['expense_types']['coupon_type'] === 'free_delivery')
                                        {{ translate('Free Delivery Promotion') }}
                                    @elseif ($transaction['expense_types']['coupon_type'] !== null)
                                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['coupon_type'])) }}
                                    @endif
                                    @if ($transaction['expense_types']['free_delivery_type'] !== null)
                                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['free_delivery_type'])) }}
                                    @endif
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
                    <td class="text-center"
                        style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Serial')}}</td>
                    <td style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Details')}}</td>
                    <td class="text-right"
                        style="background-color: #0177CD !important; color: white; font-weight: bold">{{translate('Amount')}}</td>
                </tr>
                <tr>
                    <td class="text-center">1</td>
                    <td>{{translate('Expense Amount')}}</td>
                    <td class="text-right">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['expense_amount']), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
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
