<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
    <div class="h-100 {{ ($statHasDivider ?? false) ? 'border-left pl-md-4' : '' }}">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h4 class="mb-0">{{ $statLabel }}</h4>
            <span class="badge {{ $statIsActive ? 'badge-soft-info' : 'badge-soft-danger' }}">
                {{ $statIsActive ? translate('Active') : translate('Inactive') }}
            </span>
        </div>
        <div class="pair-list mb-3">
            @foreach ($statMetrics as $statMetricLabel => $statMetricValue)
                <div class="flex-nowrap overflow-wrap-anywhere">
                    <span class="key">{{ $statMetricLabel }}</span>
                    <span>:</span>
                    <span class="value text-primary font-weight-bold">{{ $statMetricValue }}</span>
                </div>
            @endforeach
        </div>
        @isset($statRating)
            <div class="d-flex gap-3 flex-wrap lh-1 border rounded bg-white text-dark px-3 py-2 mb-3">
                <span class="d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-star text-primary"></i>
                    <span class="font-weight-bold">{{ $statRating['average'] }}</span>
                </span>
                <span class="border-left"></span>
                <span><span class="font-weight-bold">{{ $statRating['ratings'] }}</span> {{ translate('Ratings') }}</span>
                <span class="border-left"></span>
                <span><span class="font-weight-bold">{{ $statRating['reviews'] }}</span> {{ translate('Reviews') }}</span>
            </div>
        @endisset
        @isset($statTin)
            <div class="fs-12">
                <div class="font-weight-bold">{{ translate('TIN Info') }}</div>
                <div>{{ $statTin['number'] }}{{ $statTin['expire_date'] ? ', '.$statTin['expire_date'] : '' }}</div>
            </div>
        @endisset
    </div>
</div>
