@foreach($products as $product)
    @include('vendor-views.pos.partials._single-product', ['product' => $product])
@endforeach
