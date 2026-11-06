<div class="card mt-2 top-rated-product">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Top Rated Products') }}</h3>
            <p class="mb-0 fs-12">
                {{ translate('Most ordered products with the highest ratings and reviews.') }}
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                    <div class="bg-white p-3 rounded">
                        <div class="row g-4">
                            <div class="col-xl-6 col-md-6">
                                <div class="form-check d-flex gap-2 gap-sm-3">
                                    <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio" name="top_rated_product_list_priority[custom_sorting_status]" value="0" data-parent-class="top-rated-product" data-from="default-sorting"
                                        {{ $topRatedProductListPriority?->custom_sorting_status == 1 ? '' : 'checked'}}>
                                    <div class="flex-grow-1">
                                        <label for="" class="form-label text-dark fw-semibold mb-1">
                                            {{ translate('Use Default Sorting List') }}
                                        </label>
                                        <p class="fs-12 mb-3">
                                            {{ translate('Currently sorting this section based on review count') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <div class="form-check d-flex gap-2 gap-sm-3">
                                    <input class="form-check-input radio--input radio--input_lg switcher-input-js" type="radio" name="top_rated_product_list_priority[custom_sorting_status]" value="1" data-parent-class="top-rated-product" data-from="custom-sorting"
                                        {{ isset($topRatedProductListPriority?->custom_sorting_status) && $topRatedProductListPriority?->custom_sorting_status == 1 ? 'checked' : ''}}>
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
                    <div class="custom-sorting-radio-list {{ isset($topRatedProductListPriority?->custom_sorting_status) && $topRatedProductListPriority?->custom_sorting_status == 1 ? '' : 'd--none'}}">
                        <div class="d-flex flex-column gap-20">
                            <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[minimum_rating_point]" value="4" id="top-rated-product-minimum-rating-4"
                                        {{ isset($topRatedProductListPriority?->minimum_rating_point) ? ($topRatedProductListPriority?->minimum_rating_point == '4' ? 'checked' : '') : ''}}>
                                    <label class="form-check-label" for="top-rated-product-minimum-rating-4">
                                        {{ translate('Show 4+ Rated Products') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show"  name="top_rated_product_list_priority[minimum_rating_point]" value="3.5" id="top-rated-product-minimum-rating-3-5"
                                        {{ isset($topRatedProductListPriority?->minimum_rating_point) && $topRatedProductListPriority?->minimum_rating_point == '3.5' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-minimum-rating-3-5">
                                        {{ translate('Show 3.5+ Rated Sellers') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[minimum_rating_point]" id="top-rated-product-minimum-rating-2" value="2"
                                        {{ isset($topRatedProductListPriority?->minimum_rating_point) && $topRatedProductListPriority?->minimum_rating_point == '3' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-minimum-rating-2">
                                        {{ translate('Show 3+ Rated Products') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[minimum_rating_point]" id="top-rated-product-minimum-rating-0" value="default"
                                        {{ isset($topRatedProductListPriority?->minimum_rating_point) ? ($topRatedProductListPriority?->minimum_rating_point == 'default' ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label" for="top-rated-product-minimum-rating-0">
                                        {{ translate('None') }}
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[sort_by]" value="most_order" id="top-rated-product-sort-by-most-order"
                                        {{ isset($topRatedProductListPriority?->sort_by) ? ($topRatedProductListPriority?->sort_by == 'most_order' ? 'checked' : '') : 'checked'}}>
                                    <label class="form-check-label" for="top-rated-product-sort-by-most-order">
                                        {{ translate('Sort By Most Order') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[sort_by]" value="reviews_count" id="top-rated-product-sort-by-reviews-count"
                                        {{ isset($topRatedProductListPriority?->sort_by) && $topRatedProductListPriority?->sort_by == 'reviews_count' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-sort-by-reviews-count">
                                        {{ translate('Sort By Reviews Count') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[sort_by]" value="rating" id="top-rated-product-sort-by-ratings"
                                        {{ isset($topRatedProductListPriority?->sort_by) && $topRatedProductListPriority?->sort_by == 'rating' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-sort-by-ratings">
                                        {{ translate('Sort By Average Ratings') }}
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[out_of_stock_product]" value="desc" data-parent-class="top-rated-product" id="top-rated-product-stock-out-remove"
                                        {{ isset($topRatedProductListPriority?->out_of_stock_product) && $topRatedProductListPriority?->out_of_stock_product == 'desc' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-stock-out-remove">
                                        {{ translate('Show currently closed stores at the end of the list') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[out_of_stock_product]" value="hide" data-parent-class="top-rated-product" id="top-rated-product-stock-out-last"
                                        {{ isset($topRatedProductListPriority?->out_of_stock_product) && $topRatedProductListPriority?->out_of_stock_product == 'hide' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-stock-out-last">
                                        {{ translate('Remove Stock Out Products From The List') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[out_of_stock_product]" value="default" data-parent-class="top-rated-product" id="top-rated-product-stock-out-default"
                                        {{ isset($topRatedProductListPriority?->out_of_stock_product) ? ($topRatedProductListPriority?->out_of_stock_product == 'default' ? 'checked' : '') :'checked'}}>
                                    <label class="form-check-label" for="top-rated-product-stock-out-default">
                                        {{ translate('None') }}
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[temporary_close_sorting]" value="desc" data-parent-class="top-rated-product" id="top-rated-product-temporary-close-last"
                                        {{ isset($topRatedProductListPriority?->temporary_close_sorting) && $topRatedProductListPriority?->temporary_close_sorting == 'desc' ? 'checked' : ''}}>
                                    <label class="form-check-label" for="top-rated-product-temporary-close-last">
                                        {{ translate('Show product at the end of the list if store is temporarily off') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[temporary_close_sorting]" value="hide" data-parent-class="top-rated-product" id="top-rated-product-temporary-close-remove"
                                        {{ isset($topRatedProductListPriority?->temporary_close_sorting) ? ($topRatedProductListPriority?->temporary_close_sorting == 'hide' ? 'checked' : '') :'checked'}}>
                                    <label class="form-check-label" for="top-rated-product-temporary-close-remove">
                                        {{ translate('Remove Product From The List If Store Is Temporarily Off') }}
                                    </label>
                                </div>

                                <div class="form-check d-flex gap-1">
                                    <input type="radio" class="form-check-input radio--input show" name="top_rated_product_list_priority[temporary_close_sorting]" value="default" data-parent-class="top-rated-product" id="top-rated-product-temporary-close-default"
                                        {{ isset($topRatedProductListPriority?->temporary_close_sorting) ?($topRatedProductListPriority?->temporary_close_sorting == 'default' ? 'checked' : '' ) : 'checked'}}>
                                    <label class="form-check-label" for="top-rated-product-temporary-close-default">
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
