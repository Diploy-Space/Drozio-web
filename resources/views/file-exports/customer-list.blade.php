<html>
<table>
    <thead>
    <tr>
        <th style="font-size:18px">{{translate('Customer List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Customer Analytics').' '.'-' }}</th>
        <th></th>
        <th>
            {{translate('Total Customers').' '.'-'.' '.count($data['customers'])}}
        </th>
    </tr>
    <tr>
        <th>{{translate('Filter Criteria')}}-</th>
        <th></th>
        <th>
            {{ translate('Status') . ' - ' . ($data['status'] === '1' ? 'Active' : ($data['status'] === '0' ? 'Inactive' : 'All')) }}
            <br>
            {{ translate('Sort By') . ' - ' . (!empty($data['sortBy']) ? ($data['sortBy'] === 'order_amount' ? translate('Sort By Order Amount') : ($data['sortBy']  === 'asc' ? translate('Sort By Oldest') : translate('Sort By Newest'))) : 'N/A') }}
            <br>
            {{ translate('Choose first'). ' - ' . (!empty($data['chooseFirst']) ? $data['chooseFirst'] : 'N/A')}}
            <br>
            {{ translate('Search Bar Content'). ' - ' . (!empty($data['searchValue']) ? $data['searchValue'] : 'N/A')}}
            <br>
            {{translate('Order start date').' - '. (!empty($data['orderStartDate']) ?  $data['orderStartDate']->format('d F Y') : 'N/A') }}
            <br>
            {{translate('Order end date').' - '. (!empty($data['orderEndDate']) ?  $data['orderEndDate']->format('d F Y') : 'N/A') }}
            <br>
            {{translate('Joining start date').' - '. (!empty($data['joiningStartDate']) ?  $data['joiningStartDate']->format('d F Y') : 'N/A') }}
            <br>
            {{translate('Joining end date').' - '. (!empty($data['joiningEndDate']) ?  $data['joiningEndDate']->format('d F Y') : 'N/A') }}

        </th>
    </tr>
    <tr>
        <td>{{ translate('Serial') }}</td>
        <td>{{ translate('Customer Image') }}</td>
        <td>{{ translate('Name') }}</td>
        <td>{{ translate('Phone') }}</td>
        <td>{{ translate('Email') }}</td>
        <td>{{ translate('Date of Joining') }}</td>
        <td>{{ translate('Total Order') }}</td>
        <td>{{ translate('Status') }}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['customers'] as $key=>$item)
        <tr>
            <td> {{++$key}}    </td>
            <td style="height:80px"></td>
            <td>{{ ucwords(($item->f_name?? translate('Not found')).' '.$item->l_name) }}</td>
            <td>{{ (string)$item?->phone ?? translate('Not found') }}</td>
            <td>{{ $item->email }}</td>
            <td>{{ date('d M, Y ',strtotime($item->created_at)) }}</td>
            <td>{{ $item->orders->count() ?? 0 }}</td>
            <td>{{ translate($item->is_active == 1 ? 'active' : 'inactive') }}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
