<div class="row gx-2 gy-3">
    <div class="col-lg-6">
        <div class="card card-body bg-section h-100">
            <div class="robots-meta-checkbox-card d-flex flex-wrap gap-2 justify-content-between h-100 bg-white">
                <div class="item">
                    <label class="checkbox--item">
                        <input type="radio" name="meta_index" value="index" checked>
                        <img class="unchecked"
                             src="{{ dynamicAsset('public/assets/back-end/img/uncheck-radio-icon.svg') }}"
                             alt="">
                        <img class="checked"
                             src="{{ dynamicAsset('public/assets/back-end/img/check-radio-icon.svg') }}"
                             alt="">
                        <span class="user-select-none">{{ translate('Index') }}</span>
                        <span data-toggle="tooltip" title="{{ translate('Lets search engines index this page and show it in results.') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                    <label class="checkbox--item">
                        <input type="checkbox" name="meta_no_follow" value="1" class="input-no-index-sub-element">
                        <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                        <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                        <span class="user-select-none">{{ translate('No Follow') }}</span>
                        <span data-toggle="tooltip" title="{{ translate('Instruct search engines not to follow links from this web page') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                    <label class="checkbox--item">
                        <input type="checkbox" name="meta_no_image_index" value="1" class="input-no-index-sub-element">
                        <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                        <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                        <span class="user-select-none">{{ translate('No Image Index') }}</span>
                        <span data-toggle="tooltip" title="{{ translate('Prevents images from being listed or indexed by search engines') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                </div>
                <div class="item">
                    <label class="checkbox--item">
                        <input type="radio" name="meta_index" value="noindex" class="action-input-no-index-event">
                        <img class="unchecked"
                             src="{{ dynamicAsset('public/assets/back-end/img/uncheck-radio-icon.svg') }}"
                             alt="">
                        <img class="checked"
                             src="{{ dynamicAsset('public/assets/back-end/img/check-radio-icon.svg') }}"
                             alt="">
                        <span class="user-select-none">{{ translate('No Index') }}</span>
                        <span data-toggle="tooltip" title="{{ translate('Keeps this page out of search engine results.') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                    <label class="checkbox--item">
                        <input type="checkbox" name="meta_no_archive" value="1" class="input-no-index-sub-element">
                        <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                        <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                        <span class="user-select-none">{{ translate('No Archive') }}</span>
                        <span data-toggle="tooltip" title="{{ translate('Instruct search engines not to display this web page\'s cached or saved version') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                    <label class="checkbox--item">
                        <input type="checkbox" name="meta_no_snippet" value="1" class="input-no-index-sub-element">
                        <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                        <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                        <span class="user-select-none">
                            {{ translate('No Snippet') }}
                        </span>
                        <span data-toggle="tooltip" title="{{ translate('Stops search engines showing a snippet of this page.') }}">
                            <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-body bg-section h-100">
            <div class="robots-meta-checkbox-card d-flex flex-column gap-2 h-100 bg-white">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <label class="checkbox--item m-0">
                            <input type="checkbox" name="meta_max_snippet" value="1">
                            <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                            <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                            <span class="user-select-none">
                                {{ translate('Max Snippet') }}
                            </span>
                            <span data-toggle="tooltip" title="{{ translate('Maximum length of the snippet search engines display.') }}">
                                <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                            </span>
                        </label>
                    </div>
                    <div class="item max-w-200 w-100 flex-grow-0">
                        <input type="number" placeholder="-1" class="form-control h-30 py-0" name="meta_max_snippet_value" value="-1">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <label class="checkbox--item m-0">
                            <input type="checkbox" name="meta_max_video_preview" value="1">
                            <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                            <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                            <span class="user-select-none">
                                {{ translate('Max Video Preview') }}
                            </span>
                            <span data-toggle="tooltip" title="{{ translate('Maximum length of the video preview search engines display.') }}">
                                <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                            </span>
                        </label>
                    </div>
                    <div class="item max-w-200 w-100 flex-grow-0">
                        <input type="number" placeholder="-1" class="form-control h-30 py-0" name="meta_max_video_preview_value" value="-1">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <label class="checkbox--item m-0">
                            <input type="checkbox" name="meta_max_image_preview" value="1">
                            <img class="unchecked" src="{{ dynamicAsset('public/assets/back-end/img/uncheck-icon.svg') }}" alt="">
                            <img class="checked" src="{{ dynamicAsset('public/assets/back-end/img/check-icon.svg') }}" alt="">
                            <span class="user-select-none">{{ translate('Max Image Preview') }}</span>
                            <span data-toggle="tooltip" title="{{ translate('Maximum size of the image preview search engines display.') }}">
                                <i class="fi fi-sr-info fs-12 text-9EADC1"></i>
                            </span>
                        </label>
                    </div>
                    <div class="item max-w-200 w-100 flex-grow-0">
                        <select class="form-control h-30 py-0" name="meta_max_image_preview_value">
                            <option value="large">{{ translate('Large') }}</option>
                            <option value="medium">{{ translate('Medium') }}</option>
                            <option value="small">{{ translate('Small') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
