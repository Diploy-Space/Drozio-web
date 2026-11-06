<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate('Delivery Man List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Delivery Man Analytics') .' '.'-'}}</th>
                <th></th>
                <th>
                    @if(isset($data['vendor']))
                        {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
                        <br>
                    @endif
                        {{translate('Total Delivery Man').' '.'-'.' '.count($data['delivery_men'])}}
                    <br>
                        {{translate('Active Delivery Man').' '.'-'.' '.$data['active']}}
                    <br>
                        {{translate('Inactive Delivery man').' '.'-'.' '.$data['inactive']}}
                </th>
            </tr>
            <tr>
                <th>{{translate('Search Criteria')}}-</th>
                <th></th>
                <th>  {{translate('Search Bar Content').' '.'-'.' '.!empty($data['search']) ? $data['search'] : 'N/A'}}</th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}	</td>
                <td> {{translate('Delivery Man Image')}}</td>
                <td> {{translate('First Name')}}</td>
                <td> {{translate('Last Name')}}</td>
                <td> {{translate('Phone')}}	</td>
                <td> {{translate('Email')}}	</td>
                <td> {{translate('Identity Number')}}	</td>
                <td> {{translate('Total Order')}} </td>
                <td> {{translate('Rating')}} </td>
                <td> {{translate('Status')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['delivery_men'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td style="height: 70px"></td>
                    <td> {{ucwords($item->f_name)}}</td>
                    <td> {{ucwords($item->l_name)}}</td>
                    <td> {{$item->phone}}</td>
                    <td> {{ucwords($item->email)}}</td>
                    <td> {{ucwords($item?->identity_number)}}</td>
                    <td> {{$item->orders_count}}</td>
                    <td> {{isset($item->rating[0]->average) ? number_format($item->rating[0]->average, 1, '.', ' ') : 0 }}</td>
                    <td> {{translate($item->is_active == 1 ? 'active' : 'inactive')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
