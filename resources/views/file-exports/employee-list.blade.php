@php($permissionLabels = \App\Enums\GlobalConstant::employeeRolePermissionLabels())
<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate('Employee List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Employee Analytics') .' '.'-'}}</th>
                <th></th>
                <th>
                        {{translate('Total Employee').' '.'-'.' '.count($data['employees'])}}
                    <br>
                        {{translate('Active Employee').' '.'-'.' '.$data['active']}}
                    <br>
                        {{translate('Inactive Employee').' '.'-'.' '.$data['inactive']}}
                </th>
            </tr>
            <tr>
                <th>{{translate('Search Criteria')}}-</th>
                <th></th>
                <th>  {{translate('Search Bar Content').' '.'-'.' '.$data['search'] ?? 'N/A'}}</th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}	</td>
                <td> {{translate('Employee Image')}}</td>
                <td> {{translate('Name')}}</td>
                <td> {{translate('Phone')}}	</td>
                <td> {{translate('Email')}}	</td>
                <td> {{translate('Role')}}	</td>
                <td> {{translate('Accesses')}} </td>
                <td> {{translate('Date of Joining')}} </td>
                <td> {{translate('Status')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['employees'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td style="height: 70px"></td>
                    <td> {{ucwords($item->name)}}</td>
                    <td> {{$item->phone}}</td>
                    <td> {{ucwords($item->email)}}</td>
                    <td> {{ucwords($item?->role?->name)}}</td>
                    <td>
                        @foreach ((array) json_decode($item?->role?->module_access ?? '', true) as $value)
                            @isset($permissionLabels[$value])
                                {{ translate($permissionLabels[$value]['group']).' - '.translate($permissionLabels[$value]['label']) }}
                                <br>
                            @endisset
                        @endforeach
                    </td>
                    <td> {{date('d M, Y h:i A',strtotime($item->created_at))}}</td>
                    <td> {{translate($item->status == 1 ? 'active' : 'inactive')}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
