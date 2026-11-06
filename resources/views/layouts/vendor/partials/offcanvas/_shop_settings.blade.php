@include("layouts.vendor.partials.offcanvas._view-guideline-button")

<div class="offcanvas-sidebar guide-offcanvas" id="offcanvasSetupGuide" data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">
    <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

    <div class="offcanvas-content bg-white shadow d-flex flex-column">
        <div class="offcanvas-header bg-light d-flex justify-content-between align-items-center p-3">
            <h3 class="text-capitalize m-0">{{ translate('Shop Settings') }}</h3>
            <button type="button" class="close" data-dismiss="offcanvas" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="offcanvas-body p-3 overflow-auto flex-grow-1">
           
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0"
                            type="button"
                            data-toggle="collapse"
                            data-target="#showInfoSettings_01"
                            aria-expanded="true">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Store Availability') }}</span>
                    </button>
                </div>

                <div class="collapse mt-3 show" id="showInfoSettings_01">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Show or hide your store and its products from customers.') }}.
                            {{ translate('Turning the status off hides your store and products from listings.') }}
                        </p>
                    </div>
                </div>
            </div>

     
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0"
                            type="button"
                            data-toggle="collapse"
                            data-target="#showInfoSettings_02"
                            aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Shop Details') }}</span>
                    </button>
                </div>

                <div class="collapse mt-3" id="showInfoSettings_02">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Shows store availability, creation date and other details.') }}.
                            {{ translate('The date they were created and other relevant details') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0"
                            type="button"
                            data-toggle="collapse"
                            data-target="#showInfoSettings_03"
                            aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Visit Website') }}</span>
                    </button>
                </div>

                <div class="collapse mt-3" id="showInfoSettings_03">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Visit Website opens your store page on the customer site.') }}.
                        </p>
                    </div>
                </div>
            </div>

    
            <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
                <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                    <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0"
                            type="button"
                            data-toggle="collapse"
                            data-target="#showInfoSettings_04"
                            aria-expanded="false">
                        <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark">
                            <i class="fi fi-sr-angle-right"></i>
                        </div>
                        <span class="fw-bold text-start">{{ translate('Edit Shop') }}</span>
                    </button>
                </div>

                <div class="collapse mt-3" id="showInfoSettings_04">
                    <div class="card card-body">
                        <p class="fs-12">
                            {{ translate('Use Edit Shop to change your store information.') }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
