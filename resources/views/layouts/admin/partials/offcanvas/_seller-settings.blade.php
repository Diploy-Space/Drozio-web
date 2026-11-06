@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Vendors') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSellerSettings_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('General Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseSellerSettings_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('The point-of-sale feature is active for vendors.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('You are now accepting new sellers to your marketplace.') }}
                        {{ translate('Vendors can sign up and start listing products.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('The smallest cart total a customer must reach to check out.') }}
                        {{ translate('Customers must reach this amount before they can check out.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Lets vendors reply to customer reviews on their product pages.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSellerSettings_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Forget Password Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseSellerSettings_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Lets users reset a forgotten password and regain access.') }}
                        {{ translate('Customers recover accounts with a code sent by email or phone.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
