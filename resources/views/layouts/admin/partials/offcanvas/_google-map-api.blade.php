@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Google Map APIs') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyMapApi_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapse3rdPartyMapApi_01">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Google Maps APIs let the system embed maps.') }}
                        {{ translate('Add the credentials from your Google Maps platform account.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyMapApi_02" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Client & Server Keys') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartyMapApi_02">
                <div class="card card-body">
                    <div class="mb-3">
                        <h6 class="fw-semibold fs-14">{{ translate('Map api key') }} ({{ translate('Client') }})</h6>
                        <p class="fs-12 mb-0">{{ translate('Client key should have Maps enabled') }} {{ translate('Javascript API') }} {{ translate('And you can restrict it with HTTP referrer') }}.</p>
                    </div>
                    <div>
                        <h6 class="fw-semibold fs-14">{{ translate('Map api key') }} ({{ translate('Server') }})</h6>
                        <p class="fs-12 mb-0">{{ translate('Enable the Places API key. One key works for both fields.') }}.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse3rdPartyMapApi_03" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapse3rdPartyMapApi_03">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Map features will not work until this section is configured.') }}.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
