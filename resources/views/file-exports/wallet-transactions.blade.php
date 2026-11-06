@php($transactionTypes = $data['transaction_types'] ?? [])
@php($typeLabel = fn ($type) => $transactionTypes[$type] ?? ucfirst(str_replace('_', ' ', (string) $type)))
@php($customer = $data['customer'])
<html>
    <table>
        <thead>
            <tr>
                <th style="font-size:18px">{{ translate('Customer Wallet Report') }}</th>
            </tr>
            <tr>
                <th>{{ translate('Search Criteria').' -' }}</th>
                <th></th>
                <th>
                    {{ translate('Customer').' - '.($customer === 'all_customers' ? translate('All Customers') : ucwords($customer['f_name'].' '.$customer['l_name'])) }}
                    <br>
                    {{ translate('Transaction Type').' - '.(!empty($data['transaction_type']) ? $typeLabel($data['transaction_type']) : translate('All')) }}
                    <br>
                    {{ translate('From').' - '.($data['from'] ? date('d M Y', strtotime($data['from'])) : translate('All time')) }}
                    <br>
                    {{ translate('To').' - '.($data['to'] ? date('d M Y', strtotime($data['to'])) : translate('All time')) }}
                </th>
            </tr>
            <tr>
                <th>{{ translate('Search Summary').' -' }}</th>
                <th></th>
                <th>{{ translate('Balance').' - '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['balance'] ?? 0)) }}</th>
                <th></th>
                <th>{{ translate('Total Credit').' - '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['credit'] ?? 0)) }}</th>
                <th></th>
                <th>{{ translate('Total Debit').' - '.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['debit'] ?? 0)) }}</th>
            </tr>
            <tr>
                <td>{{ translate('Serial') }}</td>
                <td>{{ translate('Transaction') }}</td>
                <td>{{ translate('Customer') }}</td>
                <td>{{ translate('Reference') }}</td>
                <td>{{ translate('Amount') }}</td>
                <td>{{ translate('Balance') }}</td>
                <td>{{ translate('Date') }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['transactions'] as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>
                        {{ $typeLabel($item->transaction_type) }}
                        <br>
                        {{ $item->transaction_id }}
                    </td>
                    <td>
                        {{ $item->user ? ucwords($item->user->f_name.' '.$item->user->l_name) : translate('Not found') }}
                        <br>
                        {{ $item->user?->email }}
                    </td>
                    <td>{{ $item->reference ? ucfirst(str_replace('_', ' ', $item->reference)) : translate('N/A') }}</td>
                    <td>
                        {{ $item->credit > 0 ? '+'.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->credit)) : '-'.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->debit)) }}
                        @if($item->admin_bonus > 0)
                            <br>
                            {{ translate('Admin Bonus').' +'.setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->admin_bonus)) }}
                        @endif
                    </td>
                    <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->balance ?? 0)) }}</td>
                    <td>{{ date('d M Y h:i A', strtotime($item->created_at)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
