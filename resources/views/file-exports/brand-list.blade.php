<html>
<table>
    <thead>
        <tr>
            <th style="font-size: 18px">{{ translate('Brand List') }}</th>
        </tr>
        <tr>

            <th>{{ translate('Brand Analytics') . ' ' . '-' }}</th>
            <th></th>
            <th>
                {{ translate('Total Brand') . ' ' . '-' . ' ' . count($data['brands']) }}
                <br>
                {{ translate('Active Brand') . ' ' . '-' . ' ' . $data['active'] }}
                <br>
                {{ translate('Inactive Brand') . ' ' . '-' . ' ' . $data['inactive'] }}
            </th>
        </tr>
        <tr>
            <th>{{ translate('Search Criteria') }}-</th>
            <th></th>
            <th> {{ translate('Search Bar Content') . ' ' . '-' . ' ' . $data['search'] ?? 'N/A' }}</th>
        </tr>
        <tr>
            <td> {{ translate('Serial') }} </td>
            <td> {{ translate('Brand Logo') }}</td>
            <td> {{ translate('Name') }}</td>
            <td> {{ translate('Total Product') }} </td>
            <td> {{ translate('Total Order') }} </td>
            <td> {{ translate('Status') }} </td>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['brands'] as $key => $item)
            <tr>
                <td> {{ ++$key }} </td>
                <td style="height: 70px"></td>
                <td> {{ $item['defaultName'] }}</td>
                <td> {{ $item->brand_all_products_count }}</td>
                <td> {{ $item['brand_product_order_details_count'] }}</td>
                <td> {{ translate($item->status == 1 ? 'active' : 'inactive') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</html>
