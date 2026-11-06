<div class="admin-pos-customer-info">
    @include('admin-views.pos.partials._admin-pos-customer-info')
</div>

<div id="cart-summary">
    @include('admin-views.pos.partials._cart')
</div>
@push('script_2')
    <script>
        'use strict';
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip()
        })
    </script>
@endpush
