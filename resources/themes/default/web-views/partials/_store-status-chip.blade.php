@php($storeAvailabilityState = getVendorAvailabilityState(type: $type ?? 'vendor', vendor: $vendor ?? null, businessModule: $businessModule ?? null))

@if($storeAvailabilityState)
    @php($storeAvailabilityLabels = ['temporary_close' => translate('Temporary OFF'), 'vacation' => translate('Closed Now')])

    <span @class(['store-status-chip', $chipClass ?? null])>{{ $storeAvailabilityLabels[$storeAvailabilityState] }}</span>
@endif
