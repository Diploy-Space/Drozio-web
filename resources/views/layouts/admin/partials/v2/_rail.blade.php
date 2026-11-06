<aside class="v2-rail">
    <div class="v2-rail-items">
        @includeFirst([
            'layouts.admin.partials.v2.rail.areas._'.activeAdminArea()->key,
            'layouts.admin.partials.v2.rail.areas._retail',
        ])
    </div>

    @php
        $v2SetupRail = function_exists('checkSetupGuideRequirements') ? checkSetupGuideRequirements(panel: 'admin') : ['completePercent' => 100];
    @endphp
    @if (($v2SetupRail['completePercent'] ?? 100) < 100 && auth('admin')->user()->admin_role_id == 1)
        <div class="v2-rail-foot">
            <button type="button" class="v2-rail-setup"
                    data-v2-setup-trigger
                    data-label="{{ translate('Setup Guide') }} · {{ $v2SetupRail['completePercent'] ?? 0 }}%"
                    aria-label="{{ translate('Setup Guide') }}">
                <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/setup_guide.png') }}" alt="">
                <span class="v2-rail-setup-badge">{{ $v2SetupRail['completePercent'] ?? 0 }}%</span>
            </button>
        </div>
    @endif

    <div class="v2-rail-account">
        @include('layouts.admin.partials.v2._profile-menu')
    </div>
</aside>
