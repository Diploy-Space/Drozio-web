<div class="alert alert-soft-primary d-flex align-items-center gap-2 mb-0 p-1 pl-3 max-w-500" role="alert">
    @include('vendor-views.shop.partials._verified-badge', ['isVerified' => true, 'size' => 20])
    <span class="fs-12 flex-grow-1 text-dark">
        {{ $verification['is_qualified']
            ? translate('You are eligible for the verified badge')
            : translate('Want to verified?') }}
    </span>
    <button type="button" class="btn btn-primary btn-sm text-nowrap flex-shrink-0 p-1" data-toggle="offcanvas"
            data-target=".offcanvas-verification-criteria">
        {{ translate('View Criteria') }}
    </button>
</div>
