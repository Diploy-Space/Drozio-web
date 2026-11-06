<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('vendor/report/*/all-product') ? 'active' : '' }}"><a href="{{ route(reportModuleRoute('all-product')) }}">{{ translate('All Products') }}</a></li>
        <li class="{{ Request::is('vendor/report/*/stock-product-report') ? 'active' : '' }}"><a href="{{ route(reportModuleRoute('stock-product-report')) }}">{{ translate('Products Stock') }}</a></li>
    </ul>
</div>
