<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate('Delivery Man Withdraw Request List')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Withdraw Request Analytics') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '. ($data['searchValue'] ?? 'N/A')}}
            <br>
            {{translate('Total Request').' '.'-'.' '.count($data['withdraw_request'])}}
            <br>
            {{translate('Pending Request').' '.'-'.' '.$data['pending_request']}}
            <br>
            {{translate('Approved Request').' '.'-'.' '.$data['approved_request']}}
            <br>
            {{translate('Denied Request').' '.'-'.' '.$data['denied_request']}}
        </th>
    </tr>
    <tr>
        <th>{{translate('Filter Criteria')}}-</th>
        <th></th>
        <th> {{ucwords($data['filter'] ?? 'all')}}</th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}    </td>
        <td> {{translate('First Name')}}</td>
        <td> {{translate('Last Name')}}</td>
        <td> {{translate('Request Time')}}</td>
        <td> {{translate('Amount')}}</td>
        <td> {{translate('Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['withdraw_request'] as $key=>$item)
        <tr>
            <td> {{++$key}}  </td>
            <td> {{ ucwords($item?->deliveryMan?->f_name ?? translate('Not found')) }}</td>
            <td> {{ ucwords($item?->deliveryMan?->l_name ?? translate('Not found')) }}</td>
            <td> {{ date_format( $item->created_at, 'd M ,Y, h:i:s A') }} </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['amount'] ?? 0))}} </td>
            <td> {{ucwords($item->approved==0 ? 'pending' : ($item->approved==1 ? 'approved' : 'denied'))}}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
