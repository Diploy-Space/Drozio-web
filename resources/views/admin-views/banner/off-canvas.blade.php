@if(theme_root_path() == 'theme_aster')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="bannerSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
         data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}" style="--bs-offcanvas-width: 700px;">

        <div class="offcanvas-header bg-body">
            <div>
                <h3 class="mb-1">{{ translate('How It Work') }}</h3>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse1" aria-expanded="true">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Main Banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3 show" id="bannerCollapse1">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown at the top of your site or app.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/main-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse2" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Popup banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse2">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown on site entry. One banner only. Max 2 MB, ratio 1:1.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/popup-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse3" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Footer banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse3">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown above the footer. One banner only. Max 2 MB, ratio 4:1.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/footer-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse4" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Main section banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse4">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown below the main banner, up to two banners.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/main-section-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse5" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Header Banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse5">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown in the header nav bar. Max 2 MB, ratio 4:1.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/header-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse6" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Sidebar Banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse6">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown beside the intro section, one at a time.') }}
                        </p>
                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/sidebar-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#bannerCollapse8" aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Top Sidebar Banner') }} </span>
                    </button>
                </div>

                <div class="collapse mt-3" id="bannerCollapse8">
                    <div class="card card-body">
                        <p class="fs-12 mb-0">
                            {{ translate('Shown beside the intro section, one at a time.') }}
                        </p>

                        <img width="" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner-images/top-sidebar-banner.webp') }}" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


