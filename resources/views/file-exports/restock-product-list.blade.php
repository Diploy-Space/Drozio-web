<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{(translate('Restock product list'))}}</th>
            </tr>
            <tr>
                <th>{{ translate('Restock Analytics').' '.'-' }}</th>
                <th></th>
                <th>
                    {{translate('Total Restock Request').' '.'-'.' '.count($data['products'])}}
                </th>
            </tr>
            <tr>
                <th>
                    {{ translate('Filter Criteria').' - ' }}
                </th>
                <th></th>
                <th>
                    {{translate('Category').' - '. ($data['category'] != 'all' ? $data['category']['name'] : $data['category'])  }}
                    <br>
                    {{translate('Subcategory').' - '. ($data['subCategory'] != 'all' ? $data['subCategory']['name'] : $data['subCategory'])  }}
                    <br>
                    {{translate('Brand').' - '. ($data['brand'] != 'all' ? $data['brand']['name'] : $data['brand'])  }}
                    <br>
                    {{translate('Search Bar Content').' - '. (!empty($data['searchValue']) ?  ucwords($data['searchValue']) : 'N/A') }}
                    <br>
                    {{translate('Start Date').' - '. (!empty($data['startDate']) ?  $data['startDate']->format('d F Y') : 'N/A') }}
                    <br>
                    {{translate('End Date').' - '. (!empty($data['endDate']) ?  $data['endDate']->format('d F Y') : 'N/A') }}
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}</td>
                <td> {{translate('Product Image')}}	</td>
                <td> {{translate('Image URL')}}	</td>
                <td> {{translate('Product Name')}}	</td>
                <td> {{translate('Selling price')}}</td>
                <td> {{translate('Variant')}}</td>
                <td> {{translate('Last request date')}}</td>
                <td> {{translate('Number of request')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['products'] as $key=>$item)
                <tr>
                    <td> {{++$key}}	</td>
                    <td style="height: 200px"></td>
                    <td>{{dynamicStorage(path: 'storage/app/public/product/thumbnail/'.$item?->product?->thumbnail)}}</td>
                    <td> {{$item?->product?->name}}</td>
                    <td>{{$item?->product?->unit_price}}</td>
                    <td>{{ $item['variant'] ?? translate('No variation') }}</td>
                    <td>{{ $item->updated_at->format('d F Y, h:i A')}}</td>
                    <td>{{ $item?->restockProductCustomers?->count() ?? 0}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
