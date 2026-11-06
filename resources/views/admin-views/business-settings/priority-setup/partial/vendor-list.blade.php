<div class="card mt-2 vendor-list">
    <div class="card-body">
        <div class="mb-3 mb-sm-20">
            <h3>{{ translate('Vendor List') }}</h3>
            <p class="mb-0 fs-12">
                {{ translate('Vendors sorted by join date, rating or alphabetically.') }}
                .
            </p>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex flex-column gap-20">
                <div class="bg-white p-3 rounded">
                    <div class="row g-4">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" name="vendor_list_priority[custom_sorting_status]" value="0"
                                       data-parent-class="vendor-list" data-from="default-sorting"
                                    {{ $vendorListPriority?->custom_sorting_status == 1 ? '' : 'checked' }}>
                                <div class="flex-grow-1">
                                    <label for="" class="form-label text-dark fw-semibold mb-1">
                                        {{ translate('Use Default Sorting List') }}
                                    </label>
                                    <p class="fs-12 mb-3">
                                        {{ translate('Currently sorting this section based on first created') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-check d-flex gap-2 gap-sm-3">
                                <input class="form-check-input radio--input radio--input_lg switcher-input-js"
                                       type="radio" name="vendor_list_priority[custom_sorting_status]" value="1"
                                       data-parent-class="vendor-list" data-from="custom-sorting"
                                    {{ isset($vendorListPriority?->custom_sorting_status) && $vendorListPriority?->custom_sorting_status == 1 ? 'checked' : ''}}>
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
                    class="custom-sorting-radio-list {{ isset($vendorListPriority?->custom_sorting_status) && $vendorListPriority?->custom_sorting_status == 1 ? '' : 'd--none'}}">
                    <div class="d-flex flex-column gap-20">
                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       value="latest_created" id="vendor-list-sort-by-latest-created"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'latest_created' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-sort-by-latest-created">
                                    {{ translate('Sort By Latest Created') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       value="first_created" id="vendor-list-sort-by-first-created"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'first_created' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-sort-by-first-created">
                                    {{ translate('Sort By First Created') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       value="most_order" id="vendor-list-sort-by-most-order"
                                    {{ isset($vendorListPriority?->sort_by) ? ($vendorListPriority?->sort_by == 'most_order' ? 'checked' : '') : 'checked'}}>
                                <label class="form-check-label" for="vendor-list-sort-by-most-order">
                                    {{ translate('Sort By Most Order') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       id="vendor-list-sort-by-reviews-count" value="reviews_count"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'reviews_count' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-sort-by-reviews-count">
                                    {{ translate('Sort By Reviews Count') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       id="vendor-list-sort-by-ratings" value="rating"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'rating' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-sort-by-ratings">
                                    {{ translate('Sort By Average Ratings') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       value="a_to_z" id="vendor-list-alphabetic-order"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'a_to_z' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-alphabetic-order">
                                    {{ translate('Sort by Alphabetical') }}
                                    ({{ 'A ' . translate('To') . ' Z' }})
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[sort_by]"
                                       value="z_to_a" id="vendor-list-alphabetic-order-reverse"
                                    {{ isset($vendorListPriority?->sort_by) && $vendorListPriority?->sort_by == 'z_to_a' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-alphabetic-order-reverse">
                                    {{ translate('Sort by Alphabetical') }}
                                    ({{ 'Z ' . translate('To') . ' A' }})
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[vacation_mode_sorting]" value="desc"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-vacation-mode-last"
                                    {{ isset($vendorListPriority?->vacation_mode_sorting) && $vendorListPriority?->vacation_mode_sorting == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-vacation-mode-last">
                                    {{ translate('Show currently closed stores at the end of the list') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[vacation_mode_sorting]" value="hide"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-vacation-mode-remove"
                                    {{ isset($vendorListPriority?->vacation_mode_sorting) ? ($vendorListPriority?->vacation_mode_sorting == 'hide' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="vendor-list-vacation-mode-remove">
                                    {{ translate('Remove Currently Closed Stores From The List') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[vacation_mode_sorting]" value="hide"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-vacation-mode-remove"
                                    {{ isset($vendorListPriority?->vacation_mode_sorting) ? ($vendorListPriority?->vacation_mode_sorting == 'default' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="vendor-list-vacation-mode-remove">
                                    {{ translate('None') }}
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 gap-sm-4 border rounded-10 bg-white p-12 p-sm-20">

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[temporary_close_sorting]" value="desc"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-temporary-close-last"
                                    {{ isset($vendorListPriority?->temporary_close_sorting) && $vendorListPriority?->temporary_close_sorting == 'desc' ? 'checked' : ''}}>
                                <label class="form-check-label" for="vendor-list-temporary-close-last">
                                    {{ translate('Show temporarily off stores at the end of the list') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[temporary_close_sorting]" value="hide"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-temporary-close-remove"
                                    {{ isset($vendorListPriority?->temporary_close_sorting) ? ($vendorListPriority?->temporary_close_sorting == 'hide' ? 'checked' : '') :'checked'}}>
                                <label class="form-check-label" for="vendor-list-temporary-close-remove">
                                    {{ translate('Remove Temporarily Off Stores From The List') }}
                                </label>
                            </div>

                            <div class="form-check d-flex gap-1">
                                <input type="radio" class="form-check-input radio--input show"
                                       name="vendor_list_priority[temporary_close_sorting]" value="default"
                                       data-parent-class="vendor-list"
                                       id="vendor-list-temporary-close-default"
                                    {{ isset($vendorListPriority?->temporary_close_sorting) ?( $vendorListPriority?->temporary_close_sorting == 'default' ? 'checked' : '' ) : 'checked'}}>
                                <label class="form-check-label" for="vendor-list-temporary-close-default">
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
