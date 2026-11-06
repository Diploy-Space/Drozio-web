<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Product Report List')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>

            {{translate('Search Bar Content').' '.'-'.' '. $data['search'] ?? 'N/A'}}
            <br>
            {{translate('Store').' '.'-'.' '.ucwords($data['seller'] != 'all' && $data['seller'] !='inhouse' ? $data['seller']?->shop->name : translate($data['seller'] ?? 'all' ))}}
            <br>
            {{translate('Date Type').' '.'-'.' '.translate($data['date_type'])}}
            <br>
            @if($data['from'] && $data['to'])
                {{translate('From').' '.'-'.' '.date('d M, Y',strtotime($data['from']))}}
                <br>
                {{translate('To').' '.'-'.' '.date('d M, Y',strtotime($data['to']))}}
                <br>
            @endif
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}</td>
        <td> {{translate('Product Name')}}    </td>
        <td> {{translate('Product Unit Price')}}    </td>
        <td> {{translate('Total Amount Sold')}}</td>
        <td> {{translate('Total Quantity Sold')}}</td>
        <td> {{translate('Average Product Value')}}</td>
        <td> {{translate('Current Stock Amount')}}</td>
        <td> {{translate('Average Ratings')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['products'] as $key=>$item)
        <tr>
            <td> {{++$key}}    </td>
            <td> {{$item['name']}}    </td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->unit_price ?? 0 )) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount:  $item->orderDetails[0]->total_sold_amount ?? 0)) }}</td>
            <td>{{ $item->orderDetails[0]->product_quantity ?? 0 }}</td>
            <td>
                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount:
                    ( $item->orderDetails[0]->total_sold_amount ?? 0) /
                    ( $item->orderDetails[0]->product_quantity ?? 1)))
                }}
            </td>
            <td>
                {{ $item->product_type == 'digital' ? ($item->status==1 ? translate('Available') : translate('Not available')) : $item->current_stock }}
            </td>
            <td>{{$item?->rating && count($item->rating) > 0 ?  number_format($item->rating[0]->average,2) : 0}}
                ( {{$item->reviews->count()}} )
            </td>
        </tr>
        @endforeach
</tbody>
</table>
</html>
