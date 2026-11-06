<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{ translate('Order List') }}</th>
    </tr>
    <tr>

        <th>{{ translate('Filter Criteria').' '.'-' }}</th>
        <th></th>
        <th>
            @if($data['order_status'] != 'all')
                {{ translate('Order Status').' '.'-'.' '.translate($data['order_status'] != 'failed' ? $data['order_status'] : 'failed_to_deliver') }}
                <br>
            @endif
            {{ translate('Search Bar Content').' '.'-'.' '.$data['searchValue'] ?? 'N/A'}}
            <br>
            {{ translate('Order Type').' '.'-'.' '.translate($data['order_type']== 'admin' ? 'inhouse' : ($data['order_type'] == 'default_type' ? 'website_order' : $data['order_type']) ) }}
            <br>
            {{ translate('Store').' '.'-'.' '.ucwords($data['store_name'] ?? translate('All')) }}
            <br>
            {{ translate('Customer Name').' '.'-'.' '.ucwords(isset($data['customer']->f_name) ? $data['customer']->f_name.' '.$data['customer']->l_name : translate('All Customers') ) }}
            <br>
            {{ translate('Date Type').' '.'-'.' '.translate(!empty($data['date_type']) ? $data['date_type'] : 'all') }}
            <br>
            @if ($data['date_type'] == 'custom_date')
                {{ translate('From').' '.'-'.' '.$data['from'] ?? date('d M, Y',strtotime($data['from'])) }}
                <br>
                {{ translate('To').' '.'-'.' '.$data['to'] ?? date('d M, Y',strtotime($data['to'])) }}
                <br>
            @endif
            @foreach($data['filter_criteria'] ?? [] as $criteriaLabel => $criteriaValue)
                {{ $criteriaLabel.' '.'-'.' '.$criteriaValue }}
                <br>
            @endforeach
        </th>
    </tr>
    <tr>
        @if($data['order_status'] == 'all')
            <th>{{ translate('Order Status') }}</th>
            <th></th>
            <th>
                @foreach ($data['status_array'] as $key=>$value)
                    {{ translate($key != 'failed' ? $key : 'failed_to_deliver').' '.'-'.' '.$value}}
                @endforeach
            </th>
        @endif
    </tr>
    <tr>
        <th> {{ translate('Serial') }}    </th>
        <th> {{ translate('Order ID') }}    </th>
        <th> {{ translate('Order Date') }}    </th>
        <th> {{ translate('Customer Name') }}    </th>
        @if(isset($data['data-from']) && $data['data-from'] == 'admin')
            <th> {{ translate('Store Name') }}    </th>
        @endif
        <th> {{ translate('Total Items') }}    </th>
        <th> {{ translate('Item Price') }}    </th>
        <th> {{ translate('Item Discount') }}    </th>
        <th> {{ translate('Coupon Discount') }}    </th>
        <th> {{ translate('Referral Discount') }} </th>
        <th> {{ translate('Extra discount') }}    </th>
        <th> {{ translate('Discounted Amount') }}    </th>
        <th> {{ translate('VAT/TAX') }}    </th>
        <th> {{ translate('Shipping') }}    </th>
        <th> {{ translate('Total Amount') }}    </th>
        <th> {{ translate('Payment Status') }}</th>
        @if($data['order_status'] == 'all')
            <th> {{ translate('Order Status') }}</th>
        @endif
        <th> {{ translate('Edited Status') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($data['orders'] as $key=>$order)
        @php($orderTotalPriceSummary = $data['order_summaries'][$order->id])
        <tr>
            <td> {{++$key}}    </td>
            <td> {{$order->id}}    </td>
            <td> {{date('d M, Y h:i A',strtotime($order->created_at)) }}</td>
            <td> {{ucwords($order->is_guest == 0 ? (($order?->customer?->f_name ?? translate('Not found')) .' '. $order?->customer?->l_name) : translate('Guest customer')) }}    </td>
            @if(isset($data['data-from']) && $data['data-from'] == 'admin')
                <td> {{ucwords($order?->seller_is == 'seller' ? ($order?->seller?->shop->name ?? translate('Not found')) : translate('In-house')) }}    </td>
            @endif
            <td> {{ $orderTotalPriceSummary['totalItemQuantity'] ?? 0 }} </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['itemPrice'] ?? 0), currencyCode: getCurrencyCode()) }} </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['itemDiscount'] ?? 0), currencyCode: getCurrencyCode()) }} </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['couponDiscount'] ?? 0), currencyCode: getCurrencyCode()) }}</td>
            <td>{{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['referAndEarnDiscount'] ?? 0), currencyCode: getCurrencyCode()) }}</td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $order?->is_shipping_free == 0 ? $orderTotalPriceSummary['extraDiscount'] : 0), currencyCode: getCurrencyCode()) }}</td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['subTotal'] ?? 0), currencyCode: getCurrencyCode()) }}  </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['taxTotal'] ?? 0), currencyCode: getCurrencyCode()) }}    </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['shippingTotal'] ?? 0), currencyCode: getCurrencyCode()) }}    </td>
            <td> {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $orderTotalPriceSummary['totalAmount'] ?? 0), currencyCode: getCurrencyCode()) }}</td>
            <td> {{ translate($order->payment_status) }}</td>
            @if($data['order_status'] == 'all')
                <td> {{ translate($order->order_status) }}</td>
            @endif
            <td> {{ $order->edited_status ? translate('Modified') : translate('Unmodified') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</html>
