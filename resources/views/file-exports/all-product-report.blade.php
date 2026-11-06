<html>
<table>
    <thead>
    <tr>
        <th>{{ translate('Product Report List') }}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria').' -' }}</th>
        <th></th>
        <th>
            {{ translate('Search Bar Content').' - '.($data['search'] ?? 'N/A') }}
            <br>
            {{ translate('Store').' - '.($data['storeName'] ?? translate('All')) }}
            <br>
            {{ translate('Date Type').' - '.$dateTypeLabel }}
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
        <td>{{ translate('Product Name') }}</td>
        <td>{{ translate('Product Unit Price') }}</td>
        <td>{{ translate('Total Amount Sold') }}</td>
        <td>{{ translate('Total Quantity Sold') }}</td>
        <td>{{ translate('Average Product Value') }}</td>
        <td>{{ translate('Current Stock Amount') }}</td>
        <td>{{ translate('Average Ratings') }}</td>
    </tr>
    </thead>
    <tbody>
    @foreach($data['products'] as $product)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product['name'] }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['unit_price'])) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['total_sold_amount'])) }}</td>
            <td>{{ $product['total_sold_quantity'] }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product['average_sold_price'])) }}</td>
            <td>{{ $product['is_digital'] ? ($product['is_active'] ? translate('Available') : translate('Not available')) : $product['current_stock'] }}</td>
            <td>{{ $product['average_rating'] > 0 ? number_format($product['average_rating'], 2) : 0 }} ( {{ $product['review_count'] }} )</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
