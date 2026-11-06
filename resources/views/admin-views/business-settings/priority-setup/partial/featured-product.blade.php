<div class="card mt-2 featured-product">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Featured Products') }}</h3>
            <p class="mb-0 fs-12">
                {{ translate('Featured products are the products that are ordered most').' , '.translate('Customers\' choice with good reviews and ratings') }}
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" name="featured_product_priority[custom_sorting_status]" value="0"
                                       data-parent-class="featured-product" data-from="default-sorting"
                                    {{ $featureProductPriority?->custom_sorting_status == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label for="" class="form-label text-dark fw-semibold mb-1">
                                        {{ translate('Use Default Sorting List') }}
                                    </label>
                                    <p class="fs-12 mb-3">
                                        {{ translate('Currently sorting this section based on latest added') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" name="featured_product_priority[custom_sorting_status]" value="1"
                                       data-parent-class="featured-product" data-from="custom-sorting"
                                    {{ isset($featureProductPriority?->custom_sorting_status) && $featureProductPriority?->custom_sorting_status == 1 ? 'checked' : ''}}>
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
                    class="custom-sorting-radio-list {{ isset($featureProductPriority?->custom_sorting_status) && $featureProductPriority?->custom_sorting_status == 1 ? '' : 'd--none'}}">
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="latest_created"
                                       id="featured-product-sort-by-latest-created"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'latest_created' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-sort-by-latest-created">
                                    {{ translate('Sort By Latest Created') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="first_created"
                                       id="featured-product-sort-by-first-created"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'first_created' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-sort-by-first-created">
                                    {{ translate('Sort By First Created') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="most_order"
                                       id="featured-product-sort-by-most-order"
                                    {{ isset($featureProductPriority?->sort_by) ? ($featureProductPriority?->sort_by == 'most_order' ? 'checked' : '') : 'checked'}}>
                                <label class="form-check-label" for="featured-product-sort-by-most-order">
                                    {{ translate('Sort By Most Order') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="reviews_count"
                                       id="featured-product-sort-by-reviews-count"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'reviews_count' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-sort-by-reviews-count">
                                    {{ translate('Sort By Reviews Count') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="rating"
                                       id="featured-product-sort-by-ratings"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'rating' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-sort-by-ratings">
                                    {{ translate('Sort By Average Ratings') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="a_to_z"
                                       id="featured-product-alphabetic-order"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'a_to_z' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-alphabetic-order">
                                    {{ translate('Sort by Alphabetical') }}
                                    ({{ 'A ' . translate('To') . ' Z' }})
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[sort_by]" value="z_to_a"
                                       id="featured-product-alphabetic-order-reverse"
                                    {{ isset($featureProductPriority?->sort_by) && $featureProductPriority?->sort_by == 'z_to_a' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-alphabetic-order-reverse">
                                    {{ translate('Sort by Alphabetical') }}
                                    ({{ 'Z ' . translate('To') . ' A' }})
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[out_of_stock_product]" value="desc"
                                       data-parent-class="featured-product" id="featured-product-stock-out-remove"
                                    {{ isset($featureProductPriority?->out_of_stock_product) && $featureProductPriority?->out_of_stock_product == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-stock-out-remove">
                                    {{ translate('Show stock out products at the end of the list') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[out_of_stock_product]" value="hide"
                                       data-parent-class="featured-product" id="featured-product-stock-out-last"
                                    {{ isset($featureProductPriority?->out_of_stock_product) && $featureProductPriority?->out_of_stock_product == 'hide' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-stock-out-last">
                                    {{ translate('Remove Stock Out Products From The List') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[out_of_stock_product]" value="default"
                                       data-parent-class="featured-product" id="featured-product-stock-out-default"
                                    {{ isset($featureProductPriority?->out_of_stock_product) ? ($featureProductPriority?->out_of_stock_product == 'default' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="featured-product-stock-out-default">
                                    {{ translate('None') }}
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[temporary_close_sorting]" value="desc"
                                       data-parent-class="featured-product" id="featured-product-temporary-close-last"
                                    {{ isset($featureProductPriority?->temporary_close_sorting) && $featureProductPriority?->temporary_close_sorting == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label" for="featured-product-temporary-close-last">
                                    {{ translate('Show product at the end of the list if store is temporarily off') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[temporary_close_sorting]" value="hide"
                                       data-parent-class="featured-product" id="featured-product-temporary-close-remove"
                                    {{ isset($featureProductPriority?->temporary_close_sorting) ? ($featureProductPriority?->temporary_close_sorting == 'hide' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="featured-product-temporary-close-remove">
                                    {{ translate('Remove Product From The List If Store Is Temporarily Off') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="featured_product_priority[temporary_close_sorting]" value="default"
                                       data-parent-class="featured-product"
                                       id="featured-product-temporary-close-default"
                                    {{ isset($featureProductPriority?->temporary_close_sorting) ?($featureProductPriority?->temporary_close_sorting == 'default' ? 'checked' : '' ) : 'checked'}}>
                                <label class="form-check-label" for="featured-product-temporary-close-default">
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
