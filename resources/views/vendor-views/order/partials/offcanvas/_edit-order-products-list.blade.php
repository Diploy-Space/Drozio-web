<h4 class="mb-3">
    {{ translate('Product List') }}
    <span class="badge text-dark fs-12 bg-section2 rounded-10">
        {{ count($orderProductsSession['product_list'] ?? []) }}
    </span>
</h4>
<input type="hidden" name="order_id" value="{{ $order['id'] }}">
<div class="table-responsive border rounded-10">
    <table class="table table-hover align-middle td-padding-sm mb-0">
        <thead class="thead-light thead-50 text-capitalize">
        <tr>
            <th>{{ translate('Serial') }}</th>
            <th>{{ translate('Item List') }}</th>
            <th class="text-center">{{ translate('Quantity') }}</th>
            <th class="text-end">{{ translate('Total') }}</th>
            <th class="text-center">{{ translate('Action') }}</th>
        </tr>
        </thead>

        <tbody>
        @php($sl = 1)
        @foreach($orderProductsSession['product_list'] as $key => $detail)
            @php($quantity = $editProductQuantities[$key])
            <tr
                @class([
                    'bg-primary bg-opacity-10' => $detail['existing_product'] ?? false,
                    'opacity-50' => $detail['product_type'] === 'digital',
                ])
                @if($detail['product_type'] === 'digital')
                    data-toggle="tooltip"
                    data-title="{{ translate('Digital Product is not editable') }}"
                @endif
            >
                <td>{{ $sl++ }}</td>
                <td>
                    <div class="media align-items-center gap-2">
                        <img class="avatar avatar-50 rounded img-fit bg-white"
                             src="{{ getStorageImages(path: $detail['thumbnail_full_url'], type: 'backend-product') }}"
                             alt="{{ translate('Image description') }}">

                        <div class="media-body d-flex flex-column gap-1 fs-12">
                            <div class="max-w-200 line-1">
                                {{ $detail['name'] }}
                            </div>
                            <div class="d-flex gap-2 align-items-center lh-1">
                                <span class="text-body">{{ translate('Unit Price') }}</span> :
                                <span>
                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['price'])) }}
                                </span>
                            </div>

                            @if($detail['discount'] > 0)
                                <div class="d-flex gap-2 align-items-center lh-1">
                                    <span class="text-body">{{ translate('Discount') }}</span> :
                                    <span>
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $detail['discount'])) }}
                                    </span>
                                </div>
                            @endif

                            @if(!empty($detail['variant']))
                                <div class="d-flex gap-2 align-items-center lh-1">
                                    <span class="text-body">{{ translate('Variation') }}</span> :
                                    <span>{{ $detail['variant'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div
                        class="qty-input-group-design qty-input-group-js form-control w-max-content d-flex gap-2 align-items-center {{ isset($detail['is_quantity_editable']) && $detail['is_quantity_editable'] === false ? 'opacity--70' : '' }}"
                        @if(isset($detail['is_quantity_editable']) && $detail['is_quantity_editable'] === false)
                            data-toggle="tooltip"
                            data-title="{{ translate('The quantity cannot be updated because the product has been modified') }}"
                        @endif
                    >
                        <button type="button" class="qty-count"
                            data-action="minus"
                            {{ $quantity['value'] <= $quantity['min'] ? 'disabled' : '' }}
                            {{ $detail['product_type'] == 'digital' ? 'disabled' : '' }}
                            {{ isset($detail['is_quantity_editable']) && $detail['is_quantity_editable'] === false ? 'disabled' : '' }}
                        >-</button>
                        <input class="product-qty text-center" type="number"
                               name="products[{{ $key }}][qty]"
                               value="{{ $quantity['value'] }}"
                               min="{{ $quantity['min'] }}"
                               max="{{ $quantity['max'] }}"
                            {{ $detail['product_type'] == 'digital' ? 'readonly' : '' }}
                            {{ isset($detail['is_quantity_editable']) && $detail['is_quantity_editable'] === false ? 'readonly' : '' }}>
                        <button class="qty-count" data-action="plus" type="button"
                            {{ $detail['product_type'] == 'digital' ? 'disabled' : '' }}
                            {{ $quantity['value'] >= $quantity['max'] ? 'disabled' : '' }}
                            {{ isset($detail['is_quantity_editable']) && $detail['is_quantity_editable'] === false ? 'disabled' : '' }}
                        >+</button>
                    </div>
                </td>
                <td class="text-end">
                    {{ setCurrencySymbol(
                        amount: usdToDefaultCurrency(
                            amount: (($detail['price'] * $detail['qty']) - $detail['discount'])
                        )
                    ) }}
                </td>
                <td>
                    <div class="d-flex justify-content-center gap-3">
                        @if($detail['product_type'] != 'digital')
                            <a href="javascript:"
                               class="btn btn-danger rounded-circle icon-btn edit-order-product-remove-js"
                               data-route="{{ route('vendor.retail.orders.edit-order-product-remove', [
                                   'order_id' => $detail['order_id'],
                                   'product_id' => $detail['product_id'],
                                   'variant' => $detail['variant'] ?? ''
                               ]) }}">
                                <i class="fi fi-rr-trash"></i>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
