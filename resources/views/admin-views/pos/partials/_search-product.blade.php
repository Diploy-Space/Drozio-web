@if (count($products) > 0)
    @foreach ($products as $product)
        <div class="pos-result action-select-search-product" data-id="{{ $product['id'] }}">
            <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'backend-product') }}"
                 alt="{{ $product['name'] }}">
            <div>
                <div class="pos-result__name" title="{{ $product['name'] }}">{{ $product['name'] }}</div>
                <div class="pos-result__meta">
                    <span><b>{{ translate('SKU') }}:</b> {{ $product->code ?: translate('Not found') }}</span>
                    <span><b>{{ translate('Category') }}:</b> {{ $product->category?->name ?? translate('Not found') }}</span>
                    <span><b>{{ translate('Brand') }}:</b> {{ $product->brand?->name ?? translate('Not found') }}</span>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="pos-empty py-4 border-0">
        <div class="pos-empty__title">{{ translate('No Product Found') }}</div>
    </div>
@endif
