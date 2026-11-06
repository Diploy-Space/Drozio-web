<html>
<table>
    <thead>
    <tr>
        <th>{{ translate('Inhouse product sale Report') }}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria').' -' }}</th>
        <th></th>
        <th>{{ translate('Category').' - '.($data['categoryName'] ?? translate('All')) }}</th>
    </tr>
    <tr>
        <td>{{ translate('Serial') }}</td>
        <td>{{ translate('Product Name') }}</td>
        <td>{{ translate('Total Sale') }}</td>
    </tr>
    </thead>
    <tbody>
    @foreach($data['products'] as $product)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product['name'] }}</td>
            <td>{{ (int) $product['total_sold_quantity'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
