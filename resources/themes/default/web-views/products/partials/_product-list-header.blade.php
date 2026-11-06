<div class="search-page-header flex-wrap gap-3 mt-2 mt-lg-0">
    @if(isset($shopViewPageHeader) && $shopViewPageHeader)
        <div>
            <nav>
                <div class="nav nav-tabs mb-0" id="nav-tab" role="tablist">
                    <a class="nav-link {{ request('offer_type') != 'clearance_sale' ? 'active' : '' }}"
                       href="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug']]) }}">
                        <h3 class="widget-title align-self-center font-bold fs-16 text-capitalize my-0">{{translate('All product')}}</h3>
                    </a>
                    @if($stockClearanceSetup && $stockClearanceProducts > 0)
                        <a class="nav-link {{ request('offer_type') == 'clearance_sale' ? 'active' : '' }}"
                           href="{{ route('vendor-shop',['slug' => $shopInfoArray['slug'], 'offer_type' => 'clearance_sale']) }}">
                            <h3 class="widget-title align-self-center font-bold fs-16 text-capitalize my-0">{{translate('Clearance Sale')}}</h3>
                        </a>
                    @endif
                </div>
            </nav>
        </div>
    @else
        <div class="d-flex gap-2 gap-sm-1 flex-column justify-content-sm-between flex-grow-1">
            @if(isset($pageTitleContent) && $pageTitleContent)
                <h5 class="font-semibold mb-0 text-capitalize fs-16-mobile">
                    {{ $pageTitleContent }}
                </h5>
            @endif

            <div>
                <span class="view-page-item-count clearance-sale-count fs-14-mobile">{{ $pageProductsCount }}</span>
                {{ translate('Items found') }}
            </div>
        </div>
    @endif

    <div class="d-flex flex-nowrap gap-3 align-items-center flex-grow-1 justify-content-end">
        <div class="d-flex flex-wrap gap-3 flex-grow-1 justify-content-end">
            @if(isset($searchBarSection) && $searchBarSection)
                @if(!request()->has('global_search_input'))
                    <div class="catalog-search flex-grow-1">
                        <i class="czi-search catalog-search__icon"></i>
                        <input class="form-control catalog-search__input search-page-button-input" type="search" autocomplete="off"
                               placeholder="{{ translate('Search for items...') }}" name="product_name" value="{{ request('product_name') }}"
                               aria-label="{{ translate('Search for items...') }}">
                        <button type="submit" class="catalog-search__submit search-page-button">{{ translate('Search') }}</button>
                    </div>
                @endif
            @endif

            @if(isset($sortBySection) && $sortBySection)
            <div id="search-form" class="d-none d-lg-block">
                <div class="sorting-item">
                    @include('web-views.partials._svg-icon-container', ['iconType' => 'sorting'])
                    <label class="for-sorting" for="sorting">{{ translate('Sort By') }}</label>

                    <select class="product-list-filter-input" name="sort_by">
                        <option value="latest" {{ request('sort_by') == 'latest' ? 'selected':'' }}>
                            {{ translate('Default') }}
                        </option>
                        <option value="low-high" {{ request('sort_by') == 'low-high' ? 'selected':'' }}>
                            {{ translate('Price') }} ({{ translate('Low to High') }})
                        </option>
                        <option value="high-low" {{ request('sort_by') == 'high-low' ? 'selected':'' }}>
                            {{ translate('Price') }} ({{ translate('High to Low') }})
                        </option>
                        <option value="rating-low-high" {{ request('sort_by') == 'rating-low-high' ? 'selected':'' }}>
                            {{ translate('Rating') }} ({{ translate('Low to High') }})
                        </option>
                        <option value="rating-high-low" {{ request('sort_by') == 'rating-high-low' ? 'selected':'' }}>
                            {{ translate('Rating') }} ({{ translate('High to Low') }})
                        </option>
                        <option value="a-z" {{ request('sort_by') == 'a-z' ? 'selected':'' }}>
                            {{ translate('Alphabetical') }} ({{ 'A '.translate('To').' Z' }})
                        </option>
                        <option value="z-a" {{ request('sort_by') == 'z-a' ? 'selected':'' }}>
                            {{ translate('Alphabetical') }} ({{ 'Z '.translate('To').' A' }})
                        </option>
                    </select>
                </div>
            </div>
            @endif

            @if(isset($showProductsFilter) && $showProductsFilter)
            <div class="d-none d-lg-block">
                <div class="sorting-item">
                    @include('web-views.partials._svg-icon-container', ['iconType' => 'sorting'])
                    <label class="for-sorting" for="sorting">
                        <span>{{ translate('Filter By') }}</span>
                    </label>
                    <select class="product-list-filter-input" name="data_from">
                        <option value="default" {{ $data['data_from'] == '' ? 'selected':'' }}>
                            {{ translate('Default') }}
                        </option>
                        <option value="best-selling" {{ $data['data_from']=='best-selling'?'selected':'' }}>
                            {{ translate('Best Selling') }}
                        </option>
                        <option value="top-rated" {{ $data['data_from']=='top-rated'?'selected':'' }}>
                            {{ translate('Top Rated') }}
                        </option>
                        <option value="most-favorite" {{ $data['data_from']=='most-favorite'?'selected':''}}>
                            {{ translate('Most Favorite') }}
                        </option>
                    </select>
                </div>
            </div>
            @endif

        </div>
        <div class="d-lg-none">
            <div class="filter-show-btn btn btn--primary h-40px aspect-1 p-2 m-0">
                <i class="fi fi-sr-bars-filter"></i>
            </div>
        </div>
    </div>
</div>
