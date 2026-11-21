@extends('layouts.front-end.app')

@section('title', 'Choose Quick Delivery Address')

@section('content')
<div class="container py-4 py-md-5">
    <h2 class="mb-2">Quick Delivery</h2>
    <p class="text-muted">Choose your delivery location to see nearby stores and products. 30 minutes is a target, not a guarantee.</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($savedAddresses->isNotEmpty())
        <section class="card p-3 mb-4">
            <h5 class="mb-3">Your saved addresses</h5>
            <form method="POST" action="{{ route('quick-delivery.select-address') }}">
                @csrf
                @foreach($savedAddresses as $savedAddress)
                    <label class="d-flex gap-2 align-items-start py-2 border-bottom">
                        <input type="radio" name="address_id" value="{{ $savedAddress->id }}" required>
                        <span>{{ $savedAddress->address }} {{ $savedAddress->zip ? '('.$savedAddress->zip.')' : '' }}</span>
                    </label>
                @endforeach
                <button class="btn btn--primary mt-3" type="submit">Deliver here</button>
            </form>
        </section>
    @endif

    <section class="card p-3">
        <h5>Choose another location</h5>
        <p class="text-muted small">Use your device location (HTTPS required), then fill in your full address. Or enter map-pin coordinates manually for testing.</p>
        <form method="POST" action="{{ route('quick-delivery.select-address') }}" id="quick-location-form">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="quick-address">Complete address</label>
                <input id="quick-address" name="address" class="form-control" maxlength="500" required value="{{ old('address') }}" placeholder="House/building, street, area, city">
            </div>
            <div class="mb-3">
                <label class="form-label" for="quick-pin">PIN code</label>
                <input id="quick-pin" name="pincode" class="form-control" maxlength="20" value="{{ old('pincode') }}">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="quick-latitude">Latitude</label>
                    <input id="quick-latitude" type="number" step="any" min="-90" max="90" name="latitude" class="form-control" value="{{ old('latitude') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="quick-longitude">Longitude</label>
                    <input id="quick-longitude" type="number" step="any" min="-180" max="180" name="longitude" class="form-control" value="{{ old('longitude') }}" required>
                </div>
            </div>
            <button class="btn btn-outline-primary mb-3" type="button" id="quick-use-location">Use my current location</button>
            <div class="small text-muted mb-3" id="quick-location-help" aria-live="polite"></div>
            <button class="btn btn--primary" type="submit">Check availability</button>
        </form>
    </section>
</div>
@endsection

@push('script')
<script>
document.getElementById('quick-use-location')?.addEventListener('click', function () {
    const message = document.getElementById('quick-location-help');
    if (!navigator.geolocation) { message.textContent = 'This browser does not support location sharing.'; return; }
    message.textContent = 'Requesting location permission…';
    navigator.geolocation.getCurrentPosition(function (position) {
        document.getElementById('quick-latitude').value = position.coords.latitude.toFixed(7);
        document.getElementById('quick-longitude').value = position.coords.longitude.toFixed(7);
        message.textContent = 'Location selected. Please confirm your full address before proceeding.';
    }, function () {
        message.textContent = 'Location unavailable or permission denied. Select a map pin or enter coordinates.';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 });
});
</script>
@endpush
