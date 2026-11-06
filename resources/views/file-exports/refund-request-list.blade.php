<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate('Refund Order List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Refund Status').' '.'-'.' '.translate($data['status'])}}
            <br>
            {{translate('Search Bar Content')}} - {{!empty($data['search']) ?  ucwords($data['search']) : 'N/A'}}
            @if(isset($data['vendor']))
            <br>
            {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
            @endif
            <br>
            {{translate('Total').' '.$data['status'].' '.translate('Refund requests')}} - {{count($data['refundList'])}}
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                <br>
                {{translate('Filter By').' '.'-'.' '.ucwords(translate($data['filter_By']))}}
            @endif
            <br>
            @if(isset($data['from']) && $data['from'] && isset($data['to']) && $data['to'])
                {{translate('From').' '.'-'.' '.date('d M, Y', strtotime($data['from']))}}
                <br>
                {{translate('To').' '.'-'.' '.date('d M, Y', strtotime($data['to']))}}
                <br>
            @endif
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}    </td>
        <td> {{translate('Order ID')}}    </td>
        <td> {{translate('Order Date')}}</td>
        <td> {{translate('Product Information')}}    </td>
        <td> {{translate('Product Amount')}}</td>
        <td> {{translate('Refund Amount')}}</td>
        <td> {{translate('Customer Name')}}    </td>
        @if(isset($data['data-from']) && $data['data-from'] == 'admin')
        <td> {{translate('Store Name')}}</td>
        @endif
        <td> {{translate('Delivery Name')}}</td>
        <td> {{translate('Refund Reason')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['refundList'] as $key=>$item)
        @php($row = $data['rows'][$item->id])
        <tr>
            <td> {{++$key}}    </td>
            <td> {{$item->order_id}}</td>
            <td> {{ $row['order_date'] ? date('d M, Y h:i A', strtotime($row['order_date'])) : translate('Not found') }}</td>
            <td>
                {{$row['product_name'] ?? translate('Not found')}}
                <br>
                {{translate('Quantity') .'-'. $item?->orderDetails?->qty}}
            </td>
            <td>
                {{ $row['product_amount'] === null ? translate('Not found') : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $row['product_amount'])) }}
            </td>
            <td> {{ setCurrencySymbol(amount:usdToDefaultCurrency(amount: $item->amount ?? 0))}}</td>
            <td> {{ucwords(($item?->customer?->f_name ?? translate('Not found')) .' '. $item?->customer?->l_name)}}    </td>
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
            <td> {{ucwords($item?->order?->seller_is == 'seller' ? ($item?->order?->seller?->shop?->name ?? translate('Not found')) : translate('In-house'))}}</td>
            @endif
            <td> {{ucwords($item?->order?->delivery_type == 'self_delivery' ? (isset($item?->order?->deliveryMan) ? $item?->order?->deliveryMan?->f_name.' '.$item?->order?->deliveryMan?->l_name : translate('Not found')) : ($item?->order?->delivery_service_name ??translate('Not found')."\n".$item?->order?->third_party_delivery_tracking_id ?? translate('Not found')))}}    </td>
            <td> {{ ucfirst((string) $item->refund_reason) }}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
