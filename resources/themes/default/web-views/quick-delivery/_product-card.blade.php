{{-- Quick-specific card. Intentionally never calls the Retail add-to-cart action. --}}
<div class="card h-100 p-3">
    @if(!empty($product->thumbnail_full_url))
        <a href="{{ route('quick-delivery.product', ['slug' => $product->slug] + (!empty($shopId) ? ['store_id' => $shopId] : [])) }}">
            <img loading="lazy" class="img-fluid mb-2" alt="{{ $product->name }}"
                 src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
        </a>
    @endif
    <h6 class="mb-2">
        <a href="{{ route('quick-delivery.product', ['slug' => $product->slug] + (!empty($shopId) ? ['store_id' => $shopId] : [])) }}">
            {{ $product->name }}
        </a>
    </h6>
    <a class="btn btn-outline-primary btn-sm mt-auto"
       href="{{ route('quick-delivery.product', ['slug' => $product->slug] + (!empty($shopId) ? ['store_id' => $shopId] : [])) }}">View for Quick Delivery</a>
</div>
