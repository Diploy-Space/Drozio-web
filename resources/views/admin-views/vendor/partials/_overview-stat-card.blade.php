<div class="col-lg-4 col-md-6">
    <div class="h-100">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h4 class="mb-0">{{ $statLabel }}</h4>
            <span class="badge {{ $statIsActive ? 'badge-soft-info' : 'badge-soft-danger' }}">
                {{ $statIsActive ? translate('Active') : translate('Inactive') }}
            </span>
        </div>
        <div class="pair-list mb-3">
            @foreach ($statMetrics as $statMetricLabel => $statMetricValue)
                <div class="flex-nowrap overflow-wrap-anywhere align-items-center">
                    <span class="key">{{ $statMetricLabel }}</span>
                    <span>:</span>
                    <span class="value text-primary fw-bold fs-16">{{ $statMetricValue }}</span>
                </div>
            @endforeach
        </div>
        @isset($statRating)
            <div class="row g-0 mx-0 lh-1 border rounded bg-white text-dark py-2">
                <span class="col d-flex gap-2 justify-content-center align-items-center px-2">
                    <i class="fi fi-sr-star text-primary"></i>
                    <span class="fw-bold">{{ $statRating['average'] }}</span>
                </span>
                <span class="col d-flex gap-1 justify-content-center align-items-center border-start px-2 text-nowrap">
                    <span class="fw-bold">{{ $statRating['ratings'] }}</span> {{ translate('Ratings') }}
                </span>
                <span class="col d-flex gap-1 justify-content-center align-items-center border-start px-2 text-nowrap">
                    <span class="fw-bold">{{ $statRating['reviews'] }}</span> {{ translate('Reviews') }}
                </span>
            </div>
        @endisset
    </div>
</div>
