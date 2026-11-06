<html>
<table>
    <thead>
    <tr>
        <th>{{ translate('Product Stock Report List') }}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria').' -' }}</th>
        <th></th>
        <th>
            {{ translate('Search Bar Content').' - '.($data['search'] ?? 'N/A') }}
            <br>
            {{ translate('Store').' - '.($data['storeName'] ?? translate('All')) }}
            <br>
            {{ translate('Category').' - '.($data['categoryName'] ?? translate('All')) }}
            <br>
            {{ translate('Stock Sort By').' - '.$sortLabel }}
            <br>
        </th>
    </tr>
    <tr>
        <td>{{ translate('Serial') }}</td>
        <td>{{ translate('Product Name') }}</td>
        <td>{{ translate('Last Updated Stock') }}</td>
        <td>{{ translate('Current Stock') }}</td>
        <td>{{ translate('Status') }}</td>
    </tr>
    </thead>
    <tbody>
    @foreach($data['products'] as $product)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product['name'] }}</td>
            <td>{{ date('d M Y, h:i:s a', $product['updated_at'] ? strtotime($product['updated_at']) : null) }}</td>
            <td>{{ $product['current_stock'] }}</td>
            <td>{{ $stockStatusLabels[$product['stock_status']] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
