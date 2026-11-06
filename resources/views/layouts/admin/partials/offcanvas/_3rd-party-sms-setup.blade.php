@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('SMS Configuration') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartySMS_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapse3rdPartySMS_01">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Settings your system needs to send and receive SMS.') }}
                        {{ translate('API keys, IDs and credentials needed to send SMS.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartySMS_02" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('3rd Party SMS Gateways') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartySMS_02">
                <div class="card card-body">
                    <p class="fs-12 mb-2">
                        {{ translate('Choose the sms model for OTP and other messages.') }}.
                        {{ translate('Enter the API keys and credentials from your provider, then switch on.') }}
                    </p>
                    <ul class="d-flex flex-column gap-2 fs-12 mb-0">
                        <li>2Factor</li>
                        <li>MSG91</li>
                        <li>Twilio</li>
                        <li>Alphanet SMS</li>
                        <li>Releans</li>
                        <li>Nexmo</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartySMS_03" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Firebase OTP') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartySMS_03">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Use Firebase to send OTP instead of a third-party gateway.') }}
                        {{ translate('Setup necessary') }} {{ translate('Firebase Configurations') }} {{ translate('First to activate this option.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartySMS_04" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartySMS_04">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Only one SMS sending method can be enabled at a time.') }}
                        {{ translate('Send a test SMS to verify your setup before enabling.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
