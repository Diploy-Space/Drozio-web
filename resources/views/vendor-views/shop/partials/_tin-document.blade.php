<div class="card border">
    <div class="card-header">
        <h4 class="fs-16 mb-0">{{ $document['label'] }} {{ translate('TIN Document') }}</h4>
    </div>
    <div class="card-body">
        <div class="row mb-n3">
            <div class="col-lg-8 mb-3">
                <div class="bg-light rounded-10 p-20 h-100">
                    <div class="form-group">
                        <label class="form-label text-dark" for="tin-number-{{ $moduleKey }}">
                            {{ translate('Taxpayer identification number(TIN)') }}
                        </label>
                        <input type="text" class="form-control" id="tin-number-{{ $moduleKey }}"
                               name="tin[{{ $moduleKey }}][tax_identification_number]"
                               value="{{ old('tin.'.$moduleKey.'.tax_identification_number', $document['tax_identification_number']) }}"
                               maxlength="{{ $tinMaxLength }}" pattern="^[A-Za-z0-9\-\/]+$"
                               data-maxlength-msg="{{ translate('The TIN cannot be longer than :max characters', ['max' => $tinMaxLength]) }}"
                               data-pattern-msg="{{ translate('The TIN can only contain letters, numbers, hyphens and slashes') }}"
                               placeholder="{{ translate('Type your TIN number') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label text-dark" for="tin-expire-date-{{ $moduleKey }}">
                            {{ translate('Expire Date') }}
                        </label>
                        <div class="position-relative">
                            <span class="fi fi-sr-calendar icon-absolute-on-right"></span>
                            <input type="text" id="tin-expire-date-{{ $moduleKey }}"
                                   class="js-daterangepicker_single-date-with-placeholder js-tin-expire-date form-control"
                                   name="tin[{{ $moduleKey }}][tin_expire_date]"
                                   value="{{ old('tin.'.$moduleKey.'.tin_expire_date', $document['tin_expire_date']) }}"
                                   data-clear-label="{{ translate('Clear') }}"
                                   placeholder="{{ translate('Click to add date') }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="bg-light rounded-10 p-20 h-100">
                    <div class="d-flex flex-column align-items-center single_mx-100 document-upload-container">
                        <div class="d-flex gap-4 justify-content-between mb-20 w-100">
                            <div>
                                <label class="form-label text-dark font-weight-semibold" for="tin-certificate-{{ $moduleKey }}">
                                    {{ translate('TIN Certificate') }}
                                </label>
                                <p class="fs-12 mb-0">
                                    {{ 'pdf, doc, jpg. '.translate('File Size').' : '.translate('Max').' '.getFileUploadMaxSize(type: 'file').' MB' }}
                                </p>
                            </div>
                            <div class="d-flex gap-3 align-items-center">
                                <button type="button" class="btn btn--primary btn-sm square-btn doc_edit_btn"
                                        title="{{ translate('Change file') }}">
                                    <i class="fi fi-sr-pencil"></i>
                                </button>
                                <div class="after_upload_buttons {{ $document['certificate_url'] ? '' : 'd-none' }}">
                                    <div class="d-flex gap-3 align-items-center">
                                        <button type="button" class="btn btn-success btn-sm square-btn doc_download_btn"
                                                title="{{ translate('Download') }}">
                                            <i class="fi fi-sr-download"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm square-btn doc_remove_btn"
                                                title="{{ translate('Remove file') }}">
                                            <i class="fi fi-sr-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center w-100">
                            <div class="document-file-assets"
                                 data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
                                 data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
                                 data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/file-placeholder.png') }}">
                            </div>
                            <div class="document-existing-file"
                                 data-file-url="{{ $document['certificate_url'] }}"
                                 data-file-name="{{ $document['certificate_name'] }}"
                                 data-file-type="{{ $document['certificate_type'] }}">
                            </div>
                            <input type="hidden" class="document-remove-flag"
                                   name="tin[{{ $moduleKey }}][remove_certificate]" value="0">
                            <div class="document-upload-wrapper mw-100 doc-upload-wrapper">
                                <input type="file" id="tin-certificate-{{ $moduleKey }}"
                                       name="tin[{{ $moduleKey }}][tin_certificate]" class="document_input"
                                       data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                       data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize(type: 'file').' '.'MB' }}"
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg">
                                <div class="textbox">
                                    <img class="svg"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}"
                                         alt="">
                                    <p class="fs-12 mb-0">
                                        {{ translate('Select a file or') }}
                                        <span class="font-weight-semibold">
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
