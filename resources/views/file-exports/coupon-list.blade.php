<html>
<table>
    <thead>
    <tr>
        <th style="font-size: 18px">{{translate('Coupon List')}}</th>
    </tr>
    <tr>
        <th>{{translate('Search Criteria').' '.'-'}}</th>
        <th></th>
        <th>
            {{translate('Search Bar Content').' '.'-'.' '.($data['search'] ?? 'N/A')}}
            @if(isset($data['vendor']))
                <br>
                {{translate('Store Name')}} - {{$data['vendor']?->shop?->name}}
            @endif
        </th>
    </tr>
    <tr>
        <td> {{translate('Serial')}}    </td>
        <td> {{translate('Coupon Title')}}</td>
        <td> {{translate('Coupon Code')}}</td>
        <td> {{translate('Coupon Type')}}</td>
        <td> {{translate('Number of Uses')}}</td>
        <td> {{translate('Limit for Same User')}}</td>
        <td> {{translate('Min Purchase Amount')}}</td>
        <td> {{translate('Max discount Amount')}}</td>
        <td> {{translate('Discount Type')}}</td>
        <td> {{translate('Discount Amount')}}</td>
        <td> {{translate('Coupon Bearer')}}</td>
        <td> {{translate('Start Date')}}</td>
        <td> {{translate('End Date')}}</td>
    </tr>
</thead>
<tbody>
    @foreach ($data['coupon'] as $key=>$item)
        <tr>
            <td> {{++$key}}    </td>
            <td> {{ucwords($item['title'])}}    </td>
            <td> {{$item['code']}}    </td>
            <td> {{translate($item['coupon_type'])}}</td>
            <td> {{ $item['order_count'] }}    </td>
            <td> {{ $item['limit']==0 ? 'N/A' : $item['limit'] }}    </td>
            <td> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['min_purchase'] ?? 0)) }}    </td>
            <td> {{ $item['discount_type'] == 'percentage' ? setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['max_discount'] ?? 0)) : 'N/A'   }}    </td>
            <td> {{ translate($item['discount_type']) }} </td>
            <td> {{ $item['discount_type'] == 'percentage' ? ( $item['discount'] ."%" ):  setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['discount'] ?? 0))  }}    </td>
            <td> {{translate($item['coupon_bearer'] == 'inhouse' ? 'admin' : ($item['coupon_bearer'] == 'seller' ? 'vendor' : $item['coupon_bearer']))}}    </td>
            <td> {{date('d M, y',strtotime($item['start_date']))}}    </td>
            <td> {{date('d M, y',strtotime($item['expire_date']))}}    </td>
        </tr>
    @endforeach
</tbody>
</table>
</html>
