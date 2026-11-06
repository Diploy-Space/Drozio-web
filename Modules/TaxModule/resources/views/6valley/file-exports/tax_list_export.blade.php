<div class="row">
    <div class="col-lg-12 text-center ">
        <h1>{{ translate('Tax List') }}</h1>
    </div>
    <div class="col-lg-12">
        <table>
            <thead>
            <tr>
                <th>{{ translate('Filter Criteria') }}</th>
                <th></th>
                <th>
                    {{ translate('Search Bar Content')  }}: {{ $data['search'] ?? translate('N/A') }}
                </th>
                <th></th>
            </tr>
            <tr>
                <th class="border-0">{{ translate('Serial') }}</th>
                <th class="border-0">{{ translate('Tax name') }}</th>
                <th class="border-0">{{ translate('Tax rate') }}</th>
                <th class="border-0 text-end">{{ translate('Status') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data['data'] as $key => $taxVat)
                <tr>
                    <td>{{ $loop->index + 1 }}</td>
                    <td>{{ $taxVat->name }}</td>
                    <td>{{ $taxVat->tax_rate }} %</td>
                    <td>{{ $taxVat->is_active == 1 ? translate('Active') : translate('Inactive')  }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
