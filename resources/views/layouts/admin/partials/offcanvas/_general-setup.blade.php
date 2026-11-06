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
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Maintenance Mode') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseGeneralSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Turning on maintenance mode will temporarily close your online store.') . ' ' . translate('So that the admin can do important updates or fixes.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Basic Information') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        <strong>{{ translate('Company Name') }}:</strong>
                        {{ translate('Your business name as a legal entity.') }}
                    </p>
                    <p class="fs-12">
                        <strong>{{ translate('Email') }}:</strong>
                        {{ translate('Centralised management and archiving of business email.') }}
                    </p>
                    <p class="fs-12">
                        <strong>{{ translate('Phone') }}:</strong>
                        {{ translate('A direct line for customers to reach your business.') }}
                    </p>
                    <p class="fs-12">
                        <strong>{{ translate('Country') }}:</strong>
                        {{ translate('The country your business operates in, used for legal and financial settings.') }}
                    </p>
                    <p class="fs-12">
                        <strong>{{ translate('Address') }}:</strong>
                        {{ translate('Legally required everywhere, and it builds customer trust.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('General Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Core business details such as address, legal information and basic settings.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Currency Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_04">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Choose the default currency for your store.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('You can add multiple currencies from the currency setup page.') . ' ' . translate('But the default currency is the one you select from this dropdown.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Whether the currency symbol appears before or after the amount.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('How many digits appear after the decimal point, such as 10.00.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_05" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Business Model Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_05">
                <div class="card card-body">
                    <h5> {{ translate('Single Vendor') }}</h5>
                    <p class="fs-12">
                        {{ translate('One business sells its own products through its own store.') }}
                    </p>
                    <h5> {{ translate('Multi Vendor') }}</h5>
                    <p class="fs-12">
                        {{ translate('Many independent sellers list and sell their products on one website.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_06" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Payment Options') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_06">
                <div class="card card-body">
                    <h5> {{ translate('Cash On Delivery') }}</h5>
                    <p class="fs-12">
                        {{ translate('The customer pays cash when the order is delivered.') . ' ' . translate('They deliver the products, collect payment and forward it to you.') }}
                    </p>
                    <h5> {{ translate('Digital Payment') }}</h5>
                    <p class="fs-12">
                        {{ translate('Customers pay online by wallet, card or internet banking at checkout.') . ' ' . translate('It is processed automatically and sent to the business account of admin.') }}
                    </p>
                    <h5> {{ translate('Offline Payment') }}</h5>
                    <p class="fs-12">
                        {{ translate('The customer orders online and pays later by bank transfer or cash.') . ' ' . translate('After payment, the customer or admin updates the order payment details.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('The admin will then check the payment and manually confirm the order.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseGeneralSetup_07" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Copyright And Cookies Text') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseGeneralSetup_07">
                <div class="card card-body">
                    <h5> {{ translate('Copyright Text') }}</h5>
                    <p class="fs-12">
                        {{ translate('A short statement that your company owns the site content.') . ' ' . translate('Usually the copyright symbol, the year and your company name.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('States that the content is protected by copyright and cannot be copied.') }}
                    </p>
                    <h5> {{ translate('Company cookies text') }}</h5>
                    <p class="fs-12">
                        {{ translate('Notice telling visitors the site uses cookies.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
