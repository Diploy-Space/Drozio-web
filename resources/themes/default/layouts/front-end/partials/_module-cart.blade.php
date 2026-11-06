<div class="dropdown">
    <span class="sh-tool" role="button" tabindex="0" aria-label="{{ translate('Shopping cart') }}">
        <i class="fi fi-sr-shopping-cart"></i>
        <span class="sh-tool__count {{ ($headerModuleCartCount ?? 0) > 0 ? '' : 'is-zero' }}">{{ $headerModuleCartCount ?? 0 }}</span>
    </span>

    <div class="dropdown-menu dropdown-menu-{{ session('direction') === 'rtl' ? 'left' : 'right' }} hm-panel hm-panel--flush hm-cart cart-dropdown">
        @include('layouts.front-end.partials._module-cart-panel')
    </div>
</div>

@push('script')
    <script>
        $(document).on('click', '[data-module-cart-close]', function (event) {
            event.preventDefault();
            $(this).closest('.cart-dropdown').fadeOut(200);
        });

        $(document).on('mouseenter', '#cart_items', function () {
            $(this).find('.cart-dropdown').removeAttr('style');
        });

        document.addEventListener('hexa-ai:cart-updated', function () {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
        });
    </script>
@endpush
