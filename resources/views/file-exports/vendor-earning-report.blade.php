<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Vendor Earning Report')}}</th>
    </tr>
    <tr>

        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
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
        <td> {{translate('Vendor Information')}}    </td>
        <td> {{translate('Earn From Order')}}    </td>
        <td> {{translate('Earn From Shipping')}}</td>
        <td> {{translate('Delivery man incentive')}}</td>
        <td> {{translate('Commission Given')}}</td>
        <td> {{translate('Discount Given')}}</td>
        <td> {{translate('Tax Collected')}}</td>
        <td> {{translate('Refund Given')}}</td>
        <td> {{translate('Total Earning')}}</td>
    </tr>
</thead>
<tbody>
   @foreach($data['vendorEarnTable'] as $key=>$seller_earn)
        <tr>
            <td>{{$loop->iteration++}}</td>
            <td>{{ $seller_earn['vendor_info'] }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['earn_from_order']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['earn_from_shipping']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['commission_given']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['discount_given']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['tax_collected']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['refund_given']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $seller_earn['total_earning']), currencyCode: getCurrencyCode()) }}</td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
