@extends('layouts.front-end.app')
@section('title', 'Nearby Quick Delivery Stores')
@section('content')
<div class="container py-4">
    <h2>Stores near you</h2>
    <p class="text-muted">Delivering to {{ $quickAddress['address'] }}</p>
    <div class="row g-3">
        @forelse($eligibleStores as $shop)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('quick-delivery.store', $shop->id) }}" class="card p-3 h-100 text-dark">
                    <h5>{{ $shop->name }}</h5>
                    <span>Estimated {{ $shop->quick_eta_minutes }} min · {{ $shop->quick_distance_km }} km driving</span>
                </a>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">No stores are available for this address.</div></div>
        @endforelse
    </div>
</div>
@endsection
