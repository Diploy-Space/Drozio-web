<form action="{{ route('admin.retail.deal.day') }}" method="post"
      class="text-start form-advance-validation form-advance-inputs-validation non-ajax-form-validate"
      novalidate="novalidate">
    @csrf
    <div class="offcanvas offcanvas-end" tabindex="-1" id="dealOfTheDayAddOffcanvas"
         aria-labelledby="dealOfTheDayAddOffcanvasLabel" style="--bs-offcanvas-width: 500px;">

        <div class="offcanvas-header gap-3 justify-content-between bg-body">
            <h2 class="mb-0 flex-grow-1" id="dealOfTheDayAddOffcanvasLabel">{{ translate('Add new deal') }}</h2>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-20">
                <div class="p-12 p-sm-20 bg-section rounded">
                    <div class="table-responsive w-auto overflow-y-hidden mb-20">
                        <div class="position-relative nav--tab-wrapper">
                            <ul class="nav nav-pills nav--tab lang_tab px-0" role="tablist">
                                @foreach($language as $lang)
                                    <li class="nav-item p-0">
                                        <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}-day-add-form" role="tab"
                                           class="nav-link px-2 {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                           id="{{ $lang }}-day-add-link">
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

                    <div class="tab-content">
                        @foreach($language as $lang)
                            <div class="tab-pane fade {{ $lang == $defaultLanguage ? 'show active' : '' }}"
                                 id="{{ $lang }}-day-add-form" aria-labelledby="{{ $lang }}-day-add-link" role="tabpanel">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="day-add-title-{{ $lang }}">
                                        {{ translate('Title') }} ({{ strtoupper($lang) }})
                                        @if($lang == $defaultLanguage)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text" name="title[]" id="day-add-title-{{ $lang }}" class="form-control"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Title')]) }}"
                                           placeholder="{{ translate('Ex').' : '.translate('Daily Spotlight Deal') }}"
                                           data-maxlength="100" {{ $lang == $defaultLanguage ? 'required' : '' }}>
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/100</span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                        @endforeach
                    </div>
                </div>

                <div class="p-12 p-sm-20 bg-section rounded">
                    <label class="form-label">{{ translate('Products') }} <span class="text-danger">*</span></label>
                    <div class="dropdown select-product-search w-100"
                         data-search-url="{{ route('admin.retail.products.search-all-type-product') }}"
                         data-all-products-option="0">
                        <input type="text" class="product_id" name="product_id" hidden>
                        <button class="form-select bg-transparent shadow-none text-start dropdown-toggle line-1 word-break select-product-button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button">
                            {{ translate('Select Product') }}
                        </button>
                        <div class="dropdown-menu w-100 px-2">
                            <div class="search-form mb-3">
                                <button type="button" class="btn"><i class="fi fi-rr-search"></i></button>
                                <input type="text" class="js-form-search form-control search-bar-input search-all-type-product"
                                       placeholder="{{ translate('Search menu').'...' }}">
                            </div>
                            <div class="d-flex flex-column gap-3 max-h-40vh overflow-y-auto overflow-x-hidden search-result-box">
                                @include('admin-views.partials._search-product', [
                                    'products' => $products,
                                    'showAllProducts' => false,
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                <p class="fs-12 mb-0">
                    {{ translate('Only one deal of the day runs at a time, so a new deal is added switched off. Turn it on when you want it to replace the running deal.') }}
                </p>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center flex-wrap gap-3 bg-white px-3 py-2">
                <button type="reset" class="btn btn-secondary flex-grow-1 reset-button">{{ translate('Reset') }}</button>
                <button type="submit" class="btn btn-primary flex-grow-1">{{ translate('Submit') }}</button>
            </div>
        </div>
    </div>
</form>
