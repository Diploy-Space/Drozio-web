@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Social Media') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSocialMediaLinks_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Setup social media link') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseSocialMediaLinks_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Choose a social network, add its link and save.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSocialMediaLinks_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Social media link list') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseSocialMediaLinks_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('An overview of every social media profile. For each link you see:') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                             {{ translate('Name') }}: {{ translate('The platform name (e.g., facebook, instagram, twitter).') }}
                        </li>
                        <li>
                             {{ translate('Social Media Link') }}: {{ translate('The direct URL to your profile on that platform.') }}
                        </li>
                        <li>
                             {{ translate('Status') }}: {{ translate('Indicates whether the link is currently enabled or disabled.') }}
                        </li>
                        <li>
                             {{ translate('Actions') }}: {{ translate('Edit the link details, or delete the link.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSocialMediaLinks_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Edit social media link') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseSocialMediaLinks_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Edit lets you change the social media name and link.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
