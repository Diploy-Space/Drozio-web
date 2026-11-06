<div class="color_image_column w-100">
    <div class="bg-section p-12 p-sm-20 rounded-10 h-100">
        <div class="form-group mb-0">
            <div class="">
                <h3 class="mb-1">{{ translate('Product Color Wise Images') }}</h3>
                <p class="fs-12 mb-0">
                    {{ translate('Here you can add color wise product image.') }}
                    <span class="text-info">
                        {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                        ({{ THEME_RATIO[theme_root_path()]['Product Image'] }})
                    </span>
                </p>
            </div>

            <div id="color-wise-image-section" class="d-flex justify-content-start flex-wrap gap-3"
                 data-url="{{ route('vendor.retail.products.color-wise-images') }}"></div>
        </div>
    </div>
</div>

<div class="position-absolute start-0 top-0">
    <div class="btn-circle" style="--size: 12px;"></div>
</div>

