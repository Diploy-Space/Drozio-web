<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <div>
            <h3 class="mb-1">{{ translate('Vendor Usage Limit Guideline') }}</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapsePurpose" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapsePurpose">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Limits how much vendors can use the AI tools.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVendorLimits" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Vendor Limits On Using AI') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseVendorLimits">
                <div class="card card-body">
                    <div class="mb-4">
                        <h6 class="fw-semibold fs-14">{{ translate('Limit For Section Wise Data Generation') }}</h6>
                        <p class="fs-12 mb-0">{{ translate('How many times AI can generate data for each vendor panel element.') }}</p>
                    </div>
                    <div>
                        <h6 class="fw-semibold fs-14">{{ translate('Limit For Image Based Data Generation') }}</h6>
                        <p class="fs-12 mb-0">{{ translate('How many times vendors can generate data from an image.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTip" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseTip">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Set 0 for unlimited. A higher number lets vendors use AI more.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
