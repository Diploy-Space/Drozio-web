@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('ERP Integration') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseErp_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Purpose') }}</span>
                </button>
            </div>
            <div class="collapse mt-3 show" id="collapseErp_01">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Connect your store to an ERP system with a secure API token.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseErp_02" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('How it works') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseErp_02">
                <div class="card card-body d-flex flex-column gap-3">
                    <div class="d-flex gap-2">
                        <span class="erp-step-badge">1</span>
                        <div>
                            <h6 class="fw-semibold fs-14 mb-1">{{ translate('Generate Token') }}</h6>
                            <p class="fs-12 mb-0">{{ translate('Enter a token name and your ERP webhook URL, then generate.') }}.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="erp-step-badge">2</span>
                        <div>
                            <h6 class="fw-semibold fs-14 mb-1">{{ translate('Copy Credentials') }}</h6>
                            <p class="fs-12 mb-0">{{ translate('Copy the key, secret and base URL now. Secret shows once.') }}.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="erp-step-badge">3</span>
                        <div>
                            <h6 class="fw-semibold fs-14 mb-1">{{ translate('Add Connection in ERP') }}</h6>
                            <p class="fs-12 mb-0">{{ translate('In your ERP panel open Settings, Integration, Add Connection, paste credentials.') }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseErp_03" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Queue Worker Setup Guide') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseErp_03">
                <div class="card card-body">
                    <p class="fs-12 mb-3">
                        {{ translate('ERP integration needs a running queue worker. Download the setup guide.') }}.
                    </p>
                    <a href="{{ dynamicAsset(path: 'public/assets/new/back-end/pdf/erp-queue-worker-setup-guide.pdf') }}"
                       class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 align-self-start" download>
                        <i class="fi fi-rr-download"></i>
                        {{ translate('Download PDF') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseErp_04" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Video Tutorial') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseErp_04">
                <div class="card card-body">
                    <div class="rounded-8 overflow-hidden border ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/L0imBakAkg8"
                                title="{{ translate('ERP Integration Setup Guide') }}"
                                loading="lazy" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseErp_05" aria-expanded="false">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Tip') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseErp_05">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Copy the API key and secret now. The secret shows once.') }}.
                        {{ translate('Revoke a token to stop the ERP connection using it.') }}.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
