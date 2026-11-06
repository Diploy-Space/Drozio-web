@php
    $brandMenuItems = collect($brands)->filter(fn ($brand) => !empty($brand['slug']))->take($limit ?? 9);
@endphp

<div class="hm-head">
    <span class="hm-head__title">{{ translate('Brands') }}</span>
    <a class="hm-head__link" href="{{ $viewAllUrl }}">
        {{ translate('View All') }}
        <i class="fi fi-rr-angle-small-right"></i>
    </a>
</div>

<div class="hm-list hm-list--grid">
    @forelse($brandMenuItems as $brand)
        <a class="hm-item" href="{{ route($routeName, ['slug' => $brand['slug']]) }}">
            <span class="hm-item__icon">
                <img loading="lazy" alt="{{ $brand['image_alt_text'] ?? $brand['name'] }}"
                     src="{{ getStorageImages(path: $brand['image_full_url'], type: 'brand') }}">
            </span>
            <span class="hm-item__body">
                <span class="hm-item__label">{{ $brand['name'] }}</span>
            </span>
            @if(($brand[$countKey] ?? 0) > 0)
                <span class="hm-item__count">{{ $brand[$countKey] }}</span>
            @endif
        </a>
    @empty
        <p class="hm-empty">{{ translate('No brands found') }}</p>
    @endforelse
</div>
