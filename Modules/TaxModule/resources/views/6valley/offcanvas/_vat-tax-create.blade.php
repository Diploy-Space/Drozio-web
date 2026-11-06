@include("taxmodule::6valley.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <div>
            <h3 class="mb-1">{{ translate('All VAT/TAX List') }}</h3>
            <p class="fs-12">{{ translate('This section allows you to manage all taxes applied to your products.') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('What is Tax') }} ?</span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapseFirebaseConfig_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('A mandatory charge added to the bill under local regulations.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Common types are VAT, GST and income tax.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Tax types and rates vary by country or region.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Why Set Up Taxes') }} ?</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseFirebaseConfig_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Ensure your restaurant stays legally compliant.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Accurately calculate charges on products.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Automatically apply the correct rates during checkout and invoicing.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Help generate proper tax reports for your accounting.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('How to Create VAT/Tax') }} ?</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseFirebaseConfig_03">
                <div class="card card-body">
                    <p class="fs-12">1. {{ translate('Set VAT/Tax Availability') }}</p>
                    <p class="fs-12">
                        {{ translate('Toggle the Status switch ON to activate the VAT/Tax.') }}<br>
                    </p>
                    <p class="fs-12">
                    {{ translate('Left off, the VAT/Tax is created but not applied.') }}
                    </p>
                    <p class="fs-12 mt-2">2. {{ translate('Enter VAT/Tax Name') }}</p>
                    <p class="fs-12">
                        {{ translate('Enter VAT/Tax Name') }}<br>
                    </p>
                    <p class="fs-12">
                        {{ translate('Example: Service Tax, VAT GST, Sales Tax') }}
                    </p>

                    <p class="fs-12 mt-2">3. {{ translate('Enter VAT/Tax Rate') }}</p>
                    <p class="fs-12">
                        {{ translate('Enter the VAT/Tax percentage.') }}
                    </p>

                    <p class="fs-12 mt-2">4. {{ translate('Submit the VAT/Tax') }}</p>
                    <p class="fs-12">
                        {{ translate('Click the Submit button to save the new tax.') }}<br>
                    </p>
                    <p class="fs-12">
                        {{ translate('Optionally click Reset to clear the form if needed.') }}
                    </p>
                </div>
            </div>
        </div>



        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_04" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('VAT/Tax Edit') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseFirebaseConfig_04">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Once added, a VAT/Tax cannot be renamed or deleted.') }}
                        {{ translate('Only the vat/tax rate can be updated later.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_05" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('List of VAT TAX') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseFirebaseConfig_05">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Manage VAT/Tax records here. Use Export to back up the list.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Click Create VAT/Tax to add a new entry.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('VAT/Tax entries can be activated or deactivated with their status toggle.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
