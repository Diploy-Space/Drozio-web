<html>
    <table>
        <thead>
            <tr>
                <th>{{translate('Product Stock Report List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Filter Criteria') }} -</th>
                <th></th>
                <th>

                    {{translate('Search Bar Content')}} - {{ $data['search'] ?? 'N/A'}}
                    <br>
                    {{translate('Store')}} - {{ucwords($data['seller'] != 'all' && $data['seller'] !='inhouse' ? $data['seller']?->shop->name : translate($data['seller'] ?? 'all' ))}}
                    <br>
                    {{translate('Category')}} - {{ucwords($data['category'] != 'all' ? $data['category']['defaultName'] : translate($data['category'] ?? 'all' ))}}
                    <br>
                    {{translate('Stock Sort By')}} - {{translate($data['sort'] == 'ASC' ? 'low_to_high' : 'high_to_low')}}
                    <br>
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}</td>
                <td> {{translate('Product Name')}}</td>
                <td> {{translate('Last Updated Stock')}}	</td>
                <td> {{translate('Current Stock')}}</td>
                <td> {{translate('Status')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['products'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td> {{$item['name']}}	</td>
                    <td> {{ date('d M Y, h:i:s a', $item['updated_at'] ? strtotime($item['updated_at']) : null) }}	</td>
                    <td> {{$item['current_stock']}}</td>
                    <td>
                        @if($item['current_stock'] >= $data['stock_limit'])
                             {{translate('In Stock')}}
                        @elseif($item['current_stock']  <= 0)
                            {{translate('Out of Stock')}}
                        @elseif($item['current_stock'] < $data['stock_limit'])
                            {{translate('Soon Stock Out')}}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
