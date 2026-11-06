<form action="{{ route('admin.retail.notification.index') }}" method="post" enctype="multipart/form-data"
      class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
      novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notificationAddOffcanvas"
         aria-labelledby="notificationAddOffcanvasLabel" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1" id="notificationAddOffcanvasLabel">{{ translate('Send notification') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="form-group mb-3">
                        <label class="form-label" for="notification-title">
                            {{ translate('Title') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" id="notification-title" class="form-control"
                               data-required-msg="{{ translate(':field is required', ['field' => translate('Title')]) }}"
                               placeholder="{{ translate('New notification') }}"
                               data-maxlength="100" required>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/100</span>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="notification-description">
                            {{ translate('Description') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="description" id="notification-description" rows="4"
                                  class="form-control text-area-max-min"
                                  data-required-msg="{{ translate(':field is required', ['field' => translate('Description')]) }}"
                                  data-maxlength="191" required></textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/191</span>
                        </div>
                    </div>
                </div>

                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="d-flex flex-column gap-20 text-center">
                        <div>
                            <label class="form-label fw-semibold mb-1">
                                {{ translate('Image') }} <span class="text-info-dark">({{ translate('Ratio') . ' ' . '1:1' }})</span>
                            </label>
                            <p class="fs-12 mb-0">{{ translate('Upload the image shown with this notification') }}</p>
                        </div>
                        <div class="upload-file">
                            <input type="file" name="image" class="upload-file__input single_file_input"
                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                   accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}">
                            <label class="upload-file__wrapper">
                                <div class="upload-file-textbox text-center">
                                    <img width="34" height="34" class="svg"
                                         src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}" alt="image upload">
                                    <h6 class="mt-1 fw-medium lh-base text-center">
                                        <span class="text-info">{{ translate('Click to upload') }}</span><br>
                                        {{ translate('Or drag and drop') }}
                                    </h6>
                                </div>
                                <img class="upload-file-img" loading="lazy" src="" data-default-src="" alt="">
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
                            {{ getFileUploadFormats(skip: '.svg, .gif', asBladeMessage: true) . ' ' . translate('Image size') . ' : ' . translate('Max') . ' ' . getFileUploadMaxSize() . 'MB' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">{{ translate('Reset') }}</button>
                <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                        class="btn btn-primary flex-grow-1 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}">
                    {{ translate('Send notification') }}
                </button>
            </div>
        </div>
    </div>
</form>
