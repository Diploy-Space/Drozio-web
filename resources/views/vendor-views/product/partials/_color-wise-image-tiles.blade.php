@foreach ($tiles as $tile)
    <div>
        <div class="upload-file position-relative">
            <label for="color-img-upload-{{ $tile['key'] }}">
                <input type="file" name="{{ $tile['input_name'] }}"
                       class="single_file_input upload-file__input action-upload-color-image"
                       id="color-img-upload-{{ $tile['key'] }}" data-index="1"
                       data-imgpreview="{{ $tile['preview_id'] }}"
                       data-validation-error-msg="{{ translate('Only allowed extensions are: .jpg, .jpeg, .png, .webp') }}"
                       accept=".jpg, .webp, .png, .jpeg" data-max-size="{{ getFileUploadMaxSize() }}" required>
            </label>

            <div class="position-absolute start-0 z-2">
                <label for="color-img-upload-{{ $tile['key'] }}" class="p-1">
                    <div class="btn-circle shadow-lg"
                         style="--size: 22px; background-color: {{ $tile['color_code'] }}; border: 1.5px solid #fff;"></div>
                </label>
            </div>

            <div class="position-absolute end-0 d-flex gap-2 z-2 p-1">
                <label for="color-img-upload-{{ $tile['key'] }}"
                       class="btn btn-outline-primary bg-white text-primary p-0 icon-btn position-relative fs-10 product-image-edit-icon {{ $tile['image_path'] ? '' : 'd-none' }}"
                       style="--size: 24px;">
                    <i class="fi fi-sr-pencil"></i>
                </label>

                @if ($tile['stored_name'] && ! $isProductGallery)
                    <button type="button"
                            class="btn btn-danger cursor-pointer icon-btn p-0 fs-10 action-delete-product-image"
                            style="--size: 24px;" data-id="{{ $productId }}"
                            data-name="{{ $tile['stored_name'] }}"
                            data-preview-id="{{ $tile['preview_id'] }}">
                        <i class="fi fi-rr-trash"></i>
                    </button>
                @endif
            </div>

            <div class="upload-file__wrapper">
                <img id="{{ $tile['preview_id'] }}" alt=""
                     class="upload-file-img {{ $tile['image_path'] ? 'd-block' : '' }}"
                     src="{{ $tile['image_path'] }}">

                @if ($isProductGallery && $tile['stored_name'])
                    <input type="text" name="color_image_{{ $tile['key'] }}[]" value="{{ $tile['stored_name'] }}" hidden>
                @endif

                <div class="upload-file-textbox text-center {{ $tile['image_path'] ? 'd-none' : '' }}">
                    <img width="34" height="34" class="svg"
                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"
                         alt="image upload">
                    <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                        <span class="text-info">{{ translate('Click to upload') }}</span>
                        <br>{{ translate('Or drag and drop') }}
                    </h6>
                </div>
            </div>
        </div>
    </div>
@endforeach
