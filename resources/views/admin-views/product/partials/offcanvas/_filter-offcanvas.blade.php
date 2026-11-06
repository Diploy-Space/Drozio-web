@php
    $offcanvasRouteName ??= Request::is('admin/retail/products/updated-product-list/*')
        ? 'admin.retail.products.updated-product-list'
        : 'admin.retail.products.list';
    $offcanvasRouteParams ??= ['type' => request('type')];
    $offcanvasScopeParams = array_filter([
        'request_status' => request('request_status'),
        'searchValue' => request('searchValue'),
        'seller_id' => request('seller_id'),
    ], fn ($value) => $value !== null && $value !== '');
    $offcanvasBaseParams = $offcanvasRouteParams + $offcanvasScopeParams;
    $filterSections ??= request('type') == 'vendor' && request('request_status', '') != 1
        ? ['sorting', 'product_type', 'brand', 'category']
        : ['sorting', 'product_type', 'product_status', 'brand', 'category'];
@endphp
<form action="{{ route($offcanvasRouteName, $offcanvasRouteParams) }}" method="GET">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProductFilter"
        aria-labelledby="offcanvasProductFilterLabel" style="--bs-offcanvas-width: 500px;">
        <div class="offcanvas-header bg-body">
            <h3 class="mb-0">{{ translate('Filter') }}</h3>
            <button type="button" class="btn btn-circle bg-white text-dark fs-10" style="--size: 1.5rem;" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fi fi-rr-cross"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            @foreach($offcanvasScopeParams as $offcanvasScopeKey => $offcanvasScopeValue)
                <input type="hidden" name="{{ $offcanvasScopeKey }}" value="{{ $offcanvasScopeValue }}">
            @endforeach

            @include("admin-views.partials._product-filters-sections", [
                'filterSection' => $filterSections,
                'productBrands' => $brands,
                'productCategories' => $categories
            ])
        </div>
        <div class="offcanvas-footer shadow-popup">
            <div class="d-flex justify-content-center gap-3 bg-white px-3 py-2">
                <a class="btn btn-secondary w-100" href="{{ route($offcanvasRouteName, $offcanvasBaseParams) }}">
                    {{ translate('Clear Filter') }}
                </a>
                <button type="submit" class="btn btn-primary w-100">
                    {{ translate('Apply') }}
                </button>
            </div>
        </div>
    </div>
</form>
