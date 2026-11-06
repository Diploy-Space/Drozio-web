<div class="offcanvas offcanvas-end" tabindex="-1" id="verificationCriteriaOffcanvas"
     aria-labelledby="verificationCriteriaOffcanvasLabel" style="--bs-offcanvas-width: 500px;">
    <div class="offcanvas-header bg-body">
        <h3 class="mb-0" id="verificationCriteriaOffcanvasLabel">{{ translate('Verification Criteria') }}</h3>
        <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;"
                data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross"></i>
        </button>
    </div>
    <div class="offcanvas-body">
        <p class="mb-3 mb-sm-20">
            {{ translate('You can give providers a Verified badge when they successfully meet the requirements listed below. This badge helps customers identify trusted and reliable providers on the platform.') }}
        </p>
        <div class="p-12 p-sm-20 bg-section rounded-10 overflow-wrap-anywhere">
            <h4 class="mb-3">{{ translate('They qualified the criteria') }}</h4>
            <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                @foreach($criteria as $criterion)
                    <li class="d-flex align-items-start gap-2">
                        <span class="text-success d-inline-flex lh-1 fs-16 pt-1">
                            <i class="fi fi-sr-check-circle"></i>
                        </span>
                        <span>{{ $criterion['leading'] }}<b class="text-dark">{{ $criterion['highlight'] }}</b>{{ $criterion['trailing'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
