<div class="hm-list">
    @foreach($items as $item)
        @if(($item['divider'] ?? false))
            <hr class="hm-divider">
        @endif
        <a class="hm-item {{ ($item['danger'] ?? false) ? 'hm-item--danger' : '' }}" href="{{ $item['url'] }}">
            <span class="hm-item__icon"><i class="{{ $item['icon'] }}"></i></span>
            <span class="hm-item__body">
                <span class="hm-item__label">{{ $item['label'] }}</span>
                @if(!empty($item['description']))
                    <span class="hm-item__desc">{{ $item['description'] }}</span>
                @endif
            </span>
            @if(($item['count'] ?? 0) > 0)
                <span class="hm-item__count">{{ $item['count'] }}</span>
            @endif
        </a>
    @endforeach
</div>
