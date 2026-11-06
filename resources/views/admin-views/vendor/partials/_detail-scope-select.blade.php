@php($vendorScopeOptions = $seller['status'] === 'pending' ? [] : vendorDetailScopeOptions($seller['id'], $vendorTabScope['module'] ?? \App\Enums\BusinessModule::COMMON))

@if (count($vendorScopeOptions) > 1)
    @include('system-partials._business-module-scope', ['moduleScopeOptions' => $vendorScopeOptions])
@endif
