<div class="inline-page-menu my-4">
    <ul class="nav nav-pills nav--tab gap-3">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/all-product') ? 'active' : '' }}" href="{{ route(reportModuleRoute('all-product')) }}">
                {{ translate('All Products') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/stock/product-stock') ? 'active' : '' }}" href="{{ route(reportModuleRoute('stock.product-stock')) }}">
                {{ translate('Product Stock') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/stock/product-in-wishlist') ? 'active' : '' }}" href="{{ route(reportModuleRoute('stock.product-in-wishlist')) }}">
                {{ translate('Wish Listed Products') }}
            </a>
        </li>
    </ul>
</div>
