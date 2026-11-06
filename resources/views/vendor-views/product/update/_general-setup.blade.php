<div class="general_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 shadow-none pb-0 pc-header-ai-btn">
                <div>
                    <h3 class="mb-1">{{ translate('General Setup') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Here you can setup the foundational details required for product creation.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 general_setup_auto_fill"
                    id="general_setup_auto_fill" data-route="{{ route('vendor.retail.product.general-setup-auto-fill') }}"  data-lang="en">
                    <div class="btn-svg-wrapper">
                        <img width="18" height="18" class=""
                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/ai/blink-right-small.svg') }}" alt="">
                    </div>
                    <span class="ai-text-animation d-none" role="status">
                        {{ translate('Just a second') }}
                    </span>
                    <span class="btn-text">{{ translate('Generate') }}</span>
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="bg-section rounded-10 p-12 p-sm-20">
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label class="title-color">
                                    {{ translate('Product Type') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <select name="product_type" id="product_type" class="form-control" required>
                                    <option
                                        value="physical" {{ $product['product_type'] == 'physical' ? 'selected' : ''}}>{{ translate('Physical') }}</option>
                                    @if($digitalProductSetting)
                                        <option
                                            value="digital" {{ $product['product_type'] == 'digital' ? 'selected' : ''}}>{{ translate('Digital') }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label for="name" class="title-color">
                                    {{ translate('Category') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <select class="js-example-basic-multiple js-states js-example-responsive form-control action-get-request-onchange"
                                        name="category_id"
                                        id="category_id"
                                        data-url-prefix="{{ url('/vendor/retail/products/get-categories?parent_id=') }}"
                                        data-element-id="sub-category-select"
                                        data-element-type="select">
                                    <option value="0" selected disabled>---{{ translate('Select') }}---</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category['id']}}" {{ $category->id==$product['category_id'] ? 'selected' : ''}}>{{ $category['defaultName']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label class="title-color">{{ translate('Subcategory') }}</label>
                                <select
                                    class="js-example-basic-multiple js-states js-example-responsive form-control action-get-request-onchange"
                                    name="sub_category_id" id="sub-category-select"
                                    data-id="{{ $product['sub_category_id'] }}"
                                    data-url-prefix="{{ url('/vendor/retail/products/get-categories?parent_id=') }}"
                                    data-element-id="sub-sub-category-select"
                                    data-element-type="select">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label class="title-color">{{ translate('Sub Sub Category') }}</label>
                                <select
                                    class="js-example-basic-multiple js-states js-example-responsive form-control"
                                    data-id="{{ $product['sub_sub_category_id'] }}"
                                    name="sub_sub_category_id" id="sub-sub-category-select">
                                </select>
                            </div>
                        </div>
                        @if($brandSetting)
                            <div class="col-md-6 col-lg-4 physical_product_show">
                                <div class="form-group mb-0">
                                    <label class="title-color">
                                        {{ translate('Brand') }}
                                    </label>
                                    <select
                                        class="js-example-basic-multiple js-states js-example-responsive form-control"
                                        name="brand_id">
                                        <option value="{{null}}" selected disabled>---{{ translate('Select') }}---
                                        </option>
                                        <option value="{{ null }}">
                                            {{ translate('No Brand') }}
                                        </option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand['id']}}" {{ $brand['id']==$product['brand_id'] ? 'selected' : ''}} >
                                                {{ $brand['defaultName']}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6 col-lg-4 digital-product-sections-show">
                            <div class="form-group mb-0">
                                <label class="title-color">
                                    {{ translate("Author") }}/{{ translate("Creator") }}/{{ translate("Artist") }}
                                </label>
                                <select class="multiple-select2 form-control" name="authors[]" multiple="multiple" id="mySelect">
                                    @foreach($digitalProductAuthors as $authors)
                                        <option value="{{ $authors['name'] }}" {{ in_array($authors['id'], $productAuthorIds) ? 'selected' : '' }}>{{ $authors['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 digital-product-sections-show">
                            <div class="form-group mb-0">
                                <label class="title-color">{{ translate('Publishing House') }}</label>
                                <select class="multiple-select2 form-control" name="publishing_house[]" multiple="multiple">
                                    @foreach($publishingHouseList as $publishingHouse)
                                        <option value="{{ $publishingHouse['name'] }}"
                                            {{ in_array($publishingHouse['id'], $productPublishingHouseIds) ? 'selected' : '' }}>{{ $publishingHouse['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4" id="digital_product_type_show">
                            <div class="form-group mb-0">
                                <label for="digital_product_type"
                                        class="title-color">{{ translate('Delivery type') }}</label>
                                <span class="input-label-secondary m-0 cursor-pointer" data-toggle="tooltip"
                                        title="{{
                                        translate('Customers pay and instantly download pre-uploaded digital products.').' '.
                                        translate('Customers pay first, then the vendor uploads the digital products for download.') }}">
                                    <i class="fi fi-sr-info fs-12"></i>
                                </span>
                                <select name="digital_product_type" id="digital_product_type" class="form-control"
                                        required>
                                    <option value="{{ old('category_id') }}"
                                            {{ !$product->retailDetail?->digital_product_type ? 'selected' : ''}} disabled>
                                        ---{{ translate('Select') }}---
                                    </option>
                                    <option
                                        value="ready_after_sell" {{ $product->retailDetail?->digital_product_type == 'ready_after_sell' ? 'selected' : ''}}>{{ translate('Ready After Sell') }}</option>
                                    <option
                                        value="ready_product" {{ $product->retailDetail?->digital_product_type == 'ready_product' ? 'selected' : ''}}>{{ translate('Ready Product') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label class="title-color d-flex justify-content-between gap-2">
                                    <span class="d-flex align-items-center gap-2">
                                        {{ translate('Product SKU') }}
                                        <span class="input-required-icon">*</span>
                                        <span class="input-label-secondary m-0 cursor-pointer" data-toggle="tooltip"
                                                title="{{ translate('Create a unique product code by clicking on the Generate Code button') }}">
                                            <i class="fi fi-sr-info fs-12"></i>
                                        </span>
                                    </span>
                                    <span class="style-one-pro cursor-pointer user-select-none text--primary action-onclick-generate-number" data-input="#generate_number">
                                        {{ translate('Generate code') }}
                                    </span>
                                </label>

                                <input type="text" id="generate_number" name="code" class="form-control"
                                        value="{{request('product-gallery') ? ' ':$product->code}}" placeholder="4FOITO" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 physical_product_show">
                            <div class="form-group mb-0">
                                <label class="title-color">{{ translate('Unit') }}</label>
                                <select
                                    class="js-example-basic-multiple js-states js-example-responsive form-control"
                                    name="unit" id="unit">
                                    @foreach(units() as $unit)
                                        <option
                                            value={{ $unit}} {{ $product['unit'] == $unit ? 'selected' : ''}}>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="form-group mb-0">
                                <label class="title-color d-flex align-items-center gap-2">
                                    {{ translate('Search Tags') }}
                                    <span class="input-label-secondary m-0 cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Search tags customers can use to find this product.') }}">
                                        <i class="fi fi-sr-info fs-12"></i>
                                    </span>
                                </label>
                                <input type="text" class="form-control" name="tags" id="tags"
                                        value="@foreach($product['tags'] as $tag) {{$tag->tag.','}} @endforeach"
                                        data-role="tagsinput">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

