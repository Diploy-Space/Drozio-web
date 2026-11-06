@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Marketing Tool') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseAnalyticsSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Marketing Tools') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseAnalyticsSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Software and platforms used to plan, run and analyse marketing.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Purpose of setting up marketing tools for an ecommerce system') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                             {{ translate('Attracting potential customers (acquisition)') }}
                        </li>
                        <li>
                             {{ translate('Engaging and converting visitors') }}
                        </li>
                        <li>
                             {{ translate('Retaining and engaging existing customers') }}
                        </li>
                        <li>
                             {{ translate('Analyzing performance and optimizing strategies') }}
                        </li>
                    </ul>
                    <p class="fs-12">
                        {{ translate('Google Analytics, Tag Manager and the major social media pixels.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
