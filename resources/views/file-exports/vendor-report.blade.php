<html>
<table>
    <thead>
    <tr>
        <th>{{ translate('Vendor Report') }}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria').' -' }}</th>
        <th></th>
        <th>
            {{ translate('Search Bar Content').' - '.($data['search'] ?? 'N/A') }}
            <br>
            {{ translate('Store').' - '.($data['shopName'] ?? translate('All')) }}
            <br>
            {{ translate('Date Type').' - '.translate($data['dateType']) }}
            <br>
            @if($data['from'] && $data['to'])
                {{ translate('From').' - '.date('d M, Y', strtotime($data['from'])) }}
                <br>
                {{ translate('To').' - '.date('d M, Y', strtotime($data['to'])) }}
                <br>
            @endif
        </th>
    </tr>
    <tr>
        <td>{{ translate('Serial') }}</td>
        <td>{{ translate('Vendor Information') }}</td>
        <td>{{ translate('Total Order') }}</td>
        <td>{{ translate('Total Revenue') }}</td>
        <td>{{ translate('Commission') }}</td>
        <td>{{ translate('Refund Rate') }}</td>
    </tr>
    </thead>
    <tbody>
    @foreach($data['vendorSaleRows'] as $vendorSale)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                {{ $vendorSale['shop_name'] ?? translate('Not found') }}
                <br>
                {{ $vendorSale['vendor_name'] }}
            </td>
            <td>{{ $vendorSale['total_order_count'] }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSale['total_order_amount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $vendorSale['total_admin_commission']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ $vendorSale['refund_rate'] === null ? '0%' : number_format($vendorSale['refund_rate'], 2).'%' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
