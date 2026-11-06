<div class="card border">
    <div class="card-header">
        <h4 class="fs-16 mb-0">{{ $moduleLabel }} {{ translate('TIN Document') }}</h4>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-section rounded-8 p-20 h-100">
                    <div class="form-group">
                        <label class="form-label mb-2" for="tin-number-{{ $moduleKey }}">
                            {{ translate('Taxpayer identification number(TIN)') }}
                        </label>
                        <input class="form-control" type="text" id="tin-number-{{ $moduleKey }}"
                               name="tin[{{ $moduleKey }}][tax_identification_number]"
                               value="{{ old('tin.'.$moduleKey.'.tax_identification_number') }}"
                               placeholder="{{ translate('Type TIN number') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label mb-2" for="tin-expire-date-{{ $moduleKey }}">
                            {{ translate('Expire Date') }}
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" id="tin-expire-date-{{ $moduleKey }}"
                                   class="js-daterangepicker_single-date-with-placeholder-add-new-vendor form-control bg-white"
                                   placeholder="{{ translate('Click to add date') }}"
                                   name="tin[{{ $moduleKey }}][tin_expire_date]"
                                   value="{{ old('tin.'.$moduleKey.'.tin_expire_date') }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex align-items-center bg-section rounded-8 p-20 w-100 h-100">
                    <div class="d-flex flex-column justify-content-center align-items-center gap-20 w-100 single_mx-100 document-upload-container">
                        <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                            <div>
                                <label class="form-label fw-semibold mb-1" for="tin-certificate-{{ $moduleKey }}">
                                    {{ translate('TIN Certificate') }}
                                </label>
                                <p class="fs-12 mb-0">
                                    {{ translate('PDF, DOC ,docx, JPG ,jpeg File Size') }}: {{ translate('Max').' '. getFileUploadMaxSize(type: 'file') . 'MB' }}
                                </p>
                            </div>
                            <div class="d-flex gap-3 align-items-center">
                                <button type="button" class="btn btn-primary icon-btn doc_edit_btn">
                                    <i class="fi fi-sr-pencil"></i>
                                </button>
                            </div>
                        </div>
                        <div class="w-100">
                            <div class="d-flex justify-content-center">
                                <div class="document-file-assets"
                                     data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
                                     data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
                                     data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/file-placeholder.png') }}">
                                </div>

                                <div class="document-existing-file"
                                     data-file-url=""
                                     data-file-name=""
                                     data-file-type="">
                                </div>
                                <div class="document-upload-wrapper mw-100 doc-upload-wrapper">
                                    <input type="file" id="tin-certificate-{{ $moduleKey }}"
                                           name="tin[{{ $moduleKey }}][tin_certificate]" class="document_input"
                                           data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                           data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize(type: 'file').' '.'MB' }}"
                                           accept=".pdf,.doc,.jpg,docx,.jpeg">
                                    <div class="textbox">
                                        <img class="svg"
                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}"
                                             alt="">
                                        <p class="fs-12 mb-0">
                                            {{ translate('Select a file or') }}
                                            <span class="fw-semibold">
                                                {{ translate('Drag and Drop here') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
