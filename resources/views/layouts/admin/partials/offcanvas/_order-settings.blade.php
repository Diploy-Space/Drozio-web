@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Orders') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOrderSettings_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('General Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseOrderSettings_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('After ordering, the customer receives a delivery verification code.') }}
                         {{ translate('The delivery person asks for this code to confirm the handover.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Each vendor sets the smallest order a customer can place.') }}
                         {{ translate('If the order is too low, the customer can\'t buy it.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Asks customers for a billing address at checkout.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOrderSettings_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Setup Free Delivery') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseOrderSettings_02">
                <div class="card card-body">
                   <p class="fs-12">
                         {{ translate('Decide whether customers get free shipping.') }}
                         {{ translate('Free delivery for orders over an amount you choose.') }}

                   </p>
                   <p class="fs-12">
                         {{ translate('Choose who pays for free delivery when an order qualifies.') }}

                   </p>
                   <p class="fs-12">
                         {{ translate('Order total above which delivery is free.') }}
                   </p>
                </div>
            </div>
        </div>
    </div>
</div>
