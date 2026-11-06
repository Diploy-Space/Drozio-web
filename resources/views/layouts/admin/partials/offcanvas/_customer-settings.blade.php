@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Customer') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseCustomerSettings_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Customer Wallet') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseCustomerSettings_01">
                <div class="card card-body">
                    <p class="fs-12">
                         {{ translate('Customers can pay for orders from their wallet balance.') . ' ' .  translate('Refunds can go straight to the customer wallet for future purchases.') }}
                    </p>
                    <h5>{{ translate('Add funds to wallet') }}</h5>
                    <p class="fs-12">
                        {{ translate('Customers top up their wallet with a digital payment method.') }}
                    </p>
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                            <strong>{{ translate('Minimum add amount') }}</strong>
                            {{ translate('The smallest amount a customer can add to their wallet.') }}
                        </li>
                        <li>
                            <strong>{{ translate('Maximum Add Amount') }}</strong>
                            {{ translate('Most a customer can add in one transaction or period.') . ' ' . translate('This helps control risk and manage wallet usage.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseCustomerSettings_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Customer Loyalty Point') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseCustomerSettings_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('How many loyalty points equal one unit of currency.') . ' ' . translate('(Ex: $1 if the system default currency is dollar).') . ' ' . translate('Shows customers what their points are worth in wallet money.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseCustomerSettings_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Customer Referral Earning Settings') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseCustomerSettings_03">
                <div class="card card-body">
                    <ul class="d-flex flex-column gap-12 fs-12">
                        <li>
                            {{ translate('Wallet reward earned when a new customer buys using their referral code.') }}
                        </li>
                        <li>
                            {{ translate('Amount, in the default currency, a referring customer earns.') . ' ' . translate('The reward arrives on the referred customer\'s first order.') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
