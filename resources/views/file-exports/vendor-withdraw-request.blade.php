<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Vendor Withdraw Request')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Vendor Withdraw Analytics') .' '.'-'}}</th>
        <th></th>
        <th>
            @if(isset($data['vendor']))
                {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
                <br>
            @endif
            {{translate('Total Withdraw Request').' '.'-'.' '.count($data['withdraw_request'])}}
            <br>
            @if($data['filter']=='all')
                {{translate('Total Pending').' '.'-'.' '.$data['pending']}}
                <br>
                {{translate('Total Approved').' '.'-'.' '.$data['approved']}}
                <br>
                {{translate('Total Denied').' '.'-'.' '.$data['denied']}}
            @endif
        </th>
    </tr>
    @php($statusLabels = [
        'all' => translate('All'),
        'pending' => translate('Pending'),
        'approved' => translate('Approved'),
        'denied' => translate('Denied'),
    ])
    <tr>
        <th>{{translate('Filter Criteria')}}-</th>
        <th></th>
        <th>
            {{ $statusLabels[$data['filter']] ?? $statusLabels['all'] }}
            @if(!empty($data['searchValue']))
                <br>
                {{translate('Search').' - '.$data['searchValue']}}
            @endif
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}    </td>
        <td> {{translate('Amount')}}</td>
        @if(isset($data['data-from']) && $data['data-from'] == 'admin')
        <td> {{translate('Name')}}</td>
        @endif
        <td> {{translate('Request Time')}}</td>
        <td> {{translate('Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['withdraw_request'] as $key=>$item)
        <tr>
            <td> {{++$key}}    </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['amount'] ?? 0))}} </td>
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
            <td>{{ ucwords(($item?->seller?->f_name ?? translate('Not found')) . ' ' . $item?->seller?->l_name) }}</td>
            @endif
            <td> {{ date_format( $item->created_at, 'd M ,Y, h:i:s A') }} </td>
            <td> {{ucwords($item->approved==0 ? 'pending' : ($item->approved==1 ? 'approved' : 'denied'))}}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
