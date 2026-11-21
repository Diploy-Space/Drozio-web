@extends('layouts.front-end.app')
@section('title', 'Quick Delivery Products')
@section('content')
<div class="container py-4">
    <h2>Quick Delivery Products</h2>
    <p class="text-muted">Available near {{ $quickAddress['address'] }}</p>
    <div class="row g-3">
        @forelse($quickProducts as $product)
            <div class="col-6 col-md-4 col-lg-3">
                @include('web-views.quick-delivery._product-card', ['product' => $product, 'shopId' => request('store_id')])
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">No Quick products are available here right now.</div></div>
        @endforelse
    </div>
    @if($quickProducts instanceof \Illuminate\Contracts\Pagination\Paginator)
        {{ $quickProducts->links() }}
    @endif
</div>
@endsection
