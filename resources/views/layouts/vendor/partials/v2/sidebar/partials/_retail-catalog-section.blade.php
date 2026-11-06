@php($v2VendorProductCounts = $sidebarVendorProductCounts ?? [])

@if (businessModuleSectionVisible('catalog'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'catalog' ? 'v2-is-on' : '' }}" data-section="catalog">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Catalog') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Products') }}</span></div>

            <a class="v2-nav-item {{ (Request::is('vendor/retail/products/list/all') || Request::is('vendor/retail/products/update*') || Request::is('vendor/retail/products/view*')) ? 'v2-is-active' : '' }}" data-item="v-products" href="{{ route('vendor.retail.products.list', ['type' => 'all']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product List') }}</span></span>
                <div class="v2-nav-right">
                    <span data-v2-tag="info">{{ $v2VendorProductCounts['all'] ?? 0 }}</span>
                    <button class="v2-pin-btn" type="button" data-pin="v-products" aria-label="Pin"></button>
                </div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/list/approved') ? 'v2-is-active' : '' }}" data-item="v-products-approved" href="{{ route('vendor.retail.products.list', ['type' => 'approved']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Approved Product List') }}</span></span>
                <div class="v2-nav-right">
                    <span data-v2-tag="success">{{ $v2VendorProductCounts['approved'] ?? 0 }}</span>
                    <button class="v2-pin-btn" type="button" data-pin="v-products-approved" aria-label="Pin"></button>
                </div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/list/new-request') ? 'v2-is-active' : '' }}" data-item="v-products-new" href="{{ route('vendor.retail.products.list', ['type' => 'new-request']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('New Product Request') }}</span></span>
                <div class="v2-nav-right">
                    <span data-v2-tag="danger">{{ $v2VendorProductCounts['new'] ?? 0 }}</span>
                    <button class="v2-pin-btn" type="button" data-pin="v-products-new" aria-label="Pin"></button>
                </div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/list/denied') ? 'v2-is-active' : '' }}" data-item="v-products-denied" href="{{ route('vendor.retail.products.list', ['type' => 'denied']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Denied Product Request') }}</span></span>
                <div class="v2-nav-right">
                    <span data-v2-tag="danger">{{ $v2VendorProductCounts['denied'] ?? 0 }}</span>
                    <button class="v2-pin-btn" type="button" data-pin="v-products-denied" aria-label="Pin"></button>
                </div>
            </a>
            @if (businessModuleItemVisible('catalog.add_product_retail'))
                <a class="v2-nav-item {{ (Request::is('vendor/retail/products/add') || (Request::is('vendor/retail/products/update/*') && request()->has('product-gallery'))) ? 'v2-is-active' : '' }}" data-item="v-products-add" href="{{ route('vendor.retail.products.add') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Product') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-products-add" aria-label="Pin"></button></div>
                </a>
            @endif
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/product-gallery') ? 'v2-is-active' : '' }}" data-item="v-product-gallery" href="{{ route('vendor.retail.products.product-gallery') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Gallery') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-product-gallery" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/stock-limit-list') ? 'v2-is-active' : '' }}" data-item="v-limited-stock" href="{{ route('vendor.retail.products.stock-limit-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Limited Stocks') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-limited-stock" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/bulk-import') ? 'v2-is-active' : '' }}" data-item="v-bulk-import" href="{{ route('vendor.retail.products.bulk-import') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Bulk Import') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-bulk-import" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/retail/products/request-restock-list') ? 'v2-is-active' : '' }}" data-item="v-restock" href="{{ route('vendor.retail.products.request-restock-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Request Restock List') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-restock" aria-label="Pin"></button></div>
            </a>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Product review') }}</span></div>
            <a class="v2-nav-item {{ Request::is('vendor/reviews/index*') ? 'v2-is-active' : '' }}" data-item="v-reviews" href="{{ route('vendor.reviews.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Reviews') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-reviews" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif
