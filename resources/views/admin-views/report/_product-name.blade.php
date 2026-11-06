<a href="{{ $url }}" class="media align-items-center gap-2">
    <img src="{{ getStorageImages(path: $product['thumbnail_full_url'], type: 'backend-product') }}"
         class="avatar border object-fit-cover flex-shrink-0" alt="">
    <div>
        <div class="media-body text-dark line-1 text-hover-primary max-w-300 min-w-130 lh-1"
             data-bs-toggle="tooltip" title="{{ $product['name'] }}">
            {{ \Illuminate\Support\Str::limit($product['name'], 20) }}
        </div>
        <div class="text-body lh-1 mt-2">ID # {{ $product['id'] }}</div>
    </div>
</a>
