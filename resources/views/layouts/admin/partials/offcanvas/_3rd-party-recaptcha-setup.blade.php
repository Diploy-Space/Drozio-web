@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('reCAPTCHA') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyRecapcha_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapse3rdPartyRecapcha_01">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Turning on ReCAPTCHA protects your site from spam.') }}.
                        {{ translate('Users must pass a ReCAPTCHA check.') }}.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyRecapcha_02" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Setup Steps') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartyRecapcha_02">
                <div class="card card-body">
                    <ul class="d-flex flex-column gap-12 fs-12 mb-0">
                        <li>{{ translate('First, activate the ReCAPTCHA status.') }}</li>
                        <li>
                            {{ translate('Provide the credentials for your ReCAPTCHA version.') }}
                            {{ translate('See the how to get credentials guide for instructions.') }}
                        </li>
                        <li>{{ translate('Finally, save the entered credentials to activate ReCAPTCHA.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyRecapcha_03" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartyRecapcha_03">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Set up v3, or the default ReCAPTCHA is shown.') }}.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
