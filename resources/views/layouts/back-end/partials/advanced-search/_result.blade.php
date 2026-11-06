<div id="searchLoaderOverlay" class="search-loader-overlay d-none">
    <div class="loader-spinner"></div>
</div>
@if (count($result) > 0)
    <div class="fs-14 fw-medium text-center text-body-light">
        {{ $recent ? translate('Recent Search') : translate('Search Result') }}
    </div>
    <div class="search-list d-flex flex-column">
        @foreach ($result as $group)
            <h6 class="fs-14 fw-bold text-body-light mt-1 mb-1">
                {{ $group['label'] }}
            </h6>
            @foreach ($group['items'] as $item)
                <form action="{{ $recentSearchUrl }}" method="POST">
                    @csrf
                    <input type="hidden" name="routeName" value="{{ $item['route_name'] }}">
                    <input type="hidden" name="routeUri" value="{{ $item['route_uri'] }}">
                    <input type="hidden" name="searchKeyword" value="{{ $item['search_keyword'] }}">
                    <input type="hidden" name="business_module" value="{{ $item['business_module'] }}">
                    <input type="hidden" name="type" value="{{ $item['type'] }}">
                    <input type="hidden" name="image" value="{{ $item['image'] }}">

                    <div class="search-item-wrapper" data-item-id="{{ $item['unique_id'] }}">
                        <button type="submit"
                            class="search-list-item d-flex align-items-start gap-3 p-3 text-decoration-none text-reset">
                            @if ($item['image'])
                                <div>
                                    <img src="{{ $item['image'] }}" alt="{{ $item['route_name'] }}"
                                        class="rounded border object-fit-cover aspect-1"
                                        width="40" height="40">
                                </div>
                            @endif
                            <div class="d-flex gap-3 align-items-center justify-content-between w-100">
                                <div class="d-flex flex-column flex-grow-1 text-start">
                                    <h5 class="fs-14 line-1 mb-1">{!! $item['highlighted_title'] !!}</h5>
                                    <p class="text-muted fs-12 mb-0 text-break">{!! $item['highlighted_uri'] !!}</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </form>
            @endforeach
        @endforeach
    </div>
@elseif ($keyword === '')
    <div class="d-flex flex-column gap-3 align-items-center justify-content-center min-h-300 rounded text-body-light">
        <img width="40" height="40" class="svg"
            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/before-search.svg') }}"
            alt="{{ translate('Search') }}">
        <span class="fs-16">{{ translate('Write something to get search results') }}</span>
    </div>
@else
    <div class="d-flex flex-column gap-3 align-items-center justify-content-center min-h-300 rounded text-body-light">
        <img width="40" height="40" class="svg"
            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/empty-state-icon/no-result-found.svg') }}"
            alt="{{ translate('No result found') }}">
        <span class="fs-16">{{ translate('No result found') }}</span>
    </div>
@endif
