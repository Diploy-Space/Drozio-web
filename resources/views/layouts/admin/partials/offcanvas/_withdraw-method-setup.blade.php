@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Withdrawal Methods') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWithdrawSetup_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Withdraw Method List') }}</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapseWithdrawSetup_01">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('This section lists all withdraw methods available to vendors and customers.') }}
                        {{ translate('Shows each method required fields, who it applies to, and its status.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWithdrawSetup_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Add Withdraw method') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseWithdrawSetup_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Click add new method to open the setup panel, then provide:') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12 mb-0">
                        <li>{{ translate('Method name') }}: {{ translate('The name shown to users e.g. paypal or bank transfer.') }}</li>
                        <li>{{ translate('Method for') }}: {{ translate('Choose whether the method is available to vendors, customers, or both.') }}</li>
                        <li>{{ translate('Method fields') }}: {{ translate('Add each field with its type, name, placeholder and requirement.') }}</li>
                        <li>{{ translate('Default method') }}: {{ translate('Enable this to use the method as the default withdraw option.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWithdrawSetup_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Edit Withdraw method') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseWithdrawSetup_03">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Click edit on a method to update its name, availability or fields.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseWithdrawSetup_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Default and status') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseWithdrawSetup_04">
                <div class="card card-body">
                    <p class="fs-12 mb-0">
                        {{ translate('Default toggle sets the primary withdraw method; status enables or disables one.') }}
                        {{ translate('The default method cannot be disabled or deleted.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
