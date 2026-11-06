@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Offline Payment') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOffinePaymentSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Offline Payment Methods List') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseOffinePaymentSetup_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('This section displays all offline payment methods available for use.') }}
                        {{ translate('Shows the payment account details and what the customer must provide.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOffinePaymentSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Offline payment method setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseOffinePaymentSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('To configure an offline payment method, follow these steps:') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                             {{ translate('Payment Information') }}: {{ translate('Enter the payment method name and account details in the provided fields.') }}
                        </li>
                        <li>
                            {{ translate('Information required from customer') }}: {{ translate('Information customers must provide to verify this offline payment method.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOffinePaymentSetup_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Edit payment method') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseOffinePaymentSetup_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Find the method in the list and click edit to update.') }}
                        {{ translate('You can then modify its details.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
