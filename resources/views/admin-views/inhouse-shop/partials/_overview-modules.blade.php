<div class="bg-section rounded p-3 p-sm-4 mt-4">
    <div class="row g-3">
        @include('admin-views.inhouse-shop.partials._overview-stat-card', $retailOverview)
        @if (businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::WHOLESALE))
            @includeIf('wholesale::admin-views.inhouse-shop.partials._overview-stats')
        @endif
        @if (businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::AUCTION))
            @includeIf('auction::admin-views.inhouse-shop.partials._overview-stats')
        @endif
    </div>
</div>
