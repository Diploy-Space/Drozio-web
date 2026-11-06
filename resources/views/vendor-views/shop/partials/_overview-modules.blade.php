<div class="bg-section rounded p-3 p-sm-4 mt-4">
    <div class="row">
        @include('vendor-views.shop.partials._overview-stat-card', $retailOverview)
        @if (businessModuleManager()->isAvailableInPanel(\App\Enums\BusinessModule::WHOLESALE))
            @includeIf('wholesale::vendor-views.shop.partials._overview-stats')
        @endif
        @if (businessModuleAvailable(\App\Enums\BusinessModule::AUCTION))
            @includeIf('auction::vendor-views.shop.partials._overview-stats')
        @endif
    </div>
</div>
