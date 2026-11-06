@if($isVerifiedBadgeEnabled)
    <div>
        <div class="bg-section rounded d-flex flex-wrap align-items-center gap-2 p-2">
            @include('admin-views.vendor.partials._verified-badge', ['isVerified' => true, 'size' => 20])
            <span class="text-dark">
                {{ translate('Recommend') }}
                <b id="verification-recommendation-count">{{ $verificationCandidateCount }}</b>
                {{ translate('provider for verification') }}
            </span>
            <button type="button" class="btn btn-secondary bg-white text-nowrap" id="verification-criteria-trigger"
                    data-url="{{ route('admin.people.vendors.verification.criteria') }}">
                {{ translate('View Criteria') }}
            </button>
            <button type="button" class="btn btn-primary text-nowrap" id="verification-candidates-trigger"
                    data-url="{{ route('admin.people.vendors.verification.candidates') }}">
                {{ translate('View Providers') }}
            </button>
        </div>
        <div id="verification-offcanvas-holder"></div>
    </div>
@endif
