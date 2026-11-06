<html>
<table>
    <thead>
        <tr>
            <th style="font-size: 18px">{{ $data['listHeading'] }}</th>
        </tr>
        <tr>

            <th>{{ $data['analyticsHeading'] . ' -' }}</th>
            <th></th>
            <th>
                {{ $data['totalLabel'] . ' - ' . count($data['categories']) }}
                @if ($data['title'] == 'category')
                    <br>
                    {{ translate('Active') . ' - ' . $data['active'] }}
                    <br>
                    {{ translate('Inactive') . ' - ' . $data['inactive'] }}
                @endif
            </th>
        </tr>
        <tr>
            <th>{{ translate('Search Criteria') . ' -' }}</th>
            <th></th>
            <th> {{ translate('Search Bar Content') . ' - ' . ($data['search'] ?? translate('N/A')) }}</th>
        </tr>
        <tr>
            <td> {{ translate('ID') }}</td>
            @if ($data['title'] == 'category')
                <td> {{ translate('Category Image') }}</td>
            @endif
            <td> {{ $data['nameHeading'] }}</td>
            @if ($data['title'] == 'sub_sub_category')
                <td> {{ translate('Sub Category Name') }}</td>
            @endif
            @if ($data['title'] == 'sub_category' || $data['title'] == 'sub_sub_category')
                <td> {{ translate('Category Name') }}</td>
            @endif
            @if ($data['title'] == 'category')
                <td> {{ translate('Sub Category') }}</td>
            @endif
            @if ($data['title'] == 'category' || $data['title'] == 'sub_category')
                <td> {{ translate('Sub Sub Category') }}</td>
            @endif
            @if (isset($data['category_wise_tax']) && $data['category_wise_tax'])
                <th>{{ translate('Tax rate') }}</th>
            @endif
            <td> {{ translate('Priority') }} </td>
            <td> {{ translate('Total Product') }}</td>
            @if ($data['title'] == 'category')
                <td> {{ translate('Home Category Status') }}</td>
            @endif
        </tr>

    </thead>
    <tbody>
        @foreach ($data['categories'] as $key => $item)
            <tr>
                <td> {{ $item['id'] }} </td>
                @if ($data['title'] == 'category')
                    <td style="height: 70px"></td>
                @endif
                <td> {{ $item['defaultName'] }}</td>

                @if ($data['title'] == 'sub_category' || $data['title'] == 'sub_sub_category')
                    <td> {{ $item?->parent?->defaultName ?? translate('Not found') }}</td>
                @endif

                @if ($data['title'] == 'sub_sub_category')
                    <td> {{ $item?->parent?->parent->defaultName ?? translate('Not found') }}</td>
                @endif

                @if ($data['title'] == 'category')
                    <td> {{ $item->sub_category_count }}</td>
                @endif

                @if ($data['title'] == 'category' || $data['title'] == 'sub_category')
                    <td> {{ $item->sub_sub_category_count }}</td>
                @endif

                @if (isset($data['category_wise_tax']) && $data['category_wise_tax'])
                    <td>
                        @forelse ($item?->taxVats as $key => $taxVatItem)
                            <div class="d-flex gap-1">
                                {{ $taxVatItem?->tax?->name }} :
                                <span>({{ $taxVatItem?->tax?->tax_rate ?? 0 }}%)</span>
                            </div>
                            <br>
                        @empty
                            <span>{{ translate('N/A') }}</span>
                        @endforelse
                    </td>
                @endif

                <td> {{ $item->priority }}</td>
                <td> {{ $item->product_count }}</td>
                @if ($data['title'] == 'category')
                    <td> {{ $item->home_status == 1 ? translate('Active') : translate('Inactive') }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

</html>
