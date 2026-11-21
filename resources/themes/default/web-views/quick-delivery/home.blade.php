@extends('layouts.front-end.app')

@section('title', 'DROJIO Quick Delivery')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
        <div>
            <h2 class="mb-1">Quick Delivery</h2>
            <div class="text-muted small">Delivering to: {{ $quickAddress['address'] }}</div>
            <div class="small text-muted">Delivery estimates are calculated by the server and may change at checkout.</div>
        </div>
        <form method="POST" action="{{ route('quick-delivery.change-address') }}">
            @csrf
            <button class="btn btn-outline-primary" type="submit">Change address</button>
        </form>
    </div>

    @if(!$isServiceable)
        <div class="alert alert-info">No Quick Delivery stores can serve this location right now. Try another delivery address.</div>
    @else
        <div class="mb-4">
            <h4>Nearby Quick stores</h4>
            <div class="row g-3">
                @foreach($eligibleStores->take(8) as $shop)
                    <div class="col-sm-6 col-lg-3">
                        <a class="card h-100 p-3 text-dark" href="{{ route('quick-delivery.store', $shop->id) }}">
                            <strong>{{ $shop->name }}</strong>
                            <span class="small text-muted">Estimated {{ $shop->quick_eta_minutes }} min · {{ $shop->quick_distance_km }} km road distance</span>
                        </a>
                    </div>
                @endforeach
            </div>
            <a class="d-inline-block mt-2" href="{{ route('quick-delivery.stores') }}">View all eligible stores</a>
        </div>

        @if($homeCategories->isNotEmpty())
            <h4>Categories near you</h4>
            <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach($homeCategories as $category)
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('quick-delivery.products') }}">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        @foreach(['Featured products' => $featuredProductsList, 'New arrivals' => $newArrivalProducts, 'Top rated' => $topRatedProducts, 'Best selling' => $bestSellProduct] as $heading => $items)
            @if($items->count())
                <section class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">{{ $heading }}</h4>
                        <a href="{{ route('quick-delivery.products') }}">View all</a>
                    </div>
                    <div class="row g-3">
                        @foreach($items->take(8) as $product)
                            <div class="col-6 col-md-4 col-lg-3">
                                @include('web-views.quick-delivery._product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if($quickProducts->isEmpty())
            <div class="alert alert-info">Nearby stores are available, but none have Quick-enabled products in stock.</div>
        @endif
    @endif
</div>
@endsection
