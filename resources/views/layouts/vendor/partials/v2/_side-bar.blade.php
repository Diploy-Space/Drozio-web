@php
    $v2DefaultSection = activeAdminArea()->sections[0] ?? 'home';
@endphp

<aside class="v2-ctxpanel" id="v2-ctxpanel">
    <div class="v2-ctx-scroll">
        @includeFirst([
            'layouts.vendor.partials.v2.sidebar.areas._'.activeAdminArea()->key,
            'layouts.vendor.partials.v2.sidebar.areas._retail',
        ])
    </div>

    @php
        $v2Setup = function_exists('checkSetupGuideRequirements') ? checkSetupGuideRequirements(panel: 'vendor') : ['completePercent' => 100, 'totalSteps' => 0];
        $v2ShowSetupGuide = ($v2Setup['completePercent'] ?? 100) < 100;
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
