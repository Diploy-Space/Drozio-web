@extends('layouts.front-end.app')
@section('title', $product->name)
@section('content')
<div class="container py-4">
    <h2>{{ $product->name }}</h2>
    <p class="text-muted">Quick delivery to {{ $quickAddress['address'] }}</p>
    <p>Available stock for this store: {{ $product->quick_available_qty }}</p>
    <p>Store ID: {{ $product->quick_store_id }}</p>
    <div class="alert alert-info">Quick checkout is not connected yet. This page does not use the ordinary Retail cart.</div>
    <a href="{{ route('quick-delivery.store', ['storeId' => $product->quick_store_id]) }}" class="btn btn-outline-primary">Back to store products</a>
</div>
@endsection
