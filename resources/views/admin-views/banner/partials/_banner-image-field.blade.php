@php($banner = $banner ?? null)
@php($storedImage = $banner ? getStorageImages(path: $banner['photo_full_url'], type: 'backend-banner') : '')
<div class="d-flex flex-column gap-20 text-center w-100">
    <div>
        <label class="form-label fw-semibold mb-1">
            {{ translate('Banner image') }} <span class="text-danger">*</span>
        </label>
        <p class="fs-12 mb-0">
            {{ translate('Upload your banner image') }}
            <span class="text-dark fw-medium theme-ratio"></span>
        </p>
    </div>

    <div class="upload-file">
        <input type="file" name="image" class="upload-file__input single_file_input"
               accept="{{ getFileUploadFormats(skip: '.svg') }}"
               data-max-size="{{ getFileUploadMaxSize() }}"
               data-required-msg="{{ translate('Banner image is required') }}"
            {{ $banner && ! empty($storedImage) ? '' : 'required' }}>
        <label class="upload-file__wrapper ratio-4-1">
            <div class="upload-file-textbox text-center">
                <img width="34" height="34" class="svg"
                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                     alt="image upload">
                <h6 class="mt-1 fw-medium lh-base text-center">
                    <span class="text-info">{{ translate('Click to upload') }}</span><br>
                    {{ translate('Or drag and drop') }}
                </h6>
            </div>
            <img class="upload-file-img" loading="lazy" src="{{ $storedImage }}"
                 data-default-src="{{ $storedImage }}" alt="">
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
        {{ getFileUploadFormats(skip: '.svg', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
    </p>
    <p class="fs-10 mb-0 text-center">
        {{ translate('Banner image ratio is not the same for all sections on the website.') }}
        {{ translate('Please review the ratio before uploading') }}
    </p>
</div>
