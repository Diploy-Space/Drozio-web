<div class="modal fade" id="advanceSearchModal" tabindex="-1" aria-label="{{ translate('Search') }}"
    aria-hidden="true" data-business-module="{{ activeBusinessModule()->key }}" data-search-url="{{ $searchUrl }}">
    <div class="modal-dialog modal-dialog-centered advanced-search-modal-dialog">
        <div class="modal-content">
            <div class="modal-header pb-0">
                <div class="d-flex flex-column gap-2 w-100">
                    <div class="bg-info bg-opacity-10 rounded p-2 fs-12 d-flex flex-wrap align-items-center gap-2">
                        <span>
                            {{ translate('Browse') }}
                            <span class="p-1 bg-white rounded lh-1"><i class="fi fi-rr-arrow-small-up"></i></span>
                            <span class="p-1 bg-white rounded lh-1"><i class="fi fi-rr-arrow-small-down"></i></span>
                        </span>
                        <span>
                            {{ translate('Select') }}
                            <span class="p-1 bg-white rounded lh-1 fs-10"><i
                                    class="fi fi-rr-arrow-turn-down-left"></i></span>
                        </span>
                    </div>
                    <div class="d-flex gap-2 align-items-center position-relative mb-2">
                        <form class="flex-grow-1" id="searchForm" action="javascript:">
                            @csrf
                            <div class="d-flex align-items-center global-search-container">
                                <input autocomplete="off" class="form-control flex-grow-1 rounded-10 search-input"
                                    id="advance-search-input-global" maxlength="255" name="search" type="search"
                                    placeholder="{{ translate('Search by keyword') }}" aria-label="{{ translate('Search') }}">
                            </div>
                        </form>
                        <div class="position-absolute esc-button">
                            <button class="border-0 fs-12 fw-normal px-2 py-1 rounded text-dark" type="button"
                                data-dismiss="modal" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}">
                                {{ translate('Esc') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    <div class="min-h-300">
                        <div class="search-result position-relative" id="searchResults">
                            <div id="searchLoaderOverlay" class="search-loader-overlay d-none">
                                <div class="loader-spinner"></div>
                            </div>
                            <div
                                class="d-flex flex-column gap-3 align-items-center justify-content-center min-h-300 rounded text-body-light">
                                <img width="40" height="40" class="svg"
                                    src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/before-search.svg') }}"
                                    alt="{{ translate('Search') }}">
                                <span class="fs-16">{{ translate('Write something to get search results') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <template id="advanceSearchErrorTemplate" data-fallback-message="{{ translate('Something went wrong. Please try again.') }}">
        <div id="searchLoaderOverlay" class="search-loader-overlay d-none">
            <div class="loader-spinner"></div>
        </div>
        <div class="d-flex flex-column gap-3 align-items-center justify-content-center min-h-300 rounded text-body-light">
            <img width="40" height="40" class="svg"
                src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/no-result-found.svg') }}"
                alt="{{ translate('Search') }}">
            <span class="fs-16 text-center advance-search-error-message"></span>
        </div>
    </template>
</div>
