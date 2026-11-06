@foreach($products as $product)
    @include('admin-views.pos.partials._single-product', ['product' => $product])
@endforeach
