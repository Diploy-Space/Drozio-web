@php($isPhysical = $product->product_type === 'physical')
@php($currentStock = max(0, (int) $product->current_stock))
@php($isOutOfStock = $isPhysical && $currentStock < 1)

<div class="pos-card action-select-product {{ $isOutOfStock ? 'pos-card--out' : '' }}" data-id="{{ $product['id'] }}">
    <div class="product-add-count d-none" id="product-added-to-cart-{{ $product['id'] }}"></div>

    <div class="pos-card__media">
        <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product') }}"
             alt="{{ $product['name'] }}">

        @if($product?->clearanceSale)
            <span class="pos-flag pos-flag--warning pos-card__flag">{{ translate('Clearance Sale') }}</span>
        @endif
        @if($isPhysical)
            <span class="pos-flag pos-card__stock {{ $isOutOfStock ? 'pos-flag--danger' : '' }}">
                {{ $isOutOfStock ? translate('Out of Stock') : translate('Stock').': '.$currentStock }}
            </span>
        @endif

        <div class="pos-card__hover">
            @if($isOutOfStock)
                <span>
                    <i class="fi fi-rr-ban d-flex"></i>
                    {{ translate('Out of Stock') }}
                </span>
            @else
                <span>
                    <i class="fi fi-rr-shopping-cart-add d-flex"></i>
                    {{ $isPhysical ? translate('Add To Cart') : translate('Click for details') }}
                </span>
            @endif
        </div>
    </div>

    <div class="pos-card__body">
        <div class="pos-card__name" title="{{ $product['name'] }}">{{ $product['name'] }}</div>
        <div class="pos-card__prices pos-num">
            <span class="pos-card__price">
                {{ getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string', price: $product['unit_price'], from: 'panel') }}
            </span>
            @if (getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                <span class="pos-card__compare">{{ webCurrencyConverter(amount: $product['unit_price']) }}</span>
            @endif
        </div>
    </div>
</div>
