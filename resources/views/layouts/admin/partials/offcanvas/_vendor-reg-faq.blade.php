@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('FAQs') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVendorRegFaq_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('FAQ Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseVendorRegFaq_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('To add a FAQ, follow these steps:') }}
                    </p>
                    <ul class="d-flex flex-column gap-2 fs-12">
                        <li>
                            {{ translate('Enter question') }}
                        </li>
                        <li>
                            {{ translate('Provide a concise and relevant answer within the specified character limit.') }}
                        </li>
                        <li>
                            {{ translate('FAQ priority from 1 (highest) to 10 (lowest).') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseVendorRegFaq_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('List of FAQ') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseVendorRegFaq_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Added FAQs appear in the list.') }}
                        {{ translate('Edit the status, content and priority of each FAQ, or delete it.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
