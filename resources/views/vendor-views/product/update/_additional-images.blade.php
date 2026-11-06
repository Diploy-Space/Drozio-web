<div class="additional-image-column-section col-md-12">
    <div class="card card-body h-100">
        <div class="mb-20">
            <h3 class="mb-1">{{ translate('Product Additional Images') }}</h3>
            <p class="fs-12 mb-0">
                {{ translate('Upload additional images for this product from here.') }}
                <span class="text-info">
                    {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                    ({{ THEME_RATIO[theme_root_path()]['Product Image'] }})
                </span>
            </p>
        </div>
        <div class="d-flex flex-column bg-section rounded-10" id="additional_Image_Section">
            <div class="position-relative">
                <div class="multi_image_picker d-flex gap-4 p-3"
                    data-ratio="1/1"
                    data-field-name="images[]"
                    data-max-filesize="{{ getFileUploadMaxSize() }}"
                     data-required-msg="{{ translate(':field is required', ['field' => translate('Additional image')]) }}"
                     data-max-filesize="{{getFileUploadMaxSize()}}"
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

                    @if (count($product->colors) == 0)
                        @foreach ($product->images_full_url as $key => $photo)
                            @php($unique_id = rand(1111, 9999))
                            <div class="upload-file m-0 position-relative">
                                @if (request('product-gallery'))
                                    <button type="button"
                                        class="remove_btn btn btn-danger btn-circle w-20 h-20 fs-8 delete_file_input_css remove-addition-image-for-product-gallery"
                                        data-section-remove-id="addition-image-section-{{ $key }}">
                                        <i class="fi fi-sr-cross"></i>
                                    </button>
                                @else
                                    <button type="button"
                                        class="delete_file_input_css remove_btn btn btn-danger btn-circle w-20 h-20 fs-8 action-delete-product-image"
                                        data-id="{{ $product['id'] }}"
                                        data-name="{{ $photo['key'] }}">
                                        <i class="fi fi-rr-cross"></i>
                                    </button>
                                @endif

                                <label class="upload-file__wrapper">
                                    <img class="upload-file-img" loading="lazy"
                                        id="additional_Image_{{ $unique_id }}"
                                        src="{{ getStorageImages(path: $photo, type: 'backend-product') }}"
                                        data-default-src="{{ getStorageImages(path: $photo, type: 'backend-product') }}"
                                        alt="">
                                    @if (request('product-gallery'))
                                        <input type="text" name="existing_images[]"
                                            value="{{ $photo['key'] }}" hidden>
                                    @endif
                                </label>

                                <div class="overlay">
                                    <div
                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                        <button type="button"
                                            class="btn btn-outline-info icon-btn view_btn"
                                            data-img="#additional_Image_{{ $unique_id }}">
                                            <i class="fi fi-sr-eye d-flex text-info"></i>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @if ($product->retailDetail?->color_image)
                            @foreach ($product->color_images_full_url as $key => $photo)
                                @if ($photo['color'] == null)
                                    @php($unique_id = rand(1111, 9999))
                                    <div class="upload-file m-0 position-relative">
                                        <button type="button"
                                            class="delete_file_input_css remove_btn btn btn-danger btn-circle w-20 h-20 fs-8 action-delete-product-image"
                                            data-id="{{ $product['id'] }}"
                                            data-name="{{ $photo['image_name']['key'] }}">
                                            <i class="fi fi-rr-cross"></i>
                                        </button>

                                        <label class="upload-file__wrapper">
                                            <img class="upload-file-img" loading="lazy"
                                                id="additional_Image_{{ $unique_id }}"
                                                src="{{ getStorageImages(path: $photo['image_name'], type: 'backend-product') }}"
                                                data-default-src="{{ getStorageImages(path: $photo['image_name'], type: 'backend-product') }}"
                                                alt="">
                                        </label>

                                        <div class="overlay">
                                            <div
                                                class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                <button type="button"
                                                    class="btn btn-outline-info icon-btn view_btn"
                                                    data-img="#additional_Image_{{ $unique_id }}">
                                                    <i class="fi fi-sr-eye d-flex text-info"></i>
                                                </button>

                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            @foreach ($product->images_full_url as $key => $photo)
                                @php($unique_id = rand(1111, 9999))
                                <div class="" id="addition-image-section-{{ $key }}">
                                    <div class="upload-file m-0 position-relative">
                                        @if (request('product-gallery'))
                                            <button type="button"
                                                class="remove_btn btn btn-danger btn-circle w-20 h-20 fs-8 delete_file_input_css remove-addition-image-for-product-gallery"
                                                data-section-remove-id="addition-image-section-{{ $key }}">
                                                <i class="fi fi-sr-cross"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="delete_file_input_css remove_btn btn btn-danger btn-circle w-20 h-20 fs-8 action-delete-product-image"
                                                data-id="{{ $product['id'] }}"
                                                data-name="{{ $photo['key'] }}">
                                                <i class="fi fi-rr-cross"></i>
                                            </button>
                                        @endif

                                        <label class="upload-file__wrapper">
                                            <img class="upload-file-img" loading="lazy"
                                                id="additional_Image_{{ $unique_id }}"
                                                src="{{ getStorageImages(path: $photo, type: 'backend-product') }}"
                                                data-default-src="{{ getStorageImages(path: $photo, type: 'backend-product') }}"
                                                alt="">
                                            @if (request('product-gallery'))
                                                <input type="text" name="existing_images[]"
                                                    value="{{ $photo['key'] }}" hidden>
                                            @endif
                                        </label>

                                        <div class="overlay">
                                            <div
                                                class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                <button type="button"
                                                    class="btn btn-outline-info icon-btn view_btn"
                                                    data-img="#additional_Image_{{ $unique_id }}">
                                                    <i class="fi fi-sr-eye d-flex text-info"></i>
                                                </button>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
