<html>
    <table>
        <thead>
            <tr>
                <th style="font-size: 18px">{{ translate('Vendor VAT Report') }}</th>
            </tr>
            <tr>
                <th>{{ translate('Search Criteria') . ' - ' }}</th>
                <th></th>
                <th>
                    @if (isset($data['totalOrders']))
                        {{ translate('Total Orders') . ' - ' . $data['totalOrders'] }}
                        <br>
                    @endif
                    @if (isset($data['totalOrderAmount']))
                        {{ translate('Total Order Amount') . ' - ' }}{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['totalOrderAmount']), currencyCode: getCurrencyCode()) }}
                        <br>
                    @endif
                    @if (isset($data['totalTax']))
                        {{ translate('Total Tax') . ' - ' }}{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $data['totalTax']), currencyCode: getCurrencyCode()) }}
                        <br>
                    @endif
                    @if (isset($data['startDate']))
                        {{ translate('From') . ' - ' . $data['startDate']->format('m/d/Y') }}
                        <br>
                    @endif
                    @if (isset($data['endDate']))
                        {{ translate('To') . ' - ' . $data['endDate']->format('m/d/Y') }}
                        <br>
                    @endif
                    {{ translate('Search Bar Content') . ' - ' . ($data['search'] ?? translate('N/A')) }}
                </th>
            </tr>
            <tr>
                <td>{{ translate('Serial') }}</td>
                <td>{{ translate('Vendor Information') }}</td>
                <td>{{ translate('Total Order') }}</td>
                <td>{{ translate('Total Order Amount') }}</td>
                <td>{{ translate('VAT Amount') }}</td>
            </tr>
            @php($count = 1)
        </thead>
        <tbody>
            @foreach($data['shopTaxList'] as $key => $shopTaxItem)
                @php($shopItem = $shopTaxItem->first()->shop ?? null)
                <tr>
                    <td>{{ $count++ }}</td>
                    <td>
                        {{ $shopItem['name'] ?? 'N/A' }}
                        <br />
                        @if(!empty($shopItem['contact']))
                            ({{ $shopItem['contact'] }})
                        @endif
                    </td>
                    <td>{{ count($shopTaxItem) }}</td>
                    <td>
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $shopTaxItem->sum('order_amount')), currencyCode: getCurrencyCode()) }}
                    </td>
                    <td>
                        {{ translate('Total') . ' : ' }}{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $shopTaxItem?->sum('tax') ?? 0), currencyCode: getCurrencyCode()) }}
                        @foreach($shopTaxItem?->pluck('orderTaxes')->flatten()->groupBy('tax_on')->sortKeys() as $orderTaxItemKey => $orderTaxItem)
                            @if($orderTaxItemKey == 'basic')
                                @php($groupLabel = ucwords('Order Tax'))
                            @else
                                @php($groupLabel = ucwords(str_replace('_', ' ', $orderTaxItemKey)))
                            @endif
                            <br><br>
                            {{ $groupLabel . ' : ' }}
                            @foreach($orderTaxItem->groupBy('tax_name') as $taxItemKey => $taxGroup)
                                <br>
                                {{ ucwords($taxItemKey) . ' : ' }}{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $taxGroup->sum('tax_amount')), currencyCode: getCurrencyCode()) }}
                            @endforeach
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</html>
