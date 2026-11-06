<div class="row g-4 pt-4">
    <div class="col-lg-6">
        <div class="card card-body bg-section h-100">
            <div class="bg-white rounded p-2 p-sm-3 mb-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="radio" name="meta_index" value="index" checked class="form-check-input radio--input">
                                <span class="user-select-none form-check-label">{{ translate('Index') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Lets search engines index this page and show it in results.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="radio" name="meta_index" value="noindex" class="action-input-no-index-event form-check-input radio--input">
                                <span class="user-select-none form-check-label">{{ translate('No Index') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Keeps this page out of search engine results.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded p-2 p-sm-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="checkbox" name="meta_no_follow" value="1" class="input-no-index-sub-element form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">{{ translate('No Follow') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Instruct search engines not to follow links from this web page') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                               <input type="checkbox" name="meta_no_archive" value="1" class="input-no-index-sub-element form-check-input checkbox--input">
                               <span class="user-select-none form-check-label">{{ translate('No Archive') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Instruct search engines not to display this web page\'s cached or saved version') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="checkbox" name="meta_no_image_index" value="1" class="input-no-index-sub-element form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">{{ translate('No Image Index') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Prevents images from being listed or indexed by search engines') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="checkbox" name="meta_no_snippet" value="1" class="input-no-index-sub-element form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">
                                    {{ translate('No Snippet') }}
                                </span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Stops search engines showing a snippet of this page.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-body bg-section h-100">
            <div class="bg-white rounded p-2 p-sm-3 d-flex flex-column gap-2 h-100">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2">
                                <input type="checkbox" name="meta_max_snippet" value="1" class="form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">
                                    {{ translate('Max Snippet') }}
                                </span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum length of the snippet search engines display.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="item flex-grow-0">
                        <input type="number" placeholder="-1" class="form-control h-30 py-0" name="meta_max_snippet_value" value="-1">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2 m-0">
                                <input type="checkbox" name="meta_max_video_preview" value="1" class="form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">
                                    {{ translate('Max Video Preview') }}
                                </span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum length of the video preview search engines display.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="item flex-grow-0">
                        <input type="number" placeholder="-1" class="form-control h-30 py-0" name="meta_max_video_preview_value" value="-1">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="item">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-check d-flex gap-2 m-0">
                                <input type="checkbox" name="meta_max_image_preview" value="1" class="form-check-input checkbox--input">
                                <span class="user-select-none form-check-label">{{ translate('Max Image Preview') }}</span>
                            </label>
                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum size of the image preview search engines display.') }}">
                                 <i class="fi fi-sr-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="item w-120 flex-grow-0">
                        <div class="select-wrapper">
                            <select class="form-select h-30 py-0" name="meta_max_image_preview_value">
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
</div>
