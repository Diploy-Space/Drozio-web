<form action="{{ route('admin.retail.attribute.store') }}" method="post"
      class="text-start form-advance-validation form-advance-inputs-validation non-ajax-form-validate"
      novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="attributeAddOffcanvas"
         aria-labelledby="attributeAddOffcanvasLabel" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1" id="attributeAddOffcanvasLabel">{{ translate('Add Attribute') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded">
                <div class="table-responsive w-auto overflow-y-hidden mb-20">
                    <div class="position-relative nav--tab-wrapper">
                        <ul class="nav nav-pills nav--tab lang_tab" id="pills-tab" role="tablist">
                            @foreach ($language as $lang)
                                <li class="nav-item p-0">
                                    <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}-attribute-add-form" role="tab"
                                       class="nav-link px-2 {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                       id="{{ $lang }}-attribute-add-link">
                                        {{ getLanguageName($lang) . ' (' . strtoupper($lang) . ')' }}
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
                    @foreach ($language as $lang)
                        <div class="tab-pane fade {{ $lang == $defaultLanguage ? 'show active' : '' }}"
                             id="{{ $lang }}-attribute-add-form" aria-labelledby="{{ $lang }}-attribute-add-link" role="tabpanel">
                            <div class="form-group mb-0">
                                <label class="form-label" for="attribute-add-name-{{ $lang }}">
                                    {{ translate('Attribute Name') }}
                                    @if ($lang == $defaultLanguage)<span class="text-danger">*</span>@endif
                                    ({{ strtoupper($lang) }})
                                </label>
                                <input type="text" name="name[]" class="form-control"
                                       id="attribute-add-name-{{ $lang }}"
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('Attribute Name')]) }}"
                                       placeholder="{{ translate('Enter Attribute Name') }}"
                                       {{ $lang == $defaultLanguage ? 'required' : '' }}>
                            </div>
                        </div>
                        <input type="hidden" name="lang[]" value="{{ $lang }}">
                    @endforeach
                </div>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Submit') }}</button>
            </div>
        </div>
    </div>
</form>
