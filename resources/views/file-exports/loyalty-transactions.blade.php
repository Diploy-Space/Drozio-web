@php($transactionTypes = $data['transaction_types'] ?? [])
@php($orderReferenceTypes = $data['order_reference_types'] ?? [])
@php($typeLabel = fn ($type) => $transactionTypes[$type] ?? ucfirst(str_replace('_', ' ', (string) $type)))
@php($customer = $data['customer'])
<html>
    <table>
        <thead>
            <tr>
                <th style="font-size:18px">{{ translate('Loyalty Point Report') }}</th>
            </tr>
            <tr>
                <th>{{ translate('Search Criteria').' -' }}</th>
                <th></th>
                <th>
                    {{ translate('Business Module').' - '.$data['business_module'] }}
                    <br>
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
                <th>{{ translate('Balance').' - '.$data['balance'] }}</th>
                <th></th>
                <th>{{ translate('Total Credit').' - '.$data['credit'] }}</th>
                <th></th>
                <th>{{ translate('Total Debit').' - '.$data['debit'] }}</th>
            </tr>
            <tr>
                <td>{{ translate('Serial') }}</td>
                <td>{{ translate('Transaction') }}</td>
                <td>{{ translate('Customer') }}</td>
                <td>{{ translate('Reference') }}</td>
                <td>{{ translate('Points') }}</td>
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
                    <td>
                        @if(!$item->reference)
                            {{ translate('N/A') }}
                        @elseif(in_array($item->transaction_type, $orderReferenceTypes))
                            {{ translate('Order').' #'.$item->reference }}
                        @else
                            {{ $item->reference }}
                        @endif
                    </td>
                    <td>{{ $item->credit > 0 ? '+'.$item->credit : '-'.$item->debit }}</td>
                    <td>{{ $item->balance }}</td>
                    <td>{{ date('d M Y h:i A', strtotime($item->created_at)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
