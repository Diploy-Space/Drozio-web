<form action="{{ $filterAction }}" method="GET">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBannerFilter"
         aria-labelledby="offcanvasBannerFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Filter') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                    data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>

        <div class="offcanvas-body">
            @if (request()->filled('searchValue'))
                <input type="hidden" name="searchValue" value="{{ request('searchValue') }}">
            @endif

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label">{{ translate('Sorting') }}</label>
                <div class="bg-white rounded p-3">
                    <div class="row g-3">
                        @foreach ($sortTypes as $sortKey => $sortLabel)
                            <div class="col-sm-6">
                                <div class="d-flex gap-2">
                                    <input class="form-check-input radio--input" type="radio" name="sortBy"
                                           id="bannerSortBy-{{ $loop->index }}" value="{{ $sortKey }}"
                                        {{ $selectedSortBy === $sortKey ? 'checked' : '' }}>
                                    <label class="form-check-label fs-12" for="bannerSortBy-{{ $loop->index }}">
                                        {{ $sortLabel }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label">{{ translate('Banner type') }}</label>
                <div class="bg-white rounded p-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex gap-2">
                                <input class="form-check-input radio--input" type="radio" name="banner_type"
                                       id="bannerType-all" value="" {{ empty($selectedBannerType) ? 'checked' : '' }}>
                                <label class="form-check-label fs-12" for="bannerType-all">{{ translate('All') }}</label>
                            </div>
                        </div>
                        @foreach ($bannerTypes as $typeKey => $typeLabel)
                            <div class="col-sm-6">
                                <div class="d-flex gap-2">
                                    <input class="form-check-input radio--input" type="radio" name="banner_type"
                                           id="bannerType-{{ $loop->index }}" value="{{ $typeKey }}"
                                        {{ $selectedBannerType === $typeKey ? 'checked' : '' }}>
                                    <label class="form-check-label fs-12" for="bannerType-{{ $loop->index }}">
                                        {{ $typeLabel }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20 overflow-wrap-anywhere">
                <label class="form-label">{{ translate('Published') }}</label>
                <div class="bg-white rounded p-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex gap-2">
                                <input class="form-check-input radio--input" type="radio" name="published"
                                       id="bannerPublished-all" value=""
                                    {{ $selectedPublished === null ? 'checked' : '' }}>
                                <label class="form-check-label fs-12" for="bannerPublished-all">{{ translate('All') }}</label>
                            </div>
                        </div>
                        @foreach ($publishedTypes as $publishedKey => $publishedLabel)
                            <div class="col-sm-6">
                                <div class="d-flex gap-2">
                                    <input class="form-check-input radio--input" type="radio" name="published"
                                           id="bannerPublished-{{ $loop->index }}" value="{{ $publishedKey }}"
                                        {{ $selectedPublished === $publishedKey ? 'checked' : '' }}>
                                    <label class="form-check-label fs-12" for="bannerPublished-{{ $loop->index }}">
                                        {{ $publishedLabel }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <a class="btn btn-secondary w-100" href="{{ $filterAction }}">{{ translate('Clear Filter') }}</a>
                <button type="submit" class="btn btn-primary w-100">{{ translate('Apply') }}</button>
            </div>
        </div>
    </div>
</form>
