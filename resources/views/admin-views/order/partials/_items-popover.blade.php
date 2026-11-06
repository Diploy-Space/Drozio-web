<div class="order-items-popover">
    <button class="btn btn-link text-decoration-underline p-0 border-0 fw-semibold order-items-popover-toggle" type="button"
            aria-haspopup="true" aria-expanded="false" aria-controls="order-items-popover-{{ $orderId }}">
        {{ count($items) }} {{ count($items) === 1 ? translate('Item') : translate('Items') }}
    </button>

    <div class="order-items-popover-menu" id="order-items-popover-{{ $orderId }}" role="region"
         aria-label="{{ translate('Product List') }}">
        <div class="order-items-popover-header text-center py-3">{{ translate('Product List') }}</div>
        <div class="order-items-popover-body">
            @foreach ($items as $item)
                <div class="order-items-popover-item d-flex justify-content-between align-items-center gap-3 px-3 py-3">
                    <div class="order-items-popover-product d-flex align-items-center gap-2">
                        <div class="order-items-popover-thumbnail">
                            <img src="{{ $item['thumbnail'] }}" alt="" class="w-100 h-100 object-fit-cover rounded"
                                 onerror="this.onerror=null;this.src='{{ dynamicAsset(path: 'public/assets/back-end/img/placeholder/product.png') }}'">
                        </div>
                        <div class="order-items-popover-name">
                            <h6 class="text-dark fs-12 mb-1 line-1 fw-semibold" title="{{ $item['name'] }}">{{ $item['name'] }}</h6>
                            @if ($item['variation'])
                                <small class="text-secondary d-block fs-12">{{ $item['variation'] }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="text-dark fw-medium px-2 text-nowrap">x {{ $item['quantity'] }}</div>
                    <div class="text-dark fw-semibold text-end order-items-popover-price">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item['price'])) }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
