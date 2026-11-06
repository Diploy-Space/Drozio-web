<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Expense Transaction Report List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '. ($data['search'] ?? 'N/A')}}
            @if(isset($data['vendor']))
            <br>
                {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
            @endif
            <br>
            {{translate('Date Type').' '.'-'.' '.translate($data['dateType'])}}
            <br>
            @if($data['from'] && $data['to'])
                {{translate('From').' '.'-'.' '.date('d M, Y',strtotime($data['from']))}}
                <br>
                {{translate('To').' '.'-'.' '.date('d M, Y',strtotime($data['to']))}}
                <br>
            @endif
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}</td>
        <th>XID</th>
        <th>{{translate('Transaction Date')}}</th>
        <th>{{translate('Order ID')}}</th>
        <th>{{translate('Expense Amount')}}</th>
        <th>{{translate('Expense Type')}}</th>
    </tr>
</thead>
<tbody>
    @foreach ($data['transactions'] as $key => $transaction)
        <tr>
            <td>{{ ++$key }} </td>
            <td>{{ $transaction->orderTransaction->transaction_id }}</td>
            <td>{{ date_format($transaction?->orderTransaction->updated_at, 'd F Y h:i:s a') }}</td>
            <td>{{ $transaction->id }}</td>
            <td>
                @if(isset($data['data-from']) && $data['data-from'] == 'vendor')
                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['expense_amount']), currencyCode: getCurrencyCode()) }}
                @else
                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($transaction?->refer_and_earn_discount ?? 0) + (($transaction['seller_is'] == 'admin' || $transaction->coupon_discount_bearer == 'inhouse') ? $transaction->discount_amount : 0) + ($transaction->free_delivery_bearer=='admin' ? $transaction->extra_discount : (($transaction->coupon_discount_bearer == 'seller' ? $transaction->discount_amount : 0) + ($transaction->free_delivery_bearer == 'seller' ? $transaction->extra_discount:0)))), currencyCode: getCurrencyCode()) }}
                @endif
            </td>
            <td>
                @if(isset($data['data-from']) && $data['data-from'] == 'vendor')
                    @if ($transaction['expense_types']['coupon_type'] === 'free_delivery')
                        {{ translate('Free Delivery Promotion') }}
                    @elseif ($transaction['expense_types']['coupon_type'] !== null)
                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['coupon_type'])) }}
                    @endif
                    @if($transaction['expense_types']['free_delivery_type'] !== null)
                        {{ ucwords(str_replace('_', ' ', $transaction['expense_types']['free_delivery_type'])) }}
                        <br>
                    @endif
                @else
                    @if ($transaction->coupon_discount_bearer == 'inhouse' || ($transaction->coupon_discount_bearer == 'seller' && $transaction->seller_is == 'admin'))
                        @if (isset($transaction->coupon->coupon_type))
                            @if ($transaction->coupon->coupon_type == 'free_delivery')
                                {{ translate('Free Delivery Promotion') }}
                            @else
                                {{ ucwords(str_replace('_', ' ', ($transaction->coupon->coupon_type))) }}
                            @endif
                        @elseif(!is_null($transaction->coupon_code) && $transaction?->coupon_code != 0)
                            {{ translate('Coupon Discount') }}
                            <br>
                        @endif
                    @endif
                    @if ($transaction->free_delivery_bearer == 'admin')
                        {{ ucwords(str_replace('_', ' ', $transaction->extra_discount_type)) }}
                            <br>
                    @endif
                    @if($transaction?->refer_and_earn_discount > 0)
                        {{ translate('Referral Discount') }}
                    @endif
                @endif
            </td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
