@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Social Media') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyLogin_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Social Media Chat') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapse3rdPartyLogin_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Enable customer chat over social media. WhatsApp is supported.') }}
                        {{ translate('Activate the status and enter a WhatsApp number.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyLogin_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('3rd-social media login') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartyLogin_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('This section allows customers to log in using their social media accounts.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Currently, google, facebook, and apple login functionalities are supported.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Add credentials from each social media platform to enable these options.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
