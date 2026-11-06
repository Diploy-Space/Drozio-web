@if(count($combinations) > 0)
    <table class="table physical_product_show table-borderless">
        <thead class="thead-light thead-50 text-capitalize">
        <tr>
            <th class="text-center">
                <label for="" class="control-label">
                    {{ translate('Serial') }}
                </label>
            </th>
            <th class="text-center">
                <label for="" class="control-label">
                    {{ translate('Attribute Variation') }}
                </label>
            </th>
            <th class="text-center">
                <label for="" class="control-label">
                    {{ translate('Variation Wise Price') }}
                    ({{ getCurrencySymbol() }})
                </label>
            </th>
            <th class="text-center">
                <label for="" class="control-label">
                    {{ translate('SKU') }}
                </label>
            </th>
            <th class="text-center">
                <label for="" class="control-label">
                    {{ translate('Variation Wise Stock') }}
                </label>
            </th>
        </tr>
        </thead>
        <tbody>

        @foreach ($combinations as $key => $combination)
            <tr>
                <td class="text-center">
                    {{ $key+1 }}
                </td>
                <td>
                    <label for="" class="control-label">{{ $combination['type'] }}</label>
                    <input value="{{ $combination['type'] }}" name="type[]" class="d-none">
                </td>
                <td>
                    <input type="number" name="price_{{ $combination['type'] }}"
                           value="{{ usdToDefaultCurrency(amount: $combination['price']) }}" min="0"
                           step="0.01"
                           class="form-control min-w-max-content" required placeholder="{{ translate('Ex') }}: 535">
                </td>
                <td>
                    <input type="text" name="sku_{{ $combination['type'] }}" value="{{ $combination['sku'] }}"
                           class="form-control min-w-max-content store-keeping-unit">
                </td>
                <td>
                    <input type="number" name="qty_{{ $combination['type'] }}"
                           value="{{ $combination['qty'] }}" min="0" max="100000" step="1"
                           class="form-control min-w-max-content" placeholder="{{ translate('Ex') }}: 5"
                           required>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
