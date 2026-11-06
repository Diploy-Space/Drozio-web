<div class="header-mobile-search" data-mobile-search-panel>
    <form action="{{ $searchAction ?? $productsRoute }}" method="GET"
          data-search-suggestion-url="{{ $searchSuggestionUrl ?? '' }}">
        <div class="search-bar header-mobile-search__bar">
            @if(isset($categories) && $categories->isNotEmpty())
                @php($selectedSearchCategory ??= $categories->firstWhere('id', (int) collect(request('category_ids'))->first()))
                <div class="dropdown search_dropdown header-mobile-search__category">
                    <button type="button"
                            class="dropdown-toggle text-dark text-truncate header-search-dropdown-button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            data-default="{{ translate('All categories') }}">
                        {{ $selectedSearchCategory['name'] ?? translate('All categories') }}
                    </button>
                    <input type="hidden" name="category_ids[]" value="{{ $selectedSearchCategory['id'] ?? 'all' }}">
                    <ul class="dropdown-menu">
                        <li>
                            <a class="d-flex text-capitalize" data-value="all" href="javascript:">
                                {{ translate('All categories') }}
                            </a>
                        </li>
                        @foreach($categories as $category)
                            <li>
                                <a class="d-flex" data-value="{{ $category['id'] }}" href="javascript:">
                                    {{ $category['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <input type="search" name="{{ $searchFieldName }}" class="form-control search-bar-input-mobile"
                   autocomplete="off" value="{{ request($searchFieldName) }}"
                   placeholder="{{ $searchPlaceholder }}">
            <input name="data_from" value="search" hidden>
            <input type="hidden" name="global_search_input" value="1">
            <input name="page" value="1" hidden>
            <button type="submit" aria-label="{{ translate('Search') }}">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
        </div>
        <div class="search-result-box d--none"></div>
    </form>
</div>
