@php
    $v2DefaultSection = activeAdminArea()->sections[0] ?? 'home';
@endphp

<aside class="v2-ctxpanel" id="v2-ctxpanel">
    <div class="v2-ctx-scroll">
        @includeFirst([
            'layouts.admin.partials.v2.sidebar.areas._'.activeAdminArea()->key,
            'layouts.admin.partials.v2.sidebar.areas._retail',
        ])
    </div>

    @php
        $v2Setup = function_exists('checkSetupGuideRequirements') ? checkSetupGuideRequirements(panel: 'admin') : ['completePercent' => 100, 'totalSteps' => 0];
        $v2ShowSetupGuide = ($v2Setup['completePercent'] ?? 100) < 100 && auth('admin')->user()->admin_role_id == 1;
    @endphp
    @if ($v2ShowSetupGuide)
        <div class="v2-ctx-foot">
            <button type="button" class="v2-setup-guide" data-v2-setup-trigger aria-label="{{ translate('Setup Guide') }}">
                <div class="v2-setup-guide-icon">
                    <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/setup_guide.png') }}"
                         width="18" height="18" alt="">
                </div>
                <div class="v2-setup-guide-body">
                    <span class="v2-setup-guide-title">{{ translate('Setup Guide') }}</span>
                    <span class="v2-setup-guide-sub">{{ $v2Setup['completePercent'] ?? 0 }}% {{ translate('Complete') }}</span>
                </div>
                <div class="v2-setup-guide-chev">
                    <svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                </div>
            </button>
        </div>
    @endif

</aside>

<aside class="v2-rail-flyout" id="v2-rail-flyout" aria-hidden="true"></aside>

<div class="v2-palette-scrim" id="v2-palette-scrim" hidden>
    <div class="v2-palette">
        <div class="v2-palette-head">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="9" cy="9" r="5"/><path d="m13 13 3.5 3.5"/>
            </svg>
            <input id="v2-palette-input" type="text" placeholder="{{ translate('Jump to anything') }} — {{ translate('Orders vendors settings') }}…" autocomplete="off"/>
            <span class="v2-kbd">esc</span>
        </div>
        <div class="v2-palette-list" id="v2-palette-list"></div>
        <div class="v2-palette-foot">
            <span class="v2-palette-hint"><span class="v2-kbd">↑</span><span class="v2-kbd">↓</span> {{ translate('Navigate') }}</span>
            <span class="v2-palette-hint"><span class="v2-kbd">↵</span> {{ translate('Open') }}</span>
            <span class="v2-palette-hint"><span class="v2-kbd">esc</span> {{ translate('Close') }}</span>
        </div>
    </div>
</div>
