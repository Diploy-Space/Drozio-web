@if($web_config['digital_product_setting'])
    <div class="">
        <h6 class="font-semibold fs-13 mb-2">{{ translate('Product Type') }}</h6>
        <label class="w-100 opacity-75 text-nowrap for-sorting d-block mb-0 ps-0" for="sorting">
            <select class="form-control product-list-filter-input" name="product_type">
                <option value="all" {{ !request('product_type') ? 'selected' : '' }}>{{ translate('All') }}</option>
                <option value="physical" {{ request('product_type') == 'physical' ? 'selected' : '' }}>
                    {{ translate('Physical') }}
                </option>
                <option value="digital" {{ request('product_type') == 'digital' ? 'selected' : '' }}>
                    {{ translate('Digital') }}
                </option>
            </select>
        </label>
    </div>
@endif
