<div class="general_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0 pc-header-ai-btn">
                <div>
                    <h2 class="mb-1">{{ translate('General Setup') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Here you can setup the foundational details required for product creation.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 general_setup_auto_fill"
                    id="general_setup_auto_fill" data-route="{{ route('admin.retail.product.general-setup-auto-fill') }}" data-item=""  data-lang="en">
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
                    <div class="row gy-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate('Product Type') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select name="product_type" id="product_type" class="form-select" data-required-msg="{{ translate(':field is required', ['field' => translate('Product Type')]) }}" required>
                                        <option value="physical" {{ $product->product_type == 'physical' ? 'selected' : ''}}>
                                            {{ translate('Physical') }}
                                        </option>
                                        @if($digitalProductSetting)
                                            <option value="digital" {{ $product->product_type == 'digital' ? 'selected' : ''}}>
                                                {{ translate('Digital') }}
                                            </option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    {{ translate('Category') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <select class="custom-select action-get-request-onchange"
                                        name="category_id"
                                        id="category_id"
                                        data-url-prefix="{{ url('/admin/retail/products/get-categories?parent_id=') }}"
                                        data-element-id="sub-category-select"
                                        data-element-type="select"
                                        data-placeholder="{{ translate('Select category') }}"
                                        data-required-msg="{{ translate('Category field is required') }}" required
                                        required>
                                    <option value="{{ old('category_id') }}" selected disabled>
                                        {{ translate('Select category') }}
                                    </option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category['id'] }}"
                                            {{ $category->id == $product['category_id'] ? 'selected' : '' }}>
                                            {{ $category['defaultName'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="name" class="form-label">{{ translate('Subcategory') }}</label>
                                <select
                                    class="custom-select action-get-request-onchange"
                                    name="sub_category_id" id="sub-category-select"
                                    data-id="{{ $product['sub_category_id'] }}"
                                    data-url-prefix="{{ url('/admin/retail/products/get-categories?parent_id=') }}"
                                    data-element-id="sub-sub-category-select"
                                    data-element-type="select"
                                    data-placeholder="{{ translate('Select Sub Category') }}">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="name" class="form-label">{{ translate('Sub Sub Category') }}</label>
                                <select class="custom-select"
                                        name="sub_sub_category_id"
                                        id="sub-sub-category-select"
                                        data-id="{{ $product['sub_sub_category_id'] }}"
                                        data-placeholder="{{ translate('Select Sub Sub Category') }}"
                                >
                                </select>
                            </div>
                        </div>
                        @if($brandSetting)
                            <div class="col-md-6 col-lg-4 show-for-physical-product">
                                <div class="form-group">
                                    <label class="form-label">
                                        {{ translate('Brand') }}
                                    </label>
                                    <select class="custom-select" name="brand_id" id="brand_id">
                                        <option value="{{ null }}" selected disabled>
                                            {{ translate('Select Brand') }}
                                        </option>
                                        <option value="{{ null }}">
                                            {{ translate('No Brand') }}
                                        </option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand['id'] }}"
                                                {{ $brand['id'] == $product->brand_id ? 'selected' : ''}}>
                                                {{ $brand['defaultName'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6 col-lg-4 show-for-digital-product">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate("Author") }}/{{ translate("Creator") }}/{{ translate("Artist") }}
                                </label>
                                <select class="custom-select tags" name="authors[]" multiple="multiple" id="mySelect">
                                    @foreach($digitalProductAuthors as $authors)
                                        <option value="{{ $authors['name'] }}" {{ in_array($authors['id'], $productAuthorIds) ? 'selected' : '' }}>
                                            {{ $authors['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 show-for-digital-product">
                            <div class="form-group">
                                <label class="form-label">{{ translate('Publishing House') }}</label>
                                <select class="custom-select tags" name="publishing_house[]" multiple="multiple">
                                    @foreach($publishingHouseList as $publishingHouse)
                                        <option value="{{ $publishingHouse['name'] }}"
                                            {{ in_array($publishingHouse['id'], $productPublishingHouseIds) ? 'selected' : '' }}>
                                            {{ $publishingHouse['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 show-for-digital-product">
                            <div class="form-group">
                                <label for="digital-product-type-input" class="form-label">
                                    {{ translate('Delivery type') }}
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{
                                                    translate('Customers pay and instantly download pre-uploaded digital products.').' '.
                                                    translate('Customers pay first, then admin uploads the digital products for download.') }}"
                                          data-bs-title="{{
                                                    translate('Customers pay and instantly download pre-uploaded digital products.').' '.
                                                    translate('Customers pay first, then admin uploads the digital products for download.') }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <div class="select-wrapper">
                                    <select name="digital_product_type" id="digital-product-type-input" class="form-select"
                                            >
                                        <option value="ready_after_sell" {{ $product->retailDetail?->digital_product_type == 'ready_after_sell' ? 'selected' : ''}}>
                                            {{ translate('Ready After Sell') }}
                                        </option>
                                        <option value="ready_product" {{ $product->retailDetail?->digital_product_type == 'ready_product' ? 'selected' : ''}}>
                                            {{ translate('Ready Product') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label d-flex justify-content-between gap-2">
                                    <span class="d-flex align-items-center gap-2">
                                        {{ translate('Product SKU') }}
                                        <span class="input-required-icon">*</span>
                                        <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                              aria-label="{{ translate('Create a unique product code by clicking on the Generate Code button') }}"
                                              data-bs-title="{{ translate('Create a unique product code by clicking on the Generate Code button') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </span>
                                    <span
                                        class="style-one-pro cursor-pointer user-select-none text-primary action-onclick-generate-number"
                                        data-input="#generate-sku-code">
                                        {{ translate('Generate code') }}
                                    </span>
                                </label>
                                <input type="text" minlength="6" id="generate-sku-code" name="code"
                                       class="form-control" value="{{ request('product-gallery') ? ' ' : $product->code }}"
                                       data-required-msg="{{ translate(':field is required', ['field' => translate('Sku code')]) }}"
                                       placeholder="{{ translate('Ex'). ': YU62TN' }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 show-for-physical-product">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate('Unit') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select class="form-select" name="unit" id="unit">
                                        @foreach (units() as $unit)
                                            <option value={{ $unit }} {{ $product->unit == $unit ? 'selected' : '' }}>
                                                {{ $unit }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="form-label d-flex align-items-center gap-2">
                                    {{ translate('Search Tags') }}
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Search tags customers can use to find this product.') }}"
                                          data-bs-title="{{ translate('Search tags customers can use to find this product.') }}">
                                          <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <input type="text" class="form-control" name="tags" id="tags" placeholder="{{ translate('Enter tag') }}"
                                       value="@foreach($product->tags as $c) {{ $c->tag.','}} @endforeach"
                                       data-role="tagsinput">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

