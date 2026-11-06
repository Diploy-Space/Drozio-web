<div class="color_image_column w-100">
    <div class="bg-section p-12 p-sm-20 rounded-10 h-100">
        <div class="form-group">
            <div class="">
                <h2 class="mb-1">{{ translate('Product Color Wise Images') }}</h2>
                <p class="fs-12 mb-0">
                    {{ translate('Here you can add color wise product image.') }}
                    <span class="text-info">
                        {{ getFileUploadFormats(skip: '.svg, .gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                        {{ translate('Ratio'). ' (1:1)' }}
                    </span>
                </p>
            </div>

            <div id="color-wise-image-section" class="d-flex justify-content-start flex-wrap gap-3"
                 data-url="{{ route('admin.retail.products.color-wise-images') }}"></div>
        </div>
    </div>
</div>

<div class="position-absolute start-0 top-0">
    <div class="btn-circle" style="--size: 12px;"></div>
</div>

<input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">@if (request('product-gallery'))
    <input type="hidden" id="clone-product-gallery" value="1">
@endif

