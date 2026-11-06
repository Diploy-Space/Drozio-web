<form
    @if ($categoryType == 'sub_category')
        action="{{ route('admin.retail.sub-category.store') }}"
    @elseif($categoryType == 'sub_sub_category')
        action="{{ route('admin.retail.sub-sub-category.store') }}"
    @else
        action="{{ route('admin.retail.category.store') }}"
    @endif
    method="POST" class="form-advance-validation form-advance-file-validation form-advance-inline-validation non-ajax-form-validate"
      enctype="multipart/form-data" >
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="categoryAddOffcanvas"
         aria-labelledby="categoryAddOffcanvasLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">
                @if ($categoryType == 'sub_category')
                    {{ translate('Add Sub Category') }}
                @elseif($categoryType == 'sub_sub_category')
                    {{ translate('Add Sub Sub Category') }}
                @else
                    {{ translate('Add Category') }}
                @endif
            </h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                @if($categoryType == 'category')
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <label for="" class="form-label mb-0 flex-grow-1 d-flex align-items-center gap-1">
                            {{ translate('Home Category Status') }}
                            <span class="tooltip-icon"
                                  data-bs-toggle="tooltip" data-bs-placement="bottom"
                                  aria-label="{{ translate('Shows this category on the home page.') }}"
                                  data-bs-title="{{ translate('Shows this category on the home page.') }}"
                            >
                            <i class="fi fi-sr-info d-flex"></i>
                        </span>
                        </label>
                        <label
                            class="d-flex justify-content-between align-items-center gap-2 border rounded px-3 py-10 user-select-none flex-grow-1">
                            <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                            <label class="switcher">
                                <input type="checkbox" class="switcher_input" value="1" id="" name="home_status"
                                       checked>
                                <span class="switcher_control"></span>
                            </label>
                        </label>
                    </div>
                @endif

                <div class="p-12 p-sm-20 bg-section rounded">
                    @if ($categoryType == 'sub_category')
                        <input type="hidden" name="position" value="1">
                    @elseif($categoryType == 'sub_sub_category')
                        <input type="hidden" name="position" value="2">
                    @else
                        <input type="hidden" name="position" value="0">
                    @endif

                    <div class="table-responsive w-auto overflow-y-hidden mb-20">
                        <div class="position-relative nav--tab-wrapper">
                            <ul class="nav nav-pills nav--tab lang_tab px-0" id="pills-tab" role="tablist">
                                @foreach ($languages as $lang)
                                    <li class="nav-item px-0">
                                        <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}-add-form-category-name" role="tab"
                                           class="nav-link px-2 {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                           id="{{ $lang }}-link">
                                            {{ ucfirst(getLanguageName($lang)) . '(' . strtoupper($lang) . ')' }}
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
                        @foreach ($languages as $lang)
                            <div class="tab-pane fade {{ $lang == $defaultLanguage ? 'show active' : '' }}"
                                 id="{{ $lang }}-add-form-category-name"
                                 aria-labelledby="{{ $lang }}-link" role="tabpanel">
                                <div class="form-group mb-20">
                                    <label class="form-label text-capitalize">
                                        @if ($categoryType == 'sub_category')
                                            {{ translate('Sub Category Name') }}
                                        @elseif($categoryType == 'sub_sub_category')
                                            {{ translate('Sub sub category name') }}
                                        @else
                                            {{ translate('Category name') }}
                                        @endif
                                        ({{ strtoupper($lang) }})
                                        @if($lang == $defaultLanguage)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text" name="name[]"
                                           value="" data-maxlength="50"
                                           @if ($categoryType == 'category')
                                               data-required-msg="{{ translate('The name field is required') }}"
                                           @else
                                               data-required-msg="{{ translate(':field is required', ['field' => translate('Category Name')]) }}"
                                           @endif
                                           class="form-control"
                                            @if ($categoryType == 'sub_category')
                                                placeholder="{{ translate('Type sub category name') }}"
                                            @elseif($categoryType == 'sub_sub_category')
                                                placeholder="{{ translate('Type sub sub category name') }}"
                                            @else
                                                placeholder="{{ translate('Type category name') }}"
                                            @endif
                                        {{ $lang == $defaultLanguage ? 'required' : '' }}>
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/50</span>
                                    </div>
                                </div>
                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                            </div>
                        @endforeach
                    </div>

                    @if($categoryType == 'sub_category' && isset($parentCategories))
                        <div class="form-group mb-20">
                            <label class="form-label d-flex align-items-center gap-1"
                                   for="exampleFormControlSelect1">{{ translate('Main Category') }}
                                <span class="text-danger">*</span>
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                      aria-label="{{ translate('Select the main category under which this item will be listed.') }}"
                                      data-bs-title="{{ translate('Select the main category under which this item will be listed.') }}">
                                    <i class="fi fi-sr-info d-flex"></i>
                                </span>
                            </label>
                            <select class="custom-select form-control" name="parent_id"
                                    data-placeholder="{{ translate('Select') }}" data-required-msg="{{ translate('Main Category is required') }}" required>
                                <option value=""></option>
                                @foreach($parentCategories as $parentCategory)
                                    <option value="{{ $parentCategory['id'] }}">
                                        {{ $parentCategory['defaultname']}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($categoryType == 'sub_sub_category' && isset($parentCategories))
                        <div class="form-group mb-20">
                            <label
                                class="form-label">{{ translate('Main Category') }}
                                <span class="text-danger">*</span></label>
                            <div class="select-wrapper">
                                <select class="form-select action-get-sub-category-onchange"
                                        data-required-msg="{{ translate(':field is required', ['field' => translate('Main Category')]) }}"
                                        id="main-category" data-route="{{ route('admin.retail.sub-sub-category.get-sub-category') }}" required>
                                    <option disabled
                                            selected>{{ translate('Select Main Category') }}</option>
                                    @foreach($parentCategories as $category)
                                        <option
                                            value="{{ $category['id']}}">{{ $category['defaultName']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label text-capitalize" for="parent_id">
                                {{ translate('Sub Category Name') }}<span
                                    class="text-danger">*</span>
                            </label>
                            <div class="select-wrapper">
                                <select name="parent_id" id="parent_id" class="form-select"
                                        data-required-msg="{{ translate(':field is required', ['field' => translate('Sub Category Name')]) }}" required>
                                    <option value="" disabled selected>
                                        {{ translate('Select Sub Category First') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="form-group mb-20">
                        <label class="form-label text-capitalize d-flex align-items-center gap-1" for="priority">
                            {{ translate('Priority') }}<span class="text-danger">*</span>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                  aria-label="{{ translate('The lowest number will get the highest priority') }}"
                                  data-bs-title="{{ translate('The lowest number will get the highest priority') }}">
                                    <i class="fi fi-sr-info d-flex"></i>
                                </span>
                        </label>
                        <div>
                            <select class="custom-select" name="priority" id=""
                                    data-placeholder="{{ translate('Set Priority') }}"
                                    data-required-msg="{{ translate(':field is required', ['field' => translate('Category priority')]) }}"
                                    required>
                                <option></option>
                                @for ($index = 0; $index <= 10; $index++)
                                    <option value="{{ $index }}">
                                        {{ $index }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    @if ($categoryType == 'category')
                        @if ($categoryWiseTax)
                            <div class="form-group mb-20">
                                <label class="form-label" for="tax-ids-for-category-add">
                                    {{ translate('Select Vat/Tax Rate') }}
                                    <span class="input-required-icon">*</span>
                                </label>

                                <select class="custom-select multiple-select2" id="tax-ids-for-category-add"
                                        name="tax_ids[]" multiple="multiple"
                                        data-placeholder="{{ translate('Type & Select Vat/Tax Rate') }}"
                                        data-required-msg="{{ translate('Please add your category tax') }}!" required>
                                    @foreach ($taxVats as $taxVat)
                                        <option value="{{ $taxVat->id }}"> {{ $taxVat->name }}
                                            ({{ $taxVat->tax_rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endif

                    @if ($categoryType == 'category' || ($categoryType == 'sub_category' && theme_root_path() == 'theme_aster'))
                        <div class="d-flex flex-column gap-20">
                            <div class="text-center">
                                <label for="" class="form-label fw-semibold mb-1">
                                    {{ translate('Category Logo') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <p class="fs-12 mb-0"> {{ translate('Upload image') }}</p>
                            </div>
                            <div class="upload-file">
                                <input type="file" name="image" id="category-image"
                                    class="upload-file__input single_file_input"
                                    data-max-size="{{ getFileUploadMaxSize() }}"
                                    data-required-msg="{{ translate('Category image is required') }}" required
                                    accept="{{getFileUploadFormats(skip: '.svg,.gif')}},image/*">
                                <label class="upload-file__wrapper">
                                    <div class="upload-file-textbox text-center">
                                        <img width="34" height="34" class="svg"
                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                            alt="image upload">
                                        <h6 class="mt-1 fw-medium lh-base text-center">
                                                <span class="text-info">
                                                    {{ translate('Click to upload') }}
                                                </span><br>
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
                                {{ translate(getFileUploadFormats(skip: '.svg,.gif', asMessage: 'true')) }} {{ translate(': max 2 MB') }}
                                <span class="text-dark fw-medium">
                                    {{ THEME_RATIO[theme_root_path()]['Category Image'] }}
                                </span>
                            </p>
                        </div>
                    @endif
                </div>


                <div class="p-12 p-sm-20 bg-section rounded">
                    <h3 class="border-bottom pb-2 mb-3">{{ translate('Meta Data Setup') }}</h3>
                    <div class="form-group mb-20">
                        <label class="form-label d-flex align-items-center gap-1">
                            {{ translate('Meta Title') }}
                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  title="{{ translate('Add the category name taglines etc here.').' '.translate('Meta title shown in search results and when the category is shared.') .' [ '. translate('Character Limit') }} : 50 ]">
                                <i class="fi fi-sr-info d-flex"></i>
                            </span>
                        </label>
                        <input type="text" name="meta_title" placeholder="{{ translate('Meta Title') }}" data-maxlength="50"
                               class="form-control" id="meta_title-add">
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/50</span>
                        </div>
                    </div>
                    <div class="form-group mb-20">
                        <label class="form-label d-flex align-items-center gap-1">
                            {{ translate('Meta Description') }}
                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  title="{{ translate('Write a short description of the category').' '.translate('Description shown in search results and when the category is shared.') .' [ '. translate('Character Limit') }} : 160 ]">
                                <i class="fi fi-sr-info d-flex"></i>
                            </span>
                        </label>
                        <textarea rows="4" type="text" name="meta_description" id="meta_description-add" data-maxlength="160"
                                  class="form-control" placeholder="{{ translate('Enter your meta description') }}"></textarea>
                        <div class="d-flex justify-content-end">
                            <span class="text-body-light">0/160</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-20">
                        <div class="text-center">
                            <label for="" class="form-label fw-semibold mb-1 d-flex align-items-center justify-content-center gap-1">
                                {{ translate('Meta Image') }}
                                <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                      aria-label="{{ translate('Add Meta Image in') }} JPG, PNG or JPEG {{ translate('Format within') }} 2MB, {{ translate('Which will be shown in search engine results') }}."
                                      data-bs-title="{{ translate('Add Meta Image in') }} JPG, PNG or JPEG {{ translate('Format within') }} 2MB, {{ translate('Which will be shown in search engine results') }}."
                                >
                                    <i class="fi fi-sr-info d-flex"></i>
                                </span>
                            </label>
                            <p class="fs-12 mb-0"> {{ translate('Upload meta image') }}</p>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="upload-file">
                                <input type="file" name="meta_image"
                                       class="upload-file__input single_file_input"
                                       data-max-size="{{ getFileUploadMaxSize() }}"
                                       id="meta_image_input"
                                       accept="{{ getFileUploadFormats(skip:'.svg,.gif') }}"
                                       value=""
                                >
                                <label class="upload-file__wrapper ratio-2-1">
                                    <div class="upload-file-textbox text-center">
                                        <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap">
                                            <img width="34" height="34"
                                                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                alt="image upload">
                                            <h6 class="mt-1 mb-0 fw-medium lh-base">
                                                <span class="text-info">{{ translate('Click to upload') }}</span>
                                                <br>
                                                {{ translate('Or drag and drop') }}
                                            </h6>
                                        </div>
                                    </div>
                                    <img class="upload-file-img pre-meta-image-viewer" loading="lazy" src=""
                                         data-default-src="" alt="">
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
                        </div>
                        <p class="fs-10 mb-0 text-center">
                            {{ translate(getFileUploadFormats(skip: '.svg,.gif', asMessage: 'true')) }} {{ translate(': max 2 MB') }}
                            <span class="text-dark fw-medium">
                                {{ THEME_RATIO[theme_root_path()]['Meta Thumbnail'] }}
                            </span>
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">
                    {{ translate('Reset') }}
                </button>
                <button type="submit" class="btn btn-primary flex-grow-1">
                    {{ translate('Submit') }}
                </button>
            </div>
        </div>
    </div>
</form>
