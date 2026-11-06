<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate($data['type'] == 'earn' ? 'delivery_Man_Earnings' : 'delivery_Man_Order_List')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Delivery Man Information').' '.'-' }}</th>
        <th></th>
        <th>
            {{translate('Name').' '.'-'.' '.$data['delivery_man']['f_name'].' '.$data['delivery_man']['l_name']}}
            <br>
            {{translate('Rating').' '.'-'.' '.(isset($data['delivery_man']?->rating[0]?->average) ? number_format($data['delivery_man']?->rating[0]?->average, 1) : 0) }}
            <br>
            {{translate('Total Order').' '.'-'.' '.$data['totalOrders']}}
        </th>
    </tr>


    <tr>
        @if ($data['type'] == 'earn')
            <th>{{translate('Earning Analytics')}}-</th>
            <th></th>
            <th> {{translate('Total Earning').' '.'-'.' '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['total_earn'] ?? 0)) }} </th>
            <th></th>
            <th> {{translate('Withdrawable Balance').' '.'-'.' '. setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['withdrawable_balance'] ?? 0)) }}</th>
            <th></th>
            <th> {{translate('Already Withdrawn').' '.'-'.' '. setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['delivery_man']?->wallet?->total_withdraw ?? 0)) }}</th>
        @else
            <th>{{translate('Search Criteria').' '.'-'}}</th>
            <th></th>
            <th>  {{translate('Search Bar Content').' '.'-'.' '.!empty($data['search']) ? $data['search'] : 'N/A'}}</th>
        @endif
    </tr>
    <tr>
        <td> {{translate('Serial')}}    </td>
        <td> {{translate('Order ID')}}</td>
        <td> {{translate('Module')}}</td>
        <td> {{translate('Order Date')}}</td>
        @if ($data['type'] != 'earn')
        <td> {{translate('Total Item')}}</td>
        @endif
        @if ($data['type'] == 'earn')
            <td> {{translate('Earnings')}}</td>
        @endif
        <td> {{translate($data['type'] == 'earn' ? 'earning_status' : 'payment_status')}}</td>
        @if ($data['type'] == 'earn')
            <td> {{translate('Payment Method')}}</td>
        @endif
        <td> {{translate('Order Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['orders'] as $key=>$item)
        <tr>
            <td> {{++$key}}    </td>
            <td> {{$item->id}} </td>
            <td> {{$item->module_label}} </td>
            <td> {{ date_format( $item->created_at, 'd M ,Y, h:i:s A') }} </td>
            @if ($data['type'] != 'earn')
            <td> {{$item->total_qty}} </td>
            @endif
            @if ($data['type'] == 'earn')
                <td> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item?->deliveryman_charge ?? 0)) }}</td>
            @endif
            @if ($data['type'] == 'earn')
                <td> {{translate($item->order_status == 'delivered' && $item->payment_status == 'paid' ? translate('Received') : translate('Not received'))}} </td>
            @else
                <td> {{translate($item->payment_status)}} </td>
            @endif
            @if ($data['type'] == 'earn')
                <td>{{translate($item->payment_method)}}</td>
            @endif
            <td> {{translate($item->order_status)}}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
