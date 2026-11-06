<form action="{{ route('vendor.retail.pos.index') }}" method="GET">
    <div class="offcanvas-sidebar pos-offcanvas" id="offcanvasPosFilter" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-overlay" data-dismiss="offcanvas"></div>

        <div class="offcanvas-content d-flex flex-column">
            <div class="offcanvas-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ translate('Filter') }}</h3>
                <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-dismiss="offcanvas" aria-label="Close">
                    <i class="fi fi-rr-cross"></i>
                </button>
            </div>
            <div class="offcanvas-body overflow-auto flex-grow-1">
                <input type="hidden" name="product_type" value="{{ request('product_type') }}">
                @include("vendor-views.partials._product-filters-sections", [
                    'filterSection' => ['sorting', 'brand', 'category'],
                    'productBrands' => $productBrands,
                    'productCategories' => $productCategories
                ])
            </div>
            <div class="offcanvas-footer shadow-popup">
                <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                    <a class="btn btn-secondary w-100" href="{{ route('vendor.retail.pos.index') }}">
                        {{ translate('Clear Filter') }}
                    </a>
                    <button type="submit" class="btn btn--primary w-100">
                        {{ translate('Apply') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
