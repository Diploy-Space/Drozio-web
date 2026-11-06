<form
    action="{{ route('admin.retail.category.update') }}"

    method="POST" class="form-advance-validation form-advance-file-validation non-ajax-form-validate"
      enctype="multipart/form-data" novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="categoryEditOffcanvas-{{ $category['id'] }}"
         aria-labelledby="categoryEditOffcanvasLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h3 class="mb-0 flex-grow-1">
                @if ($category['position'] == 1)
                    {{ translate('Edit Sub Category') }}
                @elseif($category['position'] == 2)
                    {{ translate('Edit Sub Sub Category') }}
                @else
                    {{ translate('Edit Category') }}
                @endif
            </h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                @if($category['position'] == 0)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-12 p-sm-20 bg-section rounded">
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
                                    {{ $category['home_status'] == 1 ? 'checked' : '' }}>
                                <span class="switcher_control"></span>
                            </label>
                        </label>
                    </div>
                @endif

                <div class="p-12 p-sm-20 bg-section rounded">
                    <input type="hidden" name="id" value="{{ $category['id'] }}">
                    <input type="hidden" name="position" value="{{ $category['position'] }}">
                    <div class="table-responsive w-auto overflow-y-hidden mb-20">
                        <div class="position-relative nav--tab-wrapper">
                            <ul class="nav nav-pills nav--tab lang_tab px-0" id="pills-tab" role="tablist">
                                @foreach ($languages as $lang)
                                    <li class="nav-item p-0">
                                        <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}-edit-form-{{ $category['id'] }}" role="tab"
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
                                 id="{{ $lang }}-edit-form-{{ $category['id'] }}"
                                 aria-labelledby="{{ $lang }}-link" role="tabpanel">
                                <?php
                                    if (count($category['translations'])) {
                                        $translate = [];
                                        foreach ($category['translations'] as $t) {
                                            if ($t->locale == $lang && $t->key == 'name') {
                                                $translate[$lang]['name'] = $t->value;
                                            }
                                        }
                                    }
                                    $categoryName = ($category['position'] == 1) ? "sub_category_Name" : (($category['position'] == 2) ? "sub_sub_category_Name" : "category_Name");
                                    $validationMessage = ($category['position'] == 1) ? "sub_category_name_is_required" : (($category['position'] == 2) ? "sub_sub_category_name_is_required" : "category_name_is_required");
                                ?>
                                <div class="form-group mb-0">
                                    <label class="form-label text-capitalize">
                                        {{ translate($categoryName) }} ({{ strtoupper($lang) }})
                                        @if($lang == $defaultLanguage)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text" name="name[]" data-maxlength="50"
                                           value="{{ $lang == $defaultLanguage ? $category['name'] : $translate[$lang]['name'] ?? '' }}"
                                           data-required-msg="{{ translate($validationMessage) }}"
                                           class="form-control"
                                           @if ($category['position'] == 1)
                                               placeholder="{{ translate('Type sub category name') }}"
                                           @elseif($category['position'] == 2)
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
                                <input type="hidden" name="id" value="{{ $category['id'] }}">
                            </div>
                        @endforeach
                    </div>

                    @if($category['position'] == 1 && isset($parentCategories))
                        <div class="form-group mb-20 mt-20">
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
                                    data-placeholder="{{ translate('Select') }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Main Category')]) }}" required>
                                <option value=""></option>
                                @foreach($parentCategories as $parentCategory)
                                    <option value="{{ $parentCategory['id'] }}"
                                        {{ $parentCategory['id'] == $category['parent_id'] ? 'selected' : '' }}>
                                        {{ $parentCategory['defaultname']}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($category['position'] == 2)
                        <input type="hidden" name="parent_id" value="{{ $category['parent_id'] }}">
                    @endif

                    <div class="form-group mb-20 mt-20">
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
                                    data-required-msg="{{ translate(':field is required', ['field' => translate('Priority')]) }}"
                                    required>
                                <option></option>
                                @for ($index = 0; $index <= 10; $index++)
                                    <option value="{{ $index }}"
                                        {{ $category['priority'] == $index ? 'selected' : '' }}>
                                        {{ $index }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    @if ($category->position == 0)
                        @if ($categoryWiseTax)
                            <div class="form-group mb-20 mt-20">
                                <label class="form-label" for="tax-ids-update-{{ $category['id'] }}">
                                    {{ translate('Select Vat/Tax Rate') }}
                                    <span class="input-required-icon">*</span>
                                </label>

                                <select class="custom-select multiple-select2" id="tax-ids-update-{{ $category['id'] }}"
                                        name="tax_ids[]" multiple="multiple"
                                        data-placeholder="{{ translate('Type & Select Vat/Tax Rate') }}">
                                    @foreach ($taxVats as $taxVat)
                                        <option {{ in_array($taxVat->id, $category?->taxVats->pluck('tax_id')->toArray()) ? 'selected' : '' }}
                                                value="{{ $taxVat->id }}"> {{ $taxVat->name }}
                                            ({{ $taxVat->tax_rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endif

                    @if ($category->position == 0 || ($category->position == 1 && theme_root_path() == 'theme_aster'))
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
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('Category Logo')]) }}"
                                       accept="{{getFileUploadFormats(skip: '.svg,.gif')}}">
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
                                    <img class="upload-file-img" loading="lazy"
                                        src="{{ getStorageImages(path: $category->icon_full_url, type: 'backend-basic') ?? '' }}"
                                        data-default-src="{{ getStorageImages(path: $category->icon_full_url, type: 'backend-basic') ?? '' }}"
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
                               class="form-control" id="meta_title-{{ $category['id'] }}" value="{{ $category?->seo?->title ?? '' }}">
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
                        <textarea rows="4" type="text" name="meta_description" id="meta_description-{{ $category['id'] }}" data-maxlength="160"
                                  class="form-control" placeholder="{{ translate('Write a short description of the category') }}">{{ $category?->seo?->description }}</textarea>
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
                                <label
                                    class="upload-file__wrapper ratio-2-1">
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
                                    <img class="upload-file-img pre-meta-image-viewer" loading="lazy"
                                         src="{{ $category?->seo?->image_full_url['path'] ? getStorageImages(path: $category?->seo?->image_full_url['path'] ? $category?->seo?->image_full_url : '', type: 'backend-banner') : '' }}"
                                         data-default-src="{{ $category?->seo?->image_full_url['path'] ? getStorageImages(path: $category?->seo?->image_full_url['path'] ? $category?->seo?->image_full_url : '', type: 'backend-banner') : '' }}"
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
                <button type="reset" id="reset" class="btn btn-secondary flex-grow-1">
                    {{ translate('Reset') }}
                </button>
                <button type="submit" class="btn btn-primary flex-grow-1">
                    {{ translate('Update') }}
                </button>
            </div>
        </div>
    </div>
</form>
