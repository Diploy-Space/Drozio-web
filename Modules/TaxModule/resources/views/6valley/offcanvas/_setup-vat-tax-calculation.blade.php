@include("taxmodule::6valley.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <div>
            <h3 class="mb-1">{{ translate('Setup Vat/Tax Calculation') }}</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('VAT/Tax Settings') }} </span>
                </button>
            </div>

            <div class="collapse mt-3 show" id="collapseFirebaseConfig_02">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Manage your entire tax configuration for vendors.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Accurately calculate charges on products.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Enable VAT/GST and choose whether tax is included or added.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('Apply VAT/Tax order-wise, product-wise or category-wise.') }}
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
                    <span class="fw-bold text-start">{{ translate('VAT Calculation Method') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseFirebaseConfig_03">
                <div class="card card-body">
                    <p> {{ translate('When VAT/Tax is calculated as included in the product price:') }}</p>
                    <p class="fs-12">-{{ translate('The VAT/Tax is already built into the product price.') }}</p>
                    <p class="fs-12">-{{ translate('No separate vat/tax line will be shown on invoices or reports.') }}</p>
                    <p class="fs-12">-{{ translate('VAT/TAX reports will not be generated from these totals.') }}</p>
                    <p class="fs-12">-{{ translate('Orders will show a label like: VAT/Tax Included.') }}</p>
                    <p> {{ translate('Exclude from Product Price:') }}</p>
                    <p class="fs-12">-{{ translate('VAT/Tax is added to the product price as a separate amount.') }}</p>
                    <p class="fs-12">-{{ translate('Shown as a separate line on bills, invoices and order summaries.') }}</p>
                    <p class="fs-12">-{{ translate('This enables accurate, detailed VAT/GST reporting for compliance and accounting.') }}</p>
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
                    <span class="fw-bold text-start">{{ translate('VAT/Tax Effect on Order') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseFirebaseConfig_04">
                <div class="card card-body">
                    <p class="fw-bold"> 1. {{ translate('Before Activating VAT/TAX Status:') }}</p>
                    <p class="fs-12">{{ translate('Orders placed before VAT/Tax is enabled are calculated without it.') }}</p>
                    <p class="fs-12">{{ translate('The system will not apply any VAT/Tax to these orders.') }}</p>
                    <p class="fw-bold"> 2. {{ translate('After Activating VAT/TAX Status') }}</p>
                    <p class="fs-12">{{ translate('Once active, new orders have tax applied from your VAT/Tax setup.') }}</p>
                    <p class="fs-12">{{ translate('The correct VAT/Tax is applied to these new orders.') }}</p>
                    <p class="fw-bold"> 3. {{ translate('If Deactivating VAT/Tax Status:') }}</p>
                    <p class="fs-12">{{ translate('Deactivating VAT/Tax means new orders are calculated without it.') }}</p>
                    <p class="fs-12">{{ translate('No VAT/Tax is applied to new orders until the status is reactivated.') }}</p>

                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_05" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Flexible VAT/Tax Application Options') }}</span>
                </button>
            </div>

            <div class="collapse mt-3" id="collapseFirebaseConfig_05">
                <div class="card card-body">
                    <p>{{ translate('VAT/Tax can be applied with flexibility depending on business needs.') }}</p>
                    <p>{{ translate('The VAT/Tax is already built into the product price.') }}</p>
                    <p class="fs-12">- {{ translate('Order wise VAT/Tax') }}: {{ translate('One rate applied to the whole order total.') }}</p>
                    <p class="fs-12">- {{ translate('Product wise VAT/Tax') }}: {{ translate('Different rates applied per product.') }}</p>
                    <p class="fs-12">- {{ translate('Category wise VAT/Tax') }}: {{ translate('Different rates apply by product category.') }}</p>

                    <p class="fw-bold">{{ translate('Basic Setup') }}</p>
                    <p class="fs-12">{{ translate('In the basic setup you can select VAT type (Order/Product/Category)') }}</p>
                    <p class="fs-12">{{ translate('Select one or more existing VAT rates to apply automatically.') }}</p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_06" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Additional Setup') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseFirebaseConfig_06">
                <div class="card card-body">
                    <p class="fs-12">{{ translate('Manage how tax applies to extra charges.') }}</p>
                    <p class="fs-12">{{ translate('Choose whether VAT/Tax applies to delivery and packaging fees.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>


