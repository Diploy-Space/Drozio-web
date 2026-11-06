<div class="d-flex flex-column gap-2 blog-upload-tile">
    <label class="form-label fw-semibold mb-0">
        {{ $label }}
        <span class="text-danger">*</span>
    </label>
    <span class="fs-12 text-muted">{{ $hint }}</span>
    <div class="upload-file">
        <input type="file" name="{{ $name }}" class="upload-file__input single_file_input"
               accept="{{ getFileUploadFormats() }}" value="" {{ $storedImage ? '' : 'required' }}
               data-required-msg="{{ $requiredMessage }}" data-max-size="{{ getFileUploadMaxSize() }}">
        <label class="upload-file__wrapper w-325">
            <div class="upload-file-textbox text-center">
                <img width="34" height="34" class="svg"
                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}" alt="">
                <h6 class="mt-1 fw-medium lh-base text-center">
                    <span class="text-info">{{ translate('Click to upload') }}</span>
                    <br>
                    {{ translate('or drag and drop') }}
                </h6>
            </div>
            <img class="upload-file-img" loading="lazy"
                 src="{{ $storedImage ? getStorageImages(path: $storedImage, type: 'backend-basic') : '' }}"
                 data-default-src="" alt="" style="display: none;">
        </label>
        <div class="overlay">
            <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                <button type="button" class="btn btn-outline-info icon-btn view_btn">
                    <i class="fi fi-sr-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                    <i class="fi fi-rr-camera"></i>
                </button>
            </div>
        </div>
    </div>
    <p class="fs-10 mb-0 text-center">
        {{ getFileUploadFormats() }} . {{ translate('Image size Max') }} : {{ getFileUploadMaxSize().'MB' }}
        <span class="fw-medium">(325 x 100 px)</span>
    </p>
</div>
