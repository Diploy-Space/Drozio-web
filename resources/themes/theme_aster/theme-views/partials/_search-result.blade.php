<ul class="list-group list-group-flush">
    @forelse($products as $product)
        <li class="list-group-item bg--light">
            <a href="{{ route('product', $product->slug) }}">
                {{ $product['name'] }}
            </a>
        </li>
    @empty
        <li class="list-group-item bg--light text-center text-muted">{{ translate('No suggestion product found') }}</li>
    @endforelse
</ul>
