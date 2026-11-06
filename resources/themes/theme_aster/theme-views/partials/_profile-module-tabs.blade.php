@if (! empty($moduleTabs))
    <ul class="nav nav--tabs gap-3 mt-3">
        @foreach ($moduleTabs as $moduleTab)
            <li class="nav-item">
                <a class="{{ $moduleTab['is_active'] ? 'active' : '' }}" href="{{ $moduleTab['url'] }}">{{ $moduleTab['label'] }}@if (! is_null($moduleTab['count'])) (<span data-module-tab-count="{{ $moduleTab['key'] }}">{{ $moduleTab['count'] }}</span>)@endif</a>
            </li>
        @endforeach
    </ul>
@endif
