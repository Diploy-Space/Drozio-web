<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate('Vendor List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Vendor Analytics') }} -</th>
                <th></th>
                <th>
                        {{translate('Total Vendor')}} - {{count($data['vendors'])}}
                    <br>
                        {{translate('Active Vendors')}} - {{$data['active']}}
                    <br>
                        {{translate('Inactive Vendors')}} - {{$data['inactive']}}
                </th>
            </tr>
            <tr>
                <th>{{translate('Search Criteria')}}-</th>
                <th></th>
                <th>  {{translate('Search Bar Content')}} - {{!empty($data['search']) ? $data['search'] : 'N/A'}}</th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}</td>
                <td> {{translate('Store Logo')}}</td>
                <td> {{translate('Store Name')}}</td>
                <td> {{translate('Vendor Name')}}</td>
                <td> {{translate('Phone')}}	</td>
                <td> {{translate('Email')}}	</td>
                <td> {{translate('Joined At')}}	</td>
                <td> {{translate('Total Products')}}	</td>
                <td> {{translate('Total Order')}} </td>
                <td> {{translate('Status')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['vendors'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td style="height: 70px"></td>
                    <td> {{ucwords($item?->shop->name)}}</td>
                    <td> {{ucwords($item->f_name.' '.$item->l_name)}}</td>
                    <td> {{$item?->phone ?? translate('Not found')}}</td>
                    <td> {{ucwords($item->email)}}</td>
                    <td> {{date('d M, Y h:i A',strtotime($item->created_at))}}</td>
                    <td> {{$item->product_count}}</td>
                    <td> {{$item->orders_count}}</td>
                    <td> {{translate($item->status == 'approved' ? 'active' : 'inactive')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
