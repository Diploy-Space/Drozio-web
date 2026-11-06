@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Digital Payment') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseDPS_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Available digital payment method') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseDPS_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('This section lists all digital payment methods integrated into the system.') }}
                        {{ translate('Each method must be configured before it can be used for payments.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseDPS_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Digital payment methods setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseDPS_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Configuring a digital payment method from the available list involves these steps:') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                            {{ translate('Enable the status of the payment method.') }}
                        </li>
                        <li>
                            {{ translate('Credentials from your digital payment service provider.') }}
                        </li>
                        <li>
                            {{ translate('Save the filled form to activate the payment method for use') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
