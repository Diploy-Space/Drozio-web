@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Page Setup') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapsePageSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Business page list') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapsePageSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('This section provides information regarding the existing business pages within the system.') }}
                         {{ translate('You can also create additional pages as needed.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Essential system pages cannot be deleted. Only their content is editable.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapsePageSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('New business page setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapsePageSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('To create business pages, follow these steps:') }}
                    </p>
                    <ul class="d-flex flex-column gap-2 fs-12">
                        <li>
                            {{ translate('Go to the add new page section.') }}
                        </li>
                        <li>
                            {{ translate('Set the page availability status to on.') }}
                        </li>
                        <li>
                            {{ translate('Upload the desired title background image.') }}
                        </li>
                        <li>
                            {{ translate('Input the page title and description content using the provided text editor.') }}
                        </li>
                        <li>
                            {{ translate('Save to add the page to the list and publish it.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
