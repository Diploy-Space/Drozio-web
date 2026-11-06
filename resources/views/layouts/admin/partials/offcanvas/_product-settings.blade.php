@include("layouts.admin.partials.offcanvas._view-guideline-button")

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSetupGuide" aria-labelledby="offcanvasSetupGuideLabel"
     data-status="{{ request('offcanvasShow') && request('offcanvasShow') == 'offcanvasSetupGuide' ? 'show' : '' }}">

    <div class="offcanvas-header bg-body">
        <h3 class="mb-0">{{ translate('Products') }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseProductSettings_01" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('General Setup') }}</span>
                </button>

            </div>

            <div class="collapse mt-3 show" id="collapseProductSettings_01">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('Sell downloadable goods such as ebooks, software, music and videos.') }}
                        {{ translate('Expand beyond physical items into the digital products market.') }}
                    </p>
                    <p class="fs-12">
                         {{ translate('Showing the brand tells customers who makes the item.') }}
                         {{ translate('This builds trust and provides important information for their purchase decisions.') }}

                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseProductSettings_02" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Need Product Approval') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseProductSettings_02">
                <div class="card card-body">
                    <h5>{{ translate('New product approval') }}</h5>
                    <p class="fs-12">
                        {{ translate('New vendor products need admin approval before going online.') }}
                    </p>
                    <h5>{{ translate('Update Product Wise Shipping Cost') }}</h5>
                    <p class="fs-12">
                        {{ translate('Vendors need admin approval before an updated shipping cost applies.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="p-12 p-sm-20 bg-section rounded mb-3 mb-sm-20">
            <div class="d-flex gap-3 align-items-center justify-content-between overflow-hidden">
                <button class="btn-collapse d-flex gap-3 align-items-center bg-transparent border-0 p-0 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseProductSettings_03" aria-expanded="true">
                    <div class="btn-collapse-icon border bg-light icon-btn rounded-circle text-dark collapsed">
                        <i class="fi fi-sr-angle-right"></i>
                    </div>
                    <span class="fw-bold text-start">{{ translate('Reorder Level') }}</span>
                </button>

            </div>

            <div class="collapse mt-3" id="collapseProductSettings_03">
                <div class="card card-body">
                    <p class="fs-12">
                        {{ translate('The reorder point is the minimum stock level for a product.') . ' ' . translate('Below this level the product is flagged as Low Stock.') }}
                    </p>
                    <p class="fs-12">
                        {{ translate('This helps make sure you restock before running out.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
