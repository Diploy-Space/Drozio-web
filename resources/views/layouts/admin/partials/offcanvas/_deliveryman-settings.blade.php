@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Delivery Men') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseDMS_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Proof of Delivery') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseDMS_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Lets the delivery man upload a photo as proof of delivery.') }}
                        <br>
                        {{ translate('Makes things clearer if there are questions about the delivery.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseDMS_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Forget Password Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseDMS_02">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('Delivery men who forget their password click Forgot Password.') }}
                         {{ translate('They reset the password by phone or email and carry on delivering.') }}
                         {{ translate('Customers recover accounts with a code sent by email or phone.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
