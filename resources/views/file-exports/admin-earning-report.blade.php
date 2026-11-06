<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Admin Earning Report')}}</th>
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
        <td> {{translate('Duration')}}    </td>
        <td> {{translate('In-House Earning')}}    </td>
        <td> {{translate('Commission Earning')}}</td>
        <td> {{translate('Earn From Shipping')}}</td>
        <td> {{translate('Delivery man incentive')}}</td>
        <td> {{translate('Discount Given')}}</td>
        <td> {{translate('VAT/TAX')}}</td>
        <td> {{translate('Refund Given')}}</td>
        <td> {{translate('Total Earning')}}</td>
    </tr>
    </thead>
    <tbody>
        @foreach ($data['inhouseEarn'] as $key=>$earning)
            <tr>
                <td>{{$loop->iteration++}} </td>
                <td>{{ $earning['duration'] }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['in_house_earning']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['commission_earning']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['earn_from_shipping']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['discount_given']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['vat_tax']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['refund_given']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $earning['total_earning']), currencyCode: getCurrencyCode()) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</html>
