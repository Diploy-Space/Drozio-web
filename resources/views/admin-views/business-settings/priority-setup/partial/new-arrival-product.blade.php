<div class="card mt-2 new-arrival-product">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('New Arrival Products') }}</h3>
            <p class="mb-0 fs-12">
                {{ translate('Set how newly arrived products are sorted and displayed.') }}
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="new_arrival_product_list_priority[custom_sorting_status]" value="0"
                                       data-parent-class="new-arrival-product" data-from="default-sorting"
                                       {{ ($newArrivalProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Default Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('Currently sorting this section based on default settings') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="new_arrival_product_list_priority[custom_sorting_status]" value="1"
                                       data-parent-class="new-arrival-product" data-from="custom-sorting"
                                       {{ ($newArrivalProductListPriority?->custom_sorting_status ?? 0) == 1 ? 'checked' : '' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Custom Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('You can sort this section in other ways') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-sorting-radio-list {{ ($newArrivalProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'd--none' }}">
                    <div class="d-flex flex-column gap-20">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Duration') }}</label>
                                <input type="number" min="1" class="form-control" name="new_arrival_product_list_priority[duration]" value="{{ $newArrivalProductListPriority?->duration ?? 1 }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Duration Type') }}</label>
                                <select class="form-control" name="new_arrival_product_list_priority[duration_type]">
                                    @foreach(['day','week','month','year'] as $type)
                                        <option value="{{ $type }}" {{ ($newArrivalProductListPriority?->duration_type ?? 'month') == $type ? 'selected' : '' }}>{{ translate(ucfirst($type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[sort_by]" value="latest_created" id="new-arrival-sort-latest"
                                       {{ ($newArrivalProductListPriority?->sort_by ?? 'latest_created') == 'latest_created' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-sort-latest">{{ translate('Sort By Latest Created') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[sort_by]" value="first_created" id="new-arrival-sort-first"
                                       {{ ($newArrivalProductListPriority?->sort_by ?? '') == 'first_created' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-sort-first">{{ translate('Sort By First Created') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[sort_by]" value="most_order" id="new-arrival-sort-most-order"
                                       {{ ($newArrivalProductListPriority?->sort_by ?? '') == 'most_order' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-sort-most-order">{{ translate('Sort By Most Order') }}</label>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[out_of_stock_product]" value="desc" id="new-arrival-stock-desc"
                                       {{ ($newArrivalProductListPriority?->out_of_stock_product ?? '') == 'desc' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-stock-desc">{{ translate('Show out of stock products at the end of the list') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[out_of_stock_product]" value="hide" id="new-arrival-stock-hide"
                                       {{ ($newArrivalProductListPriority?->out_of_stock_product ?? '') == 'hide' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-stock-hide">{{ translate('Remove out of stock products from the list') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[out_of_stock_product]" value="default" id="new-arrival-stock-default"
                                       {{ in_array($newArrivalProductListPriority?->out_of_stock_product ?? 'default', ['default', null, ''], true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-stock-default">{{ translate('None') }}</label>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[temporary_close_sorting]" value="desc" id="new-arrival-temp-desc"
                                       {{ ($newArrivalProductListPriority?->temporary_close_sorting ?? '') == 'desc' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-temp-desc">{{ translate('Show temporarily closed products/stores at the end') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[temporary_close_sorting]" value="hide" id="new-arrival-temp-hide"
                                       {{ ($newArrivalProductListPriority?->temporary_close_sorting ?? '') == 'hide' ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-temp-hide">{{ translate('Remove temporarily closed products/stores from the list') }}</label>
                            </div>
                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show" name="new_arrival_product_list_priority[temporary_close_sorting]" value="default" id="new-arrival-temp-default"
                                       {{ in_array($newArrivalProductListPriority?->temporary_close_sorting ?? 'default', ['default', null, ''], true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="new-arrival-temp-default">{{ translate('None') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
