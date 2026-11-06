@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('System Addons') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSystemAddons_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Upload Addons') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapseSystemAddons_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Upload add-ons for the customer website.') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>{{ translate('Purchase the add-on, then upload its zip file.') }}</li>
                        <li>{{ translate('Set PHP upload and post max size to at least 20 MB.') }}</li>
                        <li>{{ translate('Only a valid zip file of the addon is accepted.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSystemAddons_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Available Addons') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseSystemAddons_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Every add-on installed on the system, with its status.') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>{{ translate('Use the status toggle to activate or deactivate an add-on.') }}</li>
                        <li>{{ translate('An active add-on appears across admin, website and apps.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSystemAddons_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Delete') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseSystemAddons_03">
                <div class="card card-body">
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>{{ translate('An addon can only be deleted while it is deactivated.') }}</li>
                        <li>{{ translate('Deleting an addon permanently removes it and its data from the system.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
