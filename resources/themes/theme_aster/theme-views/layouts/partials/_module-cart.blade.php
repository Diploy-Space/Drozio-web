<div class="dropdown module-cart">
    <span class="position-relative module-cart__toggle" role="button" tabindex="0" aria-label="{{ translate('Shopping cart') }}">
        <i class="bi bi-bag fs-18"></i>
        <span class="count">{{ $headerModuleCartCount ?? 0 }}</span>
    </span>
    <div class="dropdown-menu dropdown-menu-end module-cart__menu p-3">
        @include('theme-views.layouts.partials._module-cart-panel')
    </div>
</div>

@push('script')
    <script>
        document.addEventListener('hexa-ai:cart-updated', function () {
            if (typeof updateNavCart === 'function') {
                updateNavCart();
            }
        });
    </script>
@endpush
