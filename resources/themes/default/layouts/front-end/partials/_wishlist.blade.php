@php($wishlistCount = $headerWishlistCount ?? 0)

<div class="d-none d-md-block">
    <a class="sh-tool" href="{{ route('wishlists.index') }}" aria-label="{{ translate('Wishlist') }}">
        <i class="fi fi-sr-heart"></i>
        <span class="sh-tool__count countWishlist {{ $wishlistCount > 0 ? '' : 'is-zero' }}">{{ $wishlistCount }}</span>
    </a>
</div>

@push('script')
    <script>
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
