<div class="search-box position-relative">
    <form action="{{ $searchAction ?? $productsRoute }}"
          data-search-suggestion-url="{{ $searchSuggestionUrl ?? '' }}">
        <div class="d-flex header-search-group">
            <div class="select-wrap d-flex align-items-center">
                <div class="dropdown search_dropdown">
                    <button type="button"
                            class="border-0 bg-transparent dropdown-toggle text-dark py-0 header-search-dropdown-button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            data-default="{{ translate('All categories') }}">
                        @php($selectedSearchCategory ??= $categories->firstWhere('id', (int) collect(request('category_ids'))->first()))
                        {{ $selectedSearchCategory['name'] ?? translate('All categories') }}
                    </button>
                    <input type="hidden" name="category_ids[]" id="search_category_value"
                           value="{{ $selectedSearchCategory['id'] ?? 'all' }}">
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

                <input type="search" class="form-control border-0 focus-input search-bar-input"
                       name="{{ $searchFieldName }}" id="global-search" autocomplete="off"
                       value="{{ request($searchFieldName) }}" placeholder="{{ $searchPlaceholder }}">
            </div>
            <input name="data_from" value="search" hidden>
            <input type="hidden" name="global_search_input" value="1">
            <input name="page" value="1" hidden>
            <button type="submit" class="btn btn-primary header-search-submit"
                    aria-label="{{ translate('Search') }}">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>
    <div class="search-result-box d--none"></div>
</div>
