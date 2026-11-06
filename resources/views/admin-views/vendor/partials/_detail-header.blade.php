@php($vendorTabModule = $vendorTabScope['module'] ?? \App\Enums\BusinessModule::COMMON)

<div class="mb-3 row g-2 align-items-center justify-content-between">
    <div class="col-auto">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            {{ translate('Vendor details') }} -
            <span class="fw-normal">{{ vendorDetailScopeLabel($vendorTabModule) }}</span>
        </h2>
    </div>
    <div class="col-auto">
        @include('admin-views.vendor.partials._detail-scope-select')
    </div>
</div>

@include('admin-views.vendor.partials._detail-tabs')
