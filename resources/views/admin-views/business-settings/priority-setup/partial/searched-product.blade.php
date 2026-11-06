<div class="card mt-2 searched-product-list">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3> {{ translate('Product List') }} ({{ translate('Search Bar') }})</h3>
            <p class="mb-0 fs-12">
                {{ translate('Products that appear in search, based on availability.') }}
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" data-parent-class="searched-product-list"
                                       data-from="default-sorting" name="searched_product_list_priority[custom_sorting_status]"
                                       value="0"
                                    {{ $searchedProductListPriority?->custom_sorting_status == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label for="" class="form-label text-dark fw-semibold mb-1">
                                        {{ translate('Use Default Sorting List') }}
                                    </label>
                                    <p class="fs-12 mb-3">
                                        {{ translate('Currently sorting this section by keyword') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" name="searched_product_list_priority[custom_sorting_status]" value="1"
                                       data-parent-class="searched-product-list" data-from="custom-sorting"
                                    {{ isset($searchedProductListPriority?->custom_sorting_status) && $searchedProductListPriority?->custom_sorting_status == 1 ? 'checked' : ''}}>
                                <div class="flex-grow-1">
                                    <label for="" class="form-label text-dark fw-semibold mb-1">
                                        {{ translate('Use Custom Sorting List') }}
                                    </label>
                                    <p class="fs-12 mb-3">
                                        {{ translate('You can sort this section in other ways') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="custom-sorting-radio-list {{ isset($searchedProductListPriority?->custom_sorting_status) && $searchedProductListPriority?->custom_sorting_status == 1 ? '' : 'd--none'}}">
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="searched_product_list_priority[out_of_stock_product]" value="desc" class="check-box"
                                       data-parent-class="searched-product-list" id="show-in-last"
                                    {{ isset($searchedProductListPriority?->out_of_stock_product) && $searchedProductListPriority?->out_of_stock_product == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label" for="show-in-last">
                                    {{ translate('Show stock out products at the end of the list') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="searched_product_list_priority[out_of_stock_product]" value="hide" class="check-box"
                                       data-parent-class="searched-product-list" id="remove-product"
                                    {{ isset($searchedProductListPriority?->out_of_stock_product) && $searchedProductListPriority?->out_of_stock_product == 'hide' ? 'checked' : ''}}>
                                <label class="form-check-label" for="remove-product">
                                    {{ translate('Remove Stock Out Products From The List') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="searched_product_list_priority[out_of_stock_product]" value="default"
                                       data-parent-class="searched-product-list" id="default"
                                    {{ isset($searchedProductListPriority?->out_of_stock_product) ? ($searchedProductListPriority?->out_of_stock_product == 'default' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="default">
                                    {{ translate('None') }}
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="searched_product_list_priority[temporary_close_sorting]" value="desc"
                                       data-parent-class="searched-product-list"
                                       id="searched-product-list-temporary-close-last"
                                    {{ isset($searchedProductListPriority?->temporary_close_sorting) && $searchedProductListPriority?->temporary_close_sorting == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label text-capitalize"
                                       for="searched-product-list-temporary-close-last">
                                    {{ translate('Show product at the end of the list if the store is temporarily off') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="searched_product_list_priority[temporary_close_sorting]" value="hide"
                                       data-parent-class="searched-product-list"
                                       id="searched-product-list-temporary-close-remove"
                                    {{ isset($searchedProductListPriority?->temporary_close_sorting) ? ($searchedProductListPriority?->temporary_close_sorting == 'hide' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label text-capitalize"
                                       for="searched-product-list-temporary-close-remove">
                                    {{ translate('Remove Product From The List If Store Is Temporarily Off') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show text-capitalize"
                                       name="searched_product_list_priority[temporary_close_sorting]" value="default"
                                       data-parent-class="searched-product-list"
                                       id="searched-product-list-temporary-close-default"
                                    {{ isset($searchedProductListPriority?->temporary_close_sorting) ?( $searchedProductListPriority?->temporary_close_sorting == 'default' ? 'checked' : '' ) : 'checked'}}>
                                <label class="form-check-label" for="searched-product-list-temporary-close-default">
                                    {{ translate('None') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
