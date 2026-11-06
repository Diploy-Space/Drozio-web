<div class="dropdown d-none d-md-block">
    <span class="sh-tool" role="button" tabindex="0" aria-label="{{ translate('Wishlist') }}">
        <i class="fi fi-sr-heart"></i>
        <span class="sh-tool__count {{ $headerModuleWishlistCount > 0 ? '' : 'is-zero' }}">{{ $headerModuleWishlistCount }}</span>
    </span>

    <div class="dropdown-menu dropdown-menu-{{ session('direction') === 'rtl' ? 'left' : 'right' }} hm-panel hm-panel--flush hm-cart wishlist-dropdown">
        @include('layouts.front-end.partials._module-wishlist-panel')
    </div>
</div>

@push('script')
    <script>
        $(document).on('click', '[data-module-wishlist-close]', function (event) {
            event.preventDefault();
            $(this).closest('.wishlist-dropdown').fadeOut(200);
        });

        $(document).on('mouseenter', '#wishlist_items', function () {
            $(this).find('.wishlist-dropdown').removeAttr('style');
        });

        $(document).ajaxSuccess(function (event, xhr, settings) {
            var url = $('#wishlist_items').attr('data-header-wishlist-url');

            if (!url || !settings.url || settings.url.indexOf('/wishlists') === -1 || settings.url.indexOf(url) === 0) {
                return;
            }

            $.get(url).done(function (response) {
                $('#wishlist_items').html(response.html);
            });
        });
    </script>
@endpush
