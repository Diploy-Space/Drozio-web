<div class="row g-4 vendor-overview-modules">
    @include('admin-views.vendor.partials._overview-stat-card', $retailOverview)
    @if (businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::WHOLESALE))
        @includeIf('wholesale::admin-views.vendor.partials._overview-stats')
    @endif
    @if (businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::AUCTION))
        @includeIf('auction::admin-views.vendor.partials._overview-stats')
    @endif
</div>
