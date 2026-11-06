<div class="additional-image-column-section">
    <div class="card card-body h-100">
        <div class="mb-20">
            <h3 class="mb-1">{{ translate('Product Additional Images') }}</h3>
            <p class="fs-12 mb-0">
                {{ translate('Upload additional images for this product from here.') }}
                <span class="text-info">{{ translate('JPG, JPEG, PNG Image size : Max'). getFileUploadMaxSize(). " MB" }} ({{ THEME_RATIO[theme_root_path()]['Product Image'] }})</span>
            </p>
        </div>
        <div class="d-flex flex-column bg-section rounded-10" id="additional_Image_Section">
            <div class="position-relative">
                <div class="multi_image_picker d-flex gap-20 p-3"
                     data-ratio="1/1"
                     data-max-filesize="{{getFileUploadMaxSize()}}"
                     data-field-name="images[]"
                     data-required="true"
                     data-required-msg="{{ translate(':field is required', ['field' => translate('Additional image')]) }}"
                     data-allowed-formats="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                     data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize().' '.'MB' }}"
                >
                    <div class="dir-ltr">
                        <div class="imageSlide_prev">
                            <div
                                class="d-flex justify-content-center align-items-center h-100">
                                <button
                                    type="button"
                                    class="btn btn-circle border-0 bg-primary text-white">
                                    <i class="fi fi-sr-angle-left"></i>
                                </button>
                            </div>
                        </div>
                        <div class="imageSlide_next">
                            <div
                                class="d-flex justify-content-center align-items-center h-100">
                                <button
                                    type="button"
                                    class="btn btn-circle border-0 bg-primary text-white">
                                    <i class="fi fi-sr-angle-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
