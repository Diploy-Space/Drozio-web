<html>
<table>
    <thead>
        <tr>
            <th style="font-size: 18px">{{translate('Customer Order List')}}</th>
        </tr>
        <tr>

            <th>{{ translate('Customer Information').' '.'-' }}</th>
            <th></th>
            <th>
                {{translate('Customer Name').' '.'-'.' '.ucwords($data['customer']->f_name.' '.$data['customer']->l_name)}}
                <br>
                {{translate('Customer Email').' '.'-'.' '.$data['customer']->email ?? translate('Not found')}}
                <br>
                {{translate('Customer Phone').' '.'-'.' '.$data['customer']->phone ?? translate('Not found')}}
                <br>
                {{translate('Total Order').' '.'-'.' '.count($data['orders']) }}
            </th>
        </tr>
        <tr>
            <th>{{translate('Search Bar Content').' - '}}</th>
            <th></th>
            <th>
                {{$data['searchValue'] ?? 'N/A'}}
            </th>
        </tr>
        <tr>
            <th> {{translate('Serial')}}    </th>
            <th> {{translate('Order ID')}}    </th>
            <th> {{translate('Order Date')}}    </th>
            <th> {{translate('Store Name')}}    </th>
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
                <td> {{ucwords($order?->seller_is == 'seller' ? ($order?->seller?->shop->name ?? translate('Not found')) : translate('In-house'))}}	</td>
                <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order['order_amount'] ?? 0), currencyCode: getCurrencyCode())}}</td>
                <td> {{translate($order->payment_status)}}</td>
                <td> {{translate($order->order_status)}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</html>
