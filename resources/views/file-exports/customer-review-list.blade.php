<html>
    <table>
        <thead>
            <tr>
                <th style="font-size:18px">{{translate('Customer Reviews')}}</th>
            </tr>
            <tr>

                <th>{{ translate('Search Criteria').' '.'-' }}</th>
                <th></th>
                <th>
                    {{translate('Search Bar Content').' '.'-'.' '.!empty($data['key']) ?  ucwords($data['key']) : 'N/A'}}
                    @if(isset($data['vendor']))
                        <br>
                        {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
                    @endif
                    <br>
                        {{translate('Product').' '.'-'.' '.ucwords($data['product_name'] == 'all_products' ? translate('All Products') : $data['product_name'])}}
                    <br>
                        {{translate('Customer').' '.'-'.' '.ucwords($data['customer_name'] == 'all_customers' ? translate('All Customers') : $data['customer_name']['f_name'].' '.$data['customer_name']['l_name'])}}
                    <br>
                         {{translate('Status').' '.'-'.' '.translate(!is_null($data['status']) ? ($data['status'] == 1 ? 'active' : 'inactive') : 'all_status')}}
                    <br>
                        {{translate('From').' '.'-'.' '.($data['from'] ?   date('d M, Y',strtotime($data['from'])) : '') }}
                    <br>
                        {{translate('To').' '.'-'.' '. ($data['to'] ? date('d M, Y',strtotime($data['to'])) : '')}}
                    <br>
                </th>
            </tr>
            <tr>
                <td> {{translate('Serial')}}	</td>
                <td> {{translate('Product Name')}}	</td>
                <td> {{translate('Customer Name')}}	</td>
                @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                <td> {{translate('Store Name')}}	</td>
                @endif
                <td> {{translate('Item Price')}}	</td>
                <td> {{translate('Rating')}}</td>
                <td> {{translate('Review')}}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['reviews'] as $key=>$item)
                <tr >
                    <td > {{++$key}}	</td>
                    <td> {{$item?->product?->name ?? translate('Not found')}}	</td>
                    <td>{{ucwords(($item->customer?->f_name ?? translate('Not found')).' '.$item->customer?->l_name)}}</td>
                    @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                    <td> {{ucwords($item?->product?->seller?->shop->name ?? translate('Not found'))}}	</td>
                    @endif
                    <td> {{$item?->product ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item?->product->unit_price ?? 0)) : translate('Not found')}}</td>
                    <td> {{$item?->rating}}</td>
                    <td> {{$item?->comment}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
