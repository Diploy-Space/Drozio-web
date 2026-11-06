@if (count($moduleScopeOptions ?? []))
    @php($moduleScopeCurrent = collect($moduleScopeOptions)->firstWhere('active', true) ?? $moduleScopeOptions[0])
    @php($moduleScopeLocked = count($moduleScopeOptions) === 1)
    @php($moduleScopeId = 'business-module-scope-'.Str::random(6))

    <div class="business-module-scope">
        <span class="business-module-scope-caption">{{ translate('Business module') }}</span>

        <div class="dropdown">
            <button class="business-module-scope-btn {{ $moduleScopeLocked ? '' : 'dropdown-toggle' }}"
                    type="button" id="{{ $moduleScopeId }}"
                    @if ($moduleScopeLocked) disabled @else data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @endif>
                <img width="18" height="18" alt=""
                     src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/'.$moduleScopeCurrent['icon']) }}">
                <span>{{ translate($moduleScopeCurrent['label']) }}</span>
            </button>

            @unless ($moduleScopeLocked)
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-right v2-switcher-menu" aria-labelledby="{{ $moduleScopeId }}">
                    <div class="v2-switcher-panel">
                        @foreach ($moduleScopeOptions as $moduleScopeOption)
                            <a href="{{ $moduleScopeOption['url'] }}"
                               class="dropdown-item v2-switcher-option {{ $moduleScopeOption['active'] ? 'v2-is-active' : '' }}">
                                <img width="20" height="20" alt="{{ translate($moduleScopeOption['label']) }}"
                                     src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/'.$moduleScopeOption['icon']) }}">
                                <span>{{ translate($moduleScopeOption['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endif
