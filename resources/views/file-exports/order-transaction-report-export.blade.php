<html>
<table>
    <thead>
    <tr>
        <th>{{translate('Order Transaction Report List')}}</th>
    </tr>
    <tr>
        <th>{{ translate('Filter Criteria') .' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '. ($data['search'] ?? 'N/A')}}
            <br>
            {{translate('Status').' '.'-'.' '. translate($data['status'] ?? translate('All'))}}
            <br>
            {{translate('Store')}} - {{ucwords($data['vendor'] != 'all' && $data['vendor'] !='inhouse' ? $data['vendor']?->shop->name : $data['vendor'])}}
            <br>
            {{translate('Customer')}} - {{ucwords($data['customer'] != 'all' ? ($data['customer']->f_name.' '.$data['customer']->l_name) : translate('All'))}}
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
        <td> {{translate('Order ID')}}    </td>
        @if(isset($data['data-from']) && $data['data-from'] == 'admin')
        <td> {{translate('Shop Name')}}    </td>
        @endif
        <td> {{translate('Customer Name')}}</td>
        <td> {{translate('Total Product Amount')}}</td>
        <td> {{translate('Product Discount')}}</td>
        <td> {{translate('Coupon Discount')}}</td>
        <td> {{translate('Referral Discount')}}</td>
        <td> {{translate('Discounted Amount')}}</td>
        <td> {{translate('VAT/TAX')}}</td>
        <td> {{translate('Shipping Charge')}}</td>
        <td> {{translate('Order Amount')}}</td>
        @if($data['showEditAmounts'] ?? true)
            <td> {{translate('Due Amount')}}</td>
            <td> {{translate('Return Amount')}}</td>
        @endif
        <td> {{translate('Delivered by')}}</td>
        <td> {{translate('Delivery man incentive')}}</td>
        <td> {{translate('Admin Discount')}}</td>
        <td> {{translate('Vendor Discount')}}</td>
        <td> {{translate('Admin Commission')}}</td>
        @if(isset($data['data-from']) && $data['data-from'] == 'admin')
            <td> {{translate('Admin Net Income')}}</td>
        @endif
        <td> {{translate('Vendor Net Income')}}</td>
        <td> {{translate('Payment Method')}}</td>
        <td> {{translate('Payment Status')}}</td>
    </tr>
</thead>
<tbody>
    @foreach($data['transactions'] as $key => $transaction)
        <tr>
            <td>{{ $key }}</td>
            <td>
                <a class="title-color" href="{{ route('admin.retail.orders.details', ['id' => $transaction['order_id']]) }}">
                    {{ $transaction['order_id'] }}
                </a>
            </td>
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                <td>{{ $transaction['shop_name'] ?? translate('Not found') }}</td>
            @endif
            <td>
                @if (!$transaction['is_guest'] && isset($transaction['customer_name']))
                    {{ $transaction['customer_name']  }}
                @elseif($transaction['is_guest'])
                    {{ translate('Guest customer') }}
                @else
                    {{translate('Not found')}}
                @endif
            </td>
            <td>
                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['total_product_amount']), currencyCode: getCurrencyCode() ) }}
            </td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['product_discount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['coupon_discount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['referral_discount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['discounted_amount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['tax']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['shipping_charge']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['order_amount']), currencyCode: getCurrencyCode()) }}</td>
            @if($data['showEditAmounts'] ?? true)
                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['edit_due_amount']), currencyCode: getCurrencyCode()) }}</td>
                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['edit_return_amount']), currencyCode: getCurrencyCode()) }}</td>
            @endif
            <td>{{ $transaction['delivered_by'] }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['deliveryman_incentive']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_discount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['vendor_discount']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_commission']), currencyCode: getCurrencyCode()) }}</td>
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['admin_net_income']), currencyCode: getCurrencyCode()) }}</td>
            @endif
            <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $transaction['vendor_net_income']), currencyCode: getCurrencyCode()) }}</td>
            <td>{{ ucwords(str_replace('_',' ', $transaction['payment_method'])) }}</td>
            <td>
                {{ translate(str_replace('_',' ', $transaction['payment_status'])) }}
            </td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
