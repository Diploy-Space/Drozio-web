<div class="card mt-2 vendor-product-list">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Vendor Product List') }}</h3>
            <p class="mb-0 fs-12">{{ translate('Set how vendor product lists are sorted and displayed.') }}</p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="vendor_product_list_priority[custom_sorting_status]" value="0"
                                       data-parent-class="vendor-product-list" data-from="default-sorting"
                                       {{ ($vendorProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Default Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('Currently sorting this section based on default settings') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio"
                                       name="vendor_product_list_priority[custom_sorting_status]" value="1"
                                       data-parent-class="vendor-product-list" data-from="custom-sorting"
                                       {{ ($vendorProductListPriority?->custom_sorting_status ?? 0) == 1 ? 'checked' : '' }}>
                                <div class="flex-grow-1">
                                    <label class="form-label text-dark fw-semibold mb-1">{{ translate('Use Custom Sorting List') }}</label>
                                    <p class="fs-12 mb-3">{{ translate('You can sort this section in other ways') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-sorting-radio-list {{ ($vendorProductListPriority?->custom_sorting_status ?? 0) == 1 ? '' : 'd--none' }}">
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            @foreach(['latest_created'=>translate('Sort By Latest Created'),'first_created'=>translate('Sort By First Created'),'most_order'=>translate('Sort By Most Order'),'reviews_count'=>translate('Sort By Reviews Count'),'rating'=>translate('Sort By Average Ratings'),'a_to_z'=>translate('Sort by Alphabetical').' (A '.translate('To').' Z)','z_to_a'=>translate('Sort by Alphabetical').' (Z '.translate('To').' A)'] as $val=>$label)
                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="vendor_product_list_priority[sort_by]" value="{{ $val }}" id="vendor-prod-sort-{{ $val }}"
                                           {{ ($vendorProductListPriority?->sort_by ?? 'latest_created') == $val ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vendor-prod-sort-{{ $val }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">
                            @foreach(['desc'=>translate('Show out of stock products at the end of the list'),'hide'=>translate('Remove out of stock products from the list'),'default'=>translate('None')] as $val=>$label)
                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="vendor_product_list_priority[out_of_stock_product]" value="{{ $val }}" id="vendor-prod-stock-{{ $val }}"
                                           {{ ($vendorProductListPriority?->out_of_stock_product ?? 'default') == $val ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vendor-prod-stock-{{ $val }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
