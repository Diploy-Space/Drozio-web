@if(storefrontUsesModuleHeaderPanel() && !empty($headerModuleWishlists ?? []))
    <div class="dropdown module-cart module-wishlist">
        <span class="position-relative module-cart__toggle" role="button" tabindex="0" aria-label="{{ translate('Wishlist') }}">
            <i class="bi bi-heart fs-18"></i>
            <span class="count">{{ $headerModuleWishlistCount }}</span>
        </span>
        <div class="dropdown-menu dropdown-menu-end module-cart__menu p-3">
            @include('theme-views.layouts.partials._module-wishlist-panel')
        </div>
    </div>
@else
    @if(auth('customer')->check())
        <a href="{{ route('wishlists.index') }}" class="position-relative" aria-label="{{ translate('Wishlist') }}">
            <i class="bi bi-heart fs-18"></i>
            <span class="count wishlist_count_status">
                {{ $headerWishlistCount ?? 0 }}
            </span>
        </a>
    @else
        <a href="javascript:" class="position-relative" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-heart fs-18"></i>
        </a>
    @endif
@endif

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
