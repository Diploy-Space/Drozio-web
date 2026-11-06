<div class="price_wrapper mt-3">
    <div class="outline-wrapper">
        <div class="card rest-part bg-animate">
            <div class="card-header d-flex justify-content-between align-items-center border-0 shadow-none pb-0 pc-header-ai-btn">
                <div>
                    <h3 class="mb-1">{{ translate('Pricing & Others') }}</h3>
                    <p class="fs-12 mb-0">
                        {{ translate('Here you can setup the price and other information for the product.') }}
                    </p>
                </div>

                @if(getActiveAIProviderConfigCache())
                <button type="button"
                    class="btn bg-white text-primary bg-transparent shadow-none border-0 opacity-1 generate_btn_wrapper p-0 price_others_auto_fill"
                    id="price_others_auto_fill"
                        data-route="{{ route('vendor.retail.product.price-others-auto-fill') }}"  data-lang="en">
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
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color">
                                        {{ translate('Unit Price') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Unit selling price. Ignored when variation pricing is set.') }}.">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                    </span>
                                </div>

                                <input type="number" min="0" step="0.01"
                                        placeholder="{{ translate('Unit Price') }}" id="unit_price"
                                        name="unit_price" class="form-control"
                                        value={{ usdToDefaultCurrency($product['unit_price']) }} required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4" id="minimum_order_qty">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color" for="minimum_order_qty">
                                        {{ translate('Minimum Order Qty') }}
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Minimum order quantity. Required to start checkout.') }}.">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                    </span>
                                </div>

                                <input type="number" min="1" value={{ $product['minimum_order_qty'] }} step="1"
                                        placeholder="{{ translate('Minimum Order Quantity') }}"
                                        name="minimum_order_qty" class="form-control only-number-input" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 physical_product_show" id="quantity">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color" for="current_stock">
                                        {{ translate('Current Stock Qty') }}
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Stock quantity shown to customers.') }}.">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                    </span>
                                </div>
                                <input type="number" min="0" value={{ $product['current_stock'] }} step="1"
                                        placeholder="{{ translate('Quantity') }}"
                                        name="current_stock" id="current_stock" class="form-control only-number-input" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color" for="discount">
                                        {{ translate('Discount Amount') }}
                                        <span class="discount_amount_symbol">
                                            ({{ $product->discount_type == 'flat' ? getCurrencySymbol(currencyCode: getCurrencyCode()) : '%' }})
                                        </span>
                                    </label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Add the discount amount in percentage or a fixed value here') }}.">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                    </span>
                                </div>
                                <div class="input-group input-group-merge input-group--merge">
                                    <input type="number" min="0"
                                        value="{{ $product->discount_type=='flat'?usdToDefaultCurrency($product->discount) : $product->discount }}" step="0.01"
                                        placeholder="{{ translate('Ex') }}: 5" name="discount" id="discount"
                                        class="form-control" required>
                                    <select class="form-control input-group--merge-action" name="discount_type" id="discount_type">
                                        <option
                                            value="flat" {{ $product['discount_type']=='flat'?'selected':''}}>{{ translate('Flat') }}</option>
                                        <option
                                            value="percent" {{ $product['discount_type']=='percent'?'selected':''}}>{{ translate('Percent') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        @if ($productWiseTax)
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="">
                                        {{ translate('Select Vat/Tax Rate') }}
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <select class="custom-select multiple-select2 multiple-select-tax-input" name="tax_ids[]" multiple="multiple"
                                            data-placeholder="{{ translate('Type & Select Vat/Tax Rate') }}">
                                        <option value="all">{{translate('All')}}</option>
                                        @foreach ($taxVats as $taxVat)
                                            <option value="{{ $taxVat->id }}" {{ in_array($taxVat->id, $taxVatIds) ? 'selected' : '' }}>
                                                {{ $taxVat->name }} ({{ $taxVat->tax_rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6 col-lg-4 physical_product_show" id="shipping_cost">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color">
                                        {{ translate('Shipping Cost') }}
                                        ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                        <span class="input-required-icon">*</span>
                                    </label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('Shipping cost for this product. Applies only when product-wise shipping is on.') }}">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                    </span>
                                </div>

                                <input type="number" min="0" value="{{usdToDefaultCurrency($product->shipping_cost) }}"
                                        step="1"
                                        placeholder="{{ translate('Shipping Cost') }}" id="shipping_cost"
                                        name="shipping_cost" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 physical_product_show" id="shipping_cost_multi">
                            <div class="form-group mb-0">
                                <div class="d-flex gap-2">
                                    <label class="title-color text-capitalize"
                                            for="shipping_cost">{{ translate('Shipping cost multiply with quantity') }}</label>

                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                            title="{{ translate('If enabled, the shipping charge will increase with the product quantity') }}">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                </div>
                                <div class="form-control h-auto min-form-control-height d-flex align-items-center flex-wrap justify-content-between gap-2">
                                    <div class="d-flex gap-2">
                                        <label class="title-color text-capitalize mb-0" for="shipping_cost">
                                            {{ translate('Status') }}
                                        </label>
                                    </div>

                                    <div>
                                        <label class="switcher">
                                            <input class="switcher_input" type="checkbox" name="multiply_qty"
                                                    id="is_shipping_cost_multil" {{ $product['multiply_qty'] == 1?'checked':'' }}>
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


