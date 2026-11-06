@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Business Setup') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Maintenance Mode') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseBS_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Temporarily closes your store to customers during maintenance.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Basic Information') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseBS_02">
                <div class="card card-body">
                    <h5>{{ translate('Company Name') }}</h5>
                    <p class="fs-12">
                        {{ translate('Your business name as a legal entity.') }}
                    </p>

                    <h5>{{ translate('Email') }}</h5>
                    <p class="fs-12">
                        {{ translate('Centralised management and archiving of business email.') }}
                    </p>

                    <h5>{{ translate('Phone') }}</h5>
                    <p class="fs-12">
                        {{ translate('A direct line for customers to reach your business.') }}
                    </p>

                    <h5>{{ translate('Country') }}</h5>
                    <p class="fs-12">
                        {{ translate('The country your business operates in, used for legal and financial settings.') }}
                    </p>

                    <h5>{{ translate('Address') }}</h5>
                    <p class="fs-12">
                        {{ translate('Legally required everywhere, and it builds customer trust.') }}
                    </p>

                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('General Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseBS_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Core business details such as address, legal information and basic settings.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Currency Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseBS_04">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Currency setup lets you define the primary currency for your ecommerce store.') }}
                        {{ translate('Select currency dropdown option helps to choose multiple currencies.') }}
                    </p>

                    <p class="fs-12">
                        {{ translate('Currency position helps to choose the symbol position left or right.') }}
                    </p>

                    <p class="fs-12">
                        {{ translate('Digits after the decimal point indicate the zeros after the decimal point.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_05" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Business Model Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseBS_05">
                <div class="card card-body">
                    <h5>{{ translate('Single Vendor') }}</h5>
                    <p class="fs-12">
                        {{ translate('One business sells its own products through its own store.') }}
                    </p>

                    <h5>{{ translate('Multi Vendor') }}</h5>
                    <p class="fs-12">
                        {{ translate('Many independent sellers list and sell their products on one website.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_06" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Payment Options') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseBS_06">
                <div class="card card-body">
                    <h5>{{ translate('Cash On Delivery') }}</h5>
                    <p class="fs-12">
                        {{ translate('The customer pays cash when the order is delivered.') }}
                        {{ translate('The delivery service collects the payment and forwards it to you.') }}
                    </p>

                    <h5>{{ translate('Digital Payment') }}</h5>
                    <p class="fs-12">
                        {{ translate('Customers pay by wallet, card or internet banking before shipping.') }}
                        {{ translate('The payment is processed electronically and transferred to your business account.') }}
                    </p>

                    <h5>{{ translate('Offline Payment') }}</h5>
                    <p class="fs-12">
                        {{ translate('The customer orders online and pays later by bank transfer or cash.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseBS_07" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Copyright And Cookies Text') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseBS_07">
                <div class="card card-body">
                    <h5>{{ translate('Copyright Text') }}</h5>
                    <p class="fs-12">
                        {{ translate('Ownership notice with the copyright symbol, year and company name.') }}
                        {{ translate('States that the work is protected by copyright and cannot be copied.') }}
                    </p>

                    <h5>{{ translate('Cookies Text') }}</h5>
                    <p class="fs-12">
                        {{ translate('Notice telling visitors the site uses cookies.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
