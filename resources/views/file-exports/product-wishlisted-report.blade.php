<html>
    <table>
        <thead>
            <tr>
                <th>{{translate('Product Wishlisted Report List')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Filter Criteria').' '.'-' }}</th>
                <th></th>
                <th>

                    {{translate('Search Bar Content')}} - {{ $data['search'] ?? 'N/A'}}
                    <br>
                    {{translate('Store')}} - {{ucwords($data['seller'] != 'all' && $data['seller'] !='inhouse' ? $data['seller']?->shop->name : translate($data['seller'] ?? 'all' ))}}
                    <br>
                    {{translate('Wishlist Sort By').' '.'-'.' '.translate($data['sort'] == 'ASC' ? 'low_to_high' : 'high_to_low')}}
                    <br>
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}</td>
                <td> {{translate('Product Name')}}</td>
                <td> {{translate('Date')}}	</td>
                <td> {{translate('Total In Wishlist')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['products'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td> {{$item['name']}}	</td>
                    <td> {{ date('d M, Y', $item['created_at']  ? strtotime($item['created_at']) : null) }}	</td>
                    <td> {{ $item->wish_list_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
