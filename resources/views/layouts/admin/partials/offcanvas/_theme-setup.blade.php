@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Theme Setup') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseThemeSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Theme Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseThemeSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('To install a new theme on the system') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                             {{ translate('Purchase the theme, then upload its file to set it up.') }}
                        </li>
                        <li>
                            {{ translate('Once the theme file uploads, you can activate it immediately.') }}
                        </li>
                        <li>
                            {{ translate('Activate a theme later from the available themes list.') }}
                        </li>
                        <li>
                            {{ translate('Theme activation needs your CodeCanyon username and purchase code.') }}
                        </li>
                        <li>
                            {{ translate('After activation a popup lists the visual changes the theme makes.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseThemeSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Available Themes') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseThemeSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('The available themes list displays all uploaded themes.') }}
                        {{ translate('Select a theme from this list to apply it.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseThemeSetup_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Explore other themes') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseThemeSetup_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('View Themes opens a page of themes built for this system.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseThemeSetup_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Theme Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseThemeSetup_04">
                <div class="card card-body">
                   <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                             {{ translate('Change the website theme by choosing another from the list.') }}
                        </li>
                        <li>
                             {{ translate('Activate it after selection if it was not activated on upload.') }}
                        </li>
                        <li>
                             {{ translate('Once activated, select the theme to apply it.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
