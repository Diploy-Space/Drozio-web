@include("taxmodule::6valley.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <div>
            <h3 class="mb-1">{{ translate('Vendor VAT Report') }}</h3>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_07" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Vendor VAT Report') }}</span>
                </button>
            </div>
            <div class="collapse mt-3 show" id="collapseFirebaseConfig_07">
                <div class="card card-body">
                    <p> {{ translate('Filter by vendor and date range to narrow the VAT report.') }}</p>
                    <p> {{ translate('Vendor-wise tax detail for accounting and auditing reports.') }}</p>
                    <p>{{translate('Key figures giving a quick overview of your business performance.')}}</p>
                    <p>{{translate('Total orders, order amount and VAT collected in the selected period.')}}</p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFirebaseConfig_08" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('VAT Detail (for specific vendor)') }}</span>
                </button>
            </div>
            <div class="collapse mt-3" id="collapseFirebaseConfig_08">
                <div class="card card-body">
                    <p> {{ translate('Key figures giving a quick overview of a vendor performance.') }}</p>
                    <p> {{ translate('Export the vendor VAT list for reporting or record-keeping.') }}</p>
                </div>
            </div>
        </div>

    </div>
</div>
