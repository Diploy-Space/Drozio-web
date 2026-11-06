@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Why Sell With Us') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVendorRegWithUs_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Why sell with us setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseVendorRegWithUs_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('This section highlights the benefits of vendors joining the system.') }}
                         {{ translate('It offers two setup options:') }}
                    </p>
                    <ul class="d-flex flex-column gap-2 fs-12">
                        <li>
                            {{ translate('One for the section title, subtitle and image; another for the reasons.') }}
                        </li>
                        <li>
                            {{ translate('Each reason shows its title, subtitle and image on registration.') }}
                        </li>
                    </ul>
                    <p class="fs-12">
                         {{ translate('This is aimed at attracting vendors.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVendorRegWithUs_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Reason List') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseVendorRegWithUs_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Manage the reasons shown in the Why Sell With Us section.') }}
                        {{ translate('Update, reorder, enable or delete reasons from this list.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
