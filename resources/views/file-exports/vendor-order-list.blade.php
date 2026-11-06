<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate('Vendor Order List')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Vendor Information').' '.'-' }}</th>
        <th></th>
        <th>
            {{translate('Shop Name').' '.'-'.' '.ucwords($data['shop']->name)}}
            <br>
            {{translate('Shop Phone').' '.'-'.' '.$data['shop']->contact ?? translate('Not found')}}
            <br>
            {{translate('Shop Address').' '.'-'.' '.$data['shop']->address ?? translate('Not found')}}
            <br>
            {{translate('Total Order').' '.'-'.' '.count($data['orders']) }}
        </th>
    </tr>
    <tr>
        <th>{{translate('Order Status')}}</th>
        <th></th>
        <th>
            @foreach ($data['statusArray'] as $key=>$value)
                {{translate($key != 'failed' ? $key : 'failed_to_deliver').' '.'-'.' '.$value}}
            @endforeach
        </th>
    </tr>
    <tr>
        <th> {{translate('Serial')}}    </th>
        <th> {{translate('Order ID')}}    </th>
        <th> {{translate('Order Date')}}    </th>
        <th> {{translate('Customer Name')}}    </th>
        <th> {{translate('Total Amount')}}    </th>
        <th> {{translate('Payment Status')}}</th>
        <th> {{translate('Order Status')}}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($data['orders'] as $key=>$order)
        <tr>
            <td> {{++$key}}	</td>
            <td> {{$order->id}}	</td>
            <td> {{date('d M, Y h:i A',strtotime($order->created_at))}}</td>
            <td> {{ucwords($order->is_guest == 0 ? (($order?->customer?->f_name ?? translate('Not found')) .' '. $order?->customer?->l_name) : translate('Guest customer'))}}	</td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order['order_amount'] ?? 0), currencyCode: getCurrencyCode())}}</td>
            <td> {{translate($order->payment_status)}}</td>
            <td> {{translate($order->order_status)}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
