<div class="item style24">
    <span class="barcode_site text-capitalize">
        {{ $shopName }}
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
