<div class="price_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0 pc-header-ai-btn">
                <div>
                    <h2 class="mb-1">{{ translate('Pricing & Others') }}</h2>
                    <p class="fs-12 mb-0">
                        {{ translate('Here you can setup the price and other information for the product.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 price_others_auto_fill"
                    id="price_others_auto_fill"
                        data-route="{{ route('admin.retail.product.price-others-auto-fill') }}"  data-lang="en">
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
                    <div class="row gy-4 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate('Unit Price') }}
                                    <span class="input-required-icon">*</span>
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Unit selling price. Ignored when variation pricing is set.') }}"
                                          data-bs-title="{{ translate('Unit selling price. Ignored when variation pricing is set.') }}"
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <input type="number" min="0" step="0.01"
                                       placeholder="{{ translate('Unit Price') }}" name="unit_price" id="unit_price"
                                       value="{{ old('unit_price') }}" class="form-control"  data-required-msg="{{ translate(':field is required', ['field' => translate('Unit Price')]) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4" id="">
                            <div class="form-group">
                                <label class="form-label" for="minimum_order_qty">
                                    {{ translate('Minimum Order Qty') }}
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Minimum order quantity. Required to start checkout.') }}."
                                          data-bs-title="{{ translate('Minimum order quantity. Required to start checkout.') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="number" min="1" value="1" step="1"
                                       placeholder="{{ translate('Minimum Order Quantity') }}" name="minimum_order_qty"
                                       id="minimum_order_qty" class="form-control only-number-input">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 show-for-physical-product" id="quantity">
                            <div class="form-group">
                                <label class="form-label" for="current_stock">
                                    {{ translate('Current Stock Qty') }}
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Stock quantity shown to customers.') }}."
                                          data-bs-title="{{ translate('Stock quantity shown to customers.') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <input type="number" min="0" value="0" step="1"
                                       placeholder="{{ translate('Quantity') }}" name="current_stock" id="current_stock"
                                       class="form-control only-number-input"  data-required-msg="{{ translate(':field is required', ['field' => translate('Current Stock')]) }}" required>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label class="form-label" for="discount">
                                    {{ translate('Discount Amount') }}
                                    <span class="discount-amount-symbol" data-percent="%"
                                          data-currency="{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}">
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    </span>
                                    <span class="input-required-icon">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Add the discount amount in percentage or a fixed value here') }}."
                                          data-bs-title="{{ translate('Add the discount amount in percentage or a fixed value here') }}."
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="number" min="0" value="0" step="0.01"
                                           placeholder="{{ translate('Ex') }}: 5"
                                           name="discount" id="discount" class="form-control"  data-required-msg="{{ translate(':field is required', ['field' => translate('Discount')]) }}" required>
                                    <div class="input-group-append select-wrapper">
                                        <select class="form-control form-select shadow-none product-discount-type" name="discount_type" id="product-discount-type">
                                            <option value="flat">{{ translate('Flat') }}</option>
                                            <option value="percent">{{ translate('Percent') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($productWiseTax)
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="form-label" for="">
                                        {{ translate('Select Vat/Tax Rate') }}
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <select class="custom-select multiple-select2 multiple-select-tax-input"
                                            name="tax_ids[]" multiple="multiple"
                                            data-placeholder="{{ translate('Type & Select Vat/Tax Rate') }}">

                                        <option value="all">{{translate('All')}}</option>

                                        @foreach ($taxVats as $taxVat)
                                            <option value="{{ $taxVat->id }}">
                                                {{ $taxVat->name }} ({{ $taxVat->tax_rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6 col-lg-4 show-for-physical-product" id="shipping_cost">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ translate('Shipping Cost') }}
                                    <span class="input-required-icon">*</span>
                                    ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                          aria-label="{{ translate('Shipping cost for this product. Applies only when product-wise shipping is on.') }}"
                                          data-bs-title="{{ translate('Shipping cost for this product. Applies only when product-wise shipping is on.') }}"
                                    >
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <input type="number" min="0" value="0" step="1"
                                       placeholder="{{ translate('Shipping Cost') }}" name="shipping_cost" id="shipping_cost_input" data-required-msg="{{ translate(':field is required', ['field' => translate('Shipping Cost')]) }}"
                                       class="form-control" required>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 show-for-physical-product" id="shipping_cost_multi">
                            <div class="form-group">
                                <label class="form-label" for="shipping_cost">
                                    {{ translate('Shipping cost multiply with quantity') }}
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                      title="{{ translate('If enabled, the shipping charge will increase with the product quantity') }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>

                                <div
                                    class="form-control min-h-40 d-flex align-items-center flex-wrap justify-content-between gap-2">
                                    <label class="form-label mb-0">
                                        {{ translate('Status') }}
                                    </label>
                                    <div>
                                        <label class="switcher">
                                            <input type="checkbox" class="switcher_input" name="multiply_qty" id="is_shipping_cost_multil">
                                            <span class="switcher_control"></span>
                                        </label>
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


