<div class="card mt-2 best-selling-product">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Best Selling Products') }}</h3>
            <p class="mb-0 fs-12">{{ translate('Set how best selling products are sorted and displayed.') }}</p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="best_selling_product_list_priority[custom_sorting_status]" value="0"
                                       data-parent-class="best-selling-product" data-from="default-sorting"
                                       {{ ($bestSellingProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Default Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('Currently sorting this section based on default settings') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="best_selling_product_list_priority[custom_sorting_status]" value="1"
                                       data-parent-class="best-selling-product" data-from="custom-sorting"
                                       {{ ($bestSellingProductListPriority?->custom_sorting_status ?? 0) == 1 ? 'checked' : '' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Custom Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('You can sort this section in other ways') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-sorting-radio-list {{ ($bestSellingProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'd--none' }}">
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            @foreach(['most_order'=>translate('Sort By Most Order'),'reviews_count'=>translate('Sort By Reviews Count'),'rating'=>translate('Sort By Average Ratings'),'latest_created'=>translate('Sort By Latest Created')] as $val=>$label)
                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="best_selling_product_list_priority[sort_by]" value="{{ $val }}" id="best-sell-sort-{{ $val }}"
                                           {{ ($bestSellingProductListPriority?->sort_by ?? 'most_order') == $val ? 'checked' : '' }}>
                                    <label class="form-check-label" for="best-sell-sort-{{ $val }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            @foreach(['desc'=>translate('Show out of stock products at the end of the list'),'hide'=>translate('Remove out of stock products from the list'),'default'=>translate('None')] as $val=>$label)
                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="best_selling_product_list_priority[out_of_stock_product]" value="{{ $val }}" id="best-sell-stock-{{ $val }}"
                                           {{ ($bestSellingProductListPriority?->out_of_stock_product ?? 'default') == $val ? 'checked' : '' }}>
                                    <label class="form-check-label" for="best-sell-stock-{{ $val }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            @foreach(['desc'=>translate('Show temporarily closed products/stores at the end'),'hide'=>translate('Remove temporarily closed products/stores from the list'),'default'=>translate('None')] as $val=>$label)
                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="best_selling_product_list_priority[temporary_close_sorting]" value="{{ $val }}" id="best-sell-temp-{{ $val }}"
                                           {{ ($bestSellingProductListPriority?->temporary_close_sorting ?? 'default') == $val ? 'checked' : '' }}>
                                    <label class="form-check-label" for="best-sell-temp-{{ $val }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
