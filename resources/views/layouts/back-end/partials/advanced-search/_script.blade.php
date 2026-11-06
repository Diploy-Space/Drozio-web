@push('script')
    <script>
        (function () {
            'use strict';

            const SEARCH_DEBOUNCE_MS = 300;

            let activeIndex = -1;
            let requestedKeyword = null;
            let currentRequest = null;
            let debounceTimer = null;

            function getSearchModal() {
                return $('#advanceSearchModal');
            }

            function getSearchInput() {
                return $('#advance-search-input-global');
            }

            function getResultItems() {
                return $('#searchResults .search-item-wrapper');
            }

            function highlightItem(index) {
                const items = getResultItems();
                items.removeClass('active-item');

                if (index < 0 || index >= items.length) {
                    return;
                }

                const currentItem = $(items[index]).addClass('active-item');
                const button = currentItem.find('.search-list-item')[0];
                const container = document.querySelector('#searchResults');

                if (!button || !container) {
                    return;
                }

                const itemRect = button.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();

                if (itemRect.top < containerRect.top || itemRect.bottom > containerRect.bottom) {
                    button.scrollIntoView({behavior: 'auto', block: 'nearest'});
                }
            }

            function toggleLoader(isVisible) {
                $('#searchLoaderOverlay').toggleClass('d-none', !isVisible);
            }

            function renderError(xhr) {
                const errors = xhr.responseJSON && Array.isArray(xhr.responseJSON.errors) ? xhr.responseJSON.errors : [];
                const message = errors.length > 0 && errors[0].message ? errors[0].message : $('#advanceSearchErrorTemplate').data('fallback-message');
                const errorState = $($('#advanceSearchErrorTemplate').html().trim());

                errorState.find('.advance-search-error-message').text(message);
                $('#searchResults').empty().append(errorState);
                activeIndex = -1;
            }

            function runQuery(keyword) {
                const searchKeyword = (keyword || '').trim();

                if (requestedKeyword === searchKeyword) {
                    return;
                }

                requestedKeyword = searchKeyword;

                if (currentRequest) {
                    currentRequest.abort();
                }

                const requestData = {business_module: getSearchModal().data('business-module')};

                if (searchKeyword !== '') {
                    requestData.keyword = searchKeyword;
                }

                toggleLoader(true);

                currentRequest = $.ajax({
                    type: 'GET',
                    url: getSearchModal().data('search-url'),
                    data: requestData,
                    dataType: 'json',
                    success: function (response) {
                        if (searchKeyword !== requestedKeyword) {
                            return;
                        }

                        $('#searchResults').empty().html(response.htmlView);
                        activeIndex = searchKeyword !== '' && getResultItems().length > 0 ? 0 : -1;
                        highlightItem(activeIndex);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort' || searchKeyword !== requestedKeyword) {
                            return;
                        }

                        requestedKeyword = null;
                        renderError(xhr);
                    },
                    complete: function (xhr) {
                        if (xhr === currentRequest) {
                            currentRequest = null;
                            toggleLoader(false);
                        }
                    }
                });
            }

            $(document).ready(function () {
                const isMac = (navigator.platform || '').toLowerCase().includes('mac');

                $(document).on('click', '#v2-header-search', function (event) {
                    event.preventDefault();
                    getSearchModal().modal('show');
                });

                $(document).on('keydown', function (event) {
                    const isShortcutModifier = isMac ? event.metaKey : event.ctrlKey;

                    if (isShortcutModifier && (event.key === 'k' || event.key === 'K')) {
                        event.preventDefault();
                        getSearchModal().modal('show');
                    }
                });

                $(document).on('input', '#advance-search-input-global', function () {
                    const searchKeyword = $(this).val().trim();

                    activeIndex = -1;
                    highlightItem(activeIndex);
                    clearTimeout(debounceTimer);

                    if (searchKeyword === '') {
                        runQuery('');
                        return;
                    }

                    debounceTimer = setTimeout(function () {
                        runQuery(searchKeyword);
                    }, SEARCH_DEBOUNCE_MS);
                });

                $(document).on('keydown', function (event) {
                    if (!getSearchModal().hasClass('show')) {
                        return;
                    }

                    const items = getResultItems();

                    if (event.key === 'Enter' && event.target.id === 'advance-search-input-global') {
                        event.preventDefault();
                    }

                    if (!items.length) {
                        return;
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, items.length - 1);
                        highlightItem(activeIndex);
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                        highlightItem(activeIndex);
                    } else if (event.key === 'Enter' && activeIndex >= 0 && activeIndex < items.length) {
                        event.preventDefault();
                        const target = $(items[activeIndex]).find('.search-list-item')[0];

                        if (target) {
                            target.click();
                        }
                    }
                });

                $(document).on('mouseenter', '#searchResults .search-item-wrapper', function () {
                    activeIndex = getResultItems().index(this);
                    highlightItem(activeIndex);
                });

                $(document).on('submit', '#searchResults form', function () {
                    const form = $(this);

                    if (form.data('submitted')) {
                        return false;
                    }

                    form.data('submitted', true);
                    form.find('button[type="submit"]').prop('disabled', true);
                });

                $(window).on('pageshow', function () {
                    $('#searchResults form').removeData('submitted').find('button[type="submit"]').prop('disabled', false);
                });

                getSearchModal().on('shown.bs.modal', function () {
                    const input = getSearchInput();

                    input.trigger('focus');
                    runQuery(input.val());
                    activeIndex = input.val().trim() !== '' && getResultItems().length > 0 ? 0 : -1;
                    highlightItem(activeIndex);
                });
            });
        })();
    </script>
@endpush
