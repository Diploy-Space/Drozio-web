<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Order Report List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '. ($data['search'] ?? 'N/A')}}
            <br>
            {{translate('Store')}} - {{ucwords($data['vendor'] != 'all' && $data['vendor'] !='inhouse' ? $data['vendor']?->shop->name : ( $data['vendor'] ?? 'all' ))}}
            <br>
            {{translate('Date Type').' '.'-'.' '.translate($data['dateType'])}}
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
        <td> {{translate('Order ID')}}    </td>
        <th>{{translate('Total Amount')}}</th>
        <th>{{translate('Due Amount')}}</th>
        <th>{{translate('Return Amount')}}</th>
        <td> {{translate('Product Discount')}}</td>
        <td> {{translate('Coupon Discount')}}</td>
        <td> {{translate('Referral Discount')}}</td>
        <td> {{translate('Shipping Charge')}}</td>
        <td> {{translate('VAT/TAX')}}</td>
        <td> {{translate('Commission')}}</td>
        <td> {{translate('Delivery man incentive')}}</td>
        <td> {{translate('Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['orders'] as $key=>$item)
        <tr>
            <td> {{++$key}} </td>
            <td> {{$item['id']}} </td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->order_amount + $item->edit_due_amount - $item->edit_return_amount)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->edit_due_amount ?? 0)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->edit_return_amount ?? 0)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->details_sum_discount ?? 0)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->discount_amount ?? 0)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->refer_and_earn_discount ?? 0)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->shipping_cost - ($item->extra_discount_type == 'free_shipping_over_order_amount' ? $item->extra_discount : 0))) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item?->total_tax_amount ?? $item?->details_sum_tax)) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->admin_commission ?? 0)) }}</td>
            <td>{{ ($item->delivery_type=='self_delivery' && $item->delivery_man_id) ? setCurrencySymbol(amount: usdToDefaultCurrency(amount:$item->deliveryman_charge ?? 0)) : setCurrencySymbol(amount: usdToDefaultCurrency(amount: 0)) }}</td>
            <td>{{translate($item['order_status'])}}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
