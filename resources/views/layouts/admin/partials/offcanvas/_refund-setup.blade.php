@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Refund') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseRefundSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Refund Order') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseRefundSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('Refunds go back into the customer wallet.') }}
                         {{ translate('To manage these settings, click here.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Days after ordering that a customer can request a refund.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Whether refunds are credited to the customer wallet automatically.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
