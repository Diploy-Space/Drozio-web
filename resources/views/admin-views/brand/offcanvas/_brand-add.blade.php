<form action="{{ route('admin.retail.brand.add-new') }}" method="post" enctype="multipart/form-data" class="brand-setup-form form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="brandAddOffcanvas" aria-labelledby="brandEditOffcanvasLabel" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1">{{ translate('Add Brand') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-12 p-sm-20 bg-section rounded">
                    <label for="" class="form-label mb-0 flex-grow-1">
                        {{ translate('Availability') }}
                        <span class="tooltip-icon"
                              data-bs-toggle="tooltip" data-bs-placement="bottom"
                              aria-label="{{ translate('Manage Brand status And deletion') }}"
                              data-bs-title="{{ translate('Manage Brand status And deletion') }}"
                        >
                        <i class="fi fi-sr-info"></i>
                    </span>
                    </label>
                    <label
                        class="d-flex justify-content-between align-items-center gap-2 border rounded bg-white px-3 py-10 user-select-none flex-grow-1">
                        <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                        <label class="switcher">
                            <input type="checkbox" class="switcher_input" value="1" id="" name="status" >
                            <span class="switcher_control"></span>
                        </label>
                    </label>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="table-responsive w-auto overflow-y-hidden mb-20">
                        <div class="position-relative nav--tab-wrapper">
                            <ul class="nav nav-pills nav--tab lang_tab" id="pills-tab" role="tablist">
                                @foreach($language as $lang)
                                    <li class="nav-item p-0">
                                        <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}offcanvas-form" role="tab"
                                           class="nav-link px-2 {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                           id="{{ $lang }}offcanvas-link">
                                            {{ ucfirst(getLanguageName($lang)).'('.strtoupper($lang).')' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="nav--tab__prev">
                                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                    <i class="fi fi-sr-angle-left"></i>
                                </button>
                            </div>
                            <div class="nav--tab__next">
                                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                    <i class="fi fi-sr-angle-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="pills-tabContent">
                        @foreach($language as $lang)
                            <div class="tab-pane fade {{ $lang == $defaultLanguage ? 'show active' : '' }}"
                                 id="{{ $lang }}offcanvas-form"
                                 aria-labelledby="{{ $lang }}offcanvas-link"
                                 role="tabpanel">
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        {{ translate('Name') }} ({{ strtoupper($lang) }})
                                        @if($lang == $defaultLanguage)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text" name="name[]" class="form-control"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Brand Name')]) }}"
                                           placeholder="{{ translate('Ex') }}: LUX"
                                        {{ $lang == $defaultLanguage ? 'required' : '' }}>
                                </div>
                            </div>
                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                        @endforeach
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">{{ translate('Image alt text') }}</label>
                        <input type="text" name="image_alt_text" class="form-control"
                               placeholder="{{ translate('Ex') }}: Apex Brand">
                    </div>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="d-flex flex-column gap-20 text-center">
                        <div>
                            <label class="form-label fw-semibold mb-1">{{ translate('Brand image') }} <span class="text-danger">*</span></label>
                            <p class="fs-12 mb-0">{{ translate('Upload your Brand Image') }}</p>
                        </div>

                        <div class="upload-file">
                            <input type="file" name="image" id="brand-image-add" class="upload-file__input single_file_input action-preview-for-uploaded-image"
                                   accept="{{ getFileUploadFormats(skip:'.svg,.gif') }}"
                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                   data-required-msg="{{ translate(':field is required', ['field' => translate('Brand image')]) }}" required
                                   data-preview-elements=".show-in-meta-thumbnail">
                            <label class="upload-file__wrapper">
                                <div class="upload-file-textbox text-center">
                                    <img width="34" height="34" class="svg" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}" alt="image upload">
                                    <h6 class="mt-1 fw-medium lh-base text-center">
                                        <span class="text-info">{{ translate('Click to upload') }}</span><br>
                                        {{ translate('Or drag and drop') }}
                                    </h6>
                                </div>
                                <img class="upload-file-img" loading="lazy" src=""
                                     data-default-src=""
                                     alt="">
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
                            {{ getFileUploadFormats(skip: '.svg, .gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                            <span class="text-dark fw-medium">
                                {{ THEME_RATIO[theme_root_path()]['Brand Image'] }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="border-bottom pb-2 mb-3">
                        <h2 class="text-capitalize mb-0">{{ translate('Meta Data Setup') }}</h2>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="meta_title-add">{{ translate('Meta Title') }}
                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="[{{ translate('Character Limit') }} : 50]">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <input type="text" name="meta_title" class="form-control" id="meta_title-add" placeholder="{{ translate('Meta Title') }}" data-maxlength="50">
                        <div class="d-flex justify-content-end"><span class="text-body-light">0/50</span></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="meta_description-add">{{ translate('Meta Description') }}
                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="[{{ translate('Character Limit') }} : 160]">
                                <i class="fi fi-sr-info"></i>
                            </span>
                        </label>
                        <textarea rows="2" name="meta_description" class="form-control" id="meta_description-add" data-maxlength="160" placeholder="{{ translate('Enter your meta description') }}"></textarea>
                        <div class="d-flex justify-content-end"><span class="text-body-light">0/160</span></div>
                    </div>

                    <div class="d-flex flex-column gap-20 text-center">
                        <div>
                            <label class="form-label fw-semibold mb-1">
                                {{ translate('Meta Image') }}
                            </label>
                            <p class="fs-12 mb-0">{{ translate('Upload meta image') }}</p>
                        </div>
                        <div class="upload-file">
                            <input type="file" name="meta_image" class="upload-file__input single_file_input" accept="{{ getFileUploadFormats(skip:'.svg,.gif') }}" data-max-size="{{ getFileUploadMaxSize() }}">
                            <label class="upload-file__wrapper ratio-2-1">
                                <div class="upload-file-textbox text-center">
                                    <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap">
                                        <img width="34" height="34" class="svg" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}" alt="image upload">
                                        <h6 class="mt-1 fw-medium lh-base text-center mb-0">
                                            <span class="text-info">{{ translate('Click to upload') }}</span><br>
                                            {{ translate('Or drag and drop') }}
                                        </h6>
                                    </div>
                                </div>
                                <img class="upload-file-img show-in-meta-thumbnail" loading="lazy" src=""
                                     data-default-src=""
                                     alt="">
                            </label>
                            <div class="overlay">
                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                        <i class="fi fi-sr-eye d-flex"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                        <i class="fi fi-rr-camera d-flex"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="fs-10 mb-0 text-center">{{ getFileUploadFormats(skip:'.svg,.gif') }} : {{ 'Max '.getFileUploadMaxSize().' MB' }} <span class="fw-medium">(2:1)</span></p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Save') }}</button>
            </div>
        </div>
    </div>
</form>
