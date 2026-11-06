@if (! empty($moduleTabs))
    <ul class="nav nav-tabs nav--tabs mb-3 px-2 px-xl-0">
        @foreach ($moduleTabs as $moduleTab)
            <li class="nav-item">
                <a class="{{ $moduleTab['is_active'] ? 'active' : '' }}" href="{{ $moduleTab['url'] }}">
                    {{ $moduleTab['label'] }}@if (! is_null($moduleTab['count'])) (<span data-module-tab-count="{{ $moduleTab['key'] }}">{{ $moduleTab['count'] }}</span>)@endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
