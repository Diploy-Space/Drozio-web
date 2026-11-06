<div class="item style24">
    <span class="barcode_site text-capitalize">
        @if($product->added_by == 'seller')
            {{ isset($product?->seller?->shop->name) ? Str::limit($product->seller->shop->name, 20) : getInHouseShopConfig(key: 'name') }}
        @elseif($product->added_by == 'admin')
            {{ getInHouseShopConfig(key: 'name') }}
        @endif
    </span>
    <span class="barcode_name text-capitalize">
        {{ Str::limit($product->name, 20) }}
    </span>
    <div class="barcode_price text-capitalize">
        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product->unit_price), currencyCode: getCurrencyCode()) }}
    </div>

    @if ($product->code !== null)
        <div class="barcode_image d-flex justify-content-center overflow-hidden">
            {!! DNS1D::getBarcodeHTML($product->code, 'C128') !!}
        </div>
        <div class="barcode_code text-capitalize">
            {{ translate('Code') }} : {{ $product->code }}
        </div>
    @else
        <p class="text-danger">
            {{ translate('Please update product code') }}
        </p>
    @endif
</div>
