<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Refund Transaction Report List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '. ($data['searchValue'] ?? 'N/A')}}
            <br>
            {{translate('Payment Method').' '.'-'.' '.translate($data['paymentMethod'] ?? 'all')}}
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}</td>
        <th>{{translate('Product Image')}}</th>
        <th>{{translate('Product Name')}}</th>
        <th>{{translate('Refund id')}}</th>
        <th>{{translate('Order ID')}}</th>
        <th>{{translate('Shop Name')}}</th>
        <th>{{translate('Payment Method')}}</th>
        <th>{{translate('Payment Status')}}</th>
        <th>{{translate('Paid By')}}</th>
        <th>{{translate('Amount')}}</th>
        <th>{{translate('Transaction Type')}}</th>
    </tr>
</thead>
<tbody>
    @foreach ($data['transactions'] as $key=>$transaction)
        <tr>
            <td> {{++$key}} </td>
            <td style="height: 100px"></td>
            <td>{{ isset($transaction->orderDetails->product->name) ? Str::limit($transaction->orderDetails->product->name, 40) : translate('Not found') }}
            </td>
            <td>{{$transaction->refund_id}}</td>
            <td>{{$transaction->order_id}}</td>
            <td>
                {{$transaction->order->seller_is == 'seller' && $transaction->order->seller ? $transaction->order->seller->shop->name : translate('In-house')}}
            </td>
            <td>
                {{translate(str_replace('_',' ',$transaction->payment_method))}}
            </td>
            <td>
                {{translate(str_replace('_',' ',$transaction->payment_status))}}
            </td>
            <td>
                {{translate($transaction->paid_by)}}
            </td>
            <td>
                {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction->amount), currencyCode: getCurrencyCode())}}
            </td>
            <td>
                {{ $transaction->transaction_type == 'Refund' ? translate('Refunded') : str_replace('_',' ',$transaction->transaction_type)}}
            </td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
