<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate('Employee Role List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Employee Role Analytics') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content')}} - {{!empty($data['searchValue']) ? $data['searchValue'] : 'N/A'}}
            <br>
            {{translate('Active Employee Role').' '.'-'.' '.$data['active']}}
            <br>
            {{translate('Inactive Employee Role').' '.'-'.' '.$data['inActive']}}
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}	</td>
        <td> {{translate('Role Name')}}</td>
        <td> {{translate('Employees')}}</td>
        <td> {{translate('Module access')}}</td>
        <td> {{translate('Created At')}}</td>
        <td> {{translate('Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['roles'] as $key=>$item)
        <tr>
            <td> {{++$key}}	</td>
            <td>{{ucwords($item['name'])}}</td>
            <td>{{ $item->employees_count }}</td>
            <td>
                @foreach($data['rolePermissions'][$item['id']] ?? [] as $groupLabel => $groupPermissions)
                    {{ translate($groupLabel) }}:
                    @foreach($groupPermissions as $groupPermission)
                        {{ translate($groupPermission) }}{{ $loop->last ? '' : ',' }}
                    @endforeach
                    <br>
                @endforeach
            </td>
            <td> {{date('d M, Y h:i A',strtotime($item->created_at))}}</td>
            <td>{{translate($item['status'] ==1 ? 'active' : 'inactive')}}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
