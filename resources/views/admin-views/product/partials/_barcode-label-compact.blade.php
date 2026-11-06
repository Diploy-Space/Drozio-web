<div class="item style-compact">
    @if ($product->code !== null)
        <div class="barcode_image d-flex justify-content-center overflow-hidden">
            {!! DNS1D::getBarcodeHTML($product->code, 'C128', 1, 52, 'black', 9) !!}
        </div>
    @else
        <p class="text-danger mb-0">
            {{ translate('Please update product code') }}
        </p>
    @endif
</div>
