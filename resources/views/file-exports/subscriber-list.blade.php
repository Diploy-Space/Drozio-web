<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{translate('Subscriber List')}}</th>
            </tr>
            <tr>
                <th>{{ translate('Subscriber analytics').' '.'-' }}</th>
                <th>
                    {{translate('Total subscriber').' '.'-'.' '.count($data['subscription'])}}
                </th>
            </tr>
            <tr>
                <th>{{ translate('Filter Criteria').' '.'-' }}</th>
                <th>
                    {{ translate('Sort By') . ' - ' . (!empty($data['sortBy']) ?  ($data['sortBy']  === 'asc' ? translate('Sort By Oldest') : translate('Sort By Newest')) : 'N/A') }}
                    <br>
                    {{ translate('Choose first'). ' - ' . (!empty($data['chooseFirst']) ? $data['chooseFirst'] : 'N/A')}}
                    <br>
                    {{ translate('Search Bar Content'). ' - ' . (!empty($data['search']) ? $data['search'] : 'N/A')}}
                    <br>
                    {{translate('Start Date').' - '. (!empty($data['startDate']) ?  $data['startDate']->format('d F Y') : 'N/A') }}
                    <br>
                    {{translate('End Date').' - '. (!empty($data['endDate']) ?  $data['endDate']->format('d F Y') : 'N/A') }}
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}	</td>
                <td> {{translate('Email ID')}}</td>
                <td> {{translate('Subscription Date')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['subscription'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td>{{$item->email}}</td>
                    <td> {{date('d M, Y',strtotime($item->created_at))}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
