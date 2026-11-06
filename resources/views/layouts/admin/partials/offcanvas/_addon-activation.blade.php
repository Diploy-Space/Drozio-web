@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Addon Activation Process') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseAddonActivation_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Addon Activation Process') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapseAddonActivation_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Enable an add-on and enter its purchase code to activate it.') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>{{ translate('An add-on only works once it is activated here.') }}</li>
                        <li>{{ translate('You can deactivate it anytime from this page.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseAddonActivation_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Deliveryman App') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseAddonActivation_02">
                <div class="card card-body">
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>{{ translate('Turn on the status toggle to start activating the deliveryman app.') }}</li>
                        <li>{{ translate('Enter your name, email, username and purchase code, then save to activate.') }}</li>
                        <li>{{ translate('Enter the username and purchase code exactly, without spaces.') }}</li>
                        <li>
                            {{ translate('You can purchase the deliveryman app from') }}
                            <a href="https://store.6amtech.com/product/6valley-e-commerce-delivery-man-flutter-app/"
                               target="_blank" rel="noopener" class="text-decoration-underline fw-semibold">
                                {{ translate('Here') }}</a>.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
