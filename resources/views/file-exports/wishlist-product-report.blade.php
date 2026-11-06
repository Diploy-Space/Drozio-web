<html>
<table>
    <thead>
    <tr>
        <th>{{ translate('Product Wishlisted Report List') }}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria').' -' }}</th>
        <th></th>
        <th>
            {{ translate('Search Bar Content').' - '.($data['search'] ?? 'N/A') }}
            <br>
            {{ translate('Store').' - '.($data['storeName'] ?? translate('All')) }}
            <br>
            {{ translate('Wishlist Sort By').' - '.$sortLabel }}
            <br>
        </th>
    </tr>
    <tr>
        <td>{{ translate('Serial') }}</td>
        <td>{{ translate('Product Name') }}</td>
        <td>{{ translate('Date') }}</td>
        <td>{{ translate('Total In Wishlist') }}</td>
    </tr>
    </thead>
    <tbody>
    @foreach($data['products'] as $product)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product['name'] }}</td>
            <td>{{ date('d M, Y', $product['created_at'] ? strtotime($product['created_at']) : null) }}</td>
            <td>{{ $product['wishlist_count'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
