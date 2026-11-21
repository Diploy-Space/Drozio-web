@extends('layouts.front-end.app')
@section('title', 'Quick Delivery Store')
@section('content')
<div class="container py-4">
    <h2>{{ $shop->name }}</h2>
    <p class="text-muted">Estimated {{ $shop->quick_eta_minutes }} min to {{ $quickAddress['address'] }}</p>
    <div class="row g-3">
        @forelse($quickProducts as $product)
            <div class="col-6 col-md-4 col-lg-3">
                @include('web-views.quick-delivery._product-card', ['product' => $product, 'shopId' => $shop->id])
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">No available Quick products from this store.</div></div>
        @endforelse
    </div>
</div>
@endsection
