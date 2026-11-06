@php use App\Utils\Helpers; @endphp

@if(Helpers::module_permission_check('retail.catalog'))
    <span id="get-product-stock-view" data-stock-limit-page="{{ route('admin.retail.products.stock-limit-list') }}"></span>
    <span id="get-stock-limit-status" data-action="{{ route('admin.retail.products.stock-limit-status', ['type' => 'in_house']) }}"></span>
@endif

@if(Helpers::module_permission_check('retail.catalog') || Helpers::module_permission_check('retail.marketing'))
    <span id="get-search-product-route" data-action="{{ route('admin.retail.products.search-product') }}"></span>
    <span id="get-search-all-type-product-route" data-action="{{ route('admin.retail.products.search-all-type-product') }}"></span>
@endif

<span id="route-for-real-time-activities" data-route="{{ route('admin.retail.dashboard.real-time-activities') }}"></span>

@if(Helpers::module_permission_check('people.customers'))
    <span id="get-customer-list-route" data-action="{{ route('admin.people.customer.customer-list-search') }}"></span>
    <span id="get-customer-list-without-all-customer-route" data-action="{{ route('admin.people.customer.customer-list-without-all-customer') }}"></span>
@endif

@if(Helpers::module_permission_check('retail.marketing'))
    <span id="get-search-product-for-clearance-route" data-action="{{ route('admin.retail.deal.clearance-sale.search-product-for-clearance') }}"></span>
    <span id="get-search-vendor-for-clearance-route" data-action="{{ route('admin.retail.deal.clearance-sale.search-vendor-for-clearance') }}"></span>
    <span id="get-multiple-clearance-product-details-route" data-action="{{ route('admin.retail.deal.clearance-sale.multiple-clearance-product-details') }}"></span>
    <span id="get-clearance-vendor-add-route" data-action="{{ route('admin.retail.deal.clearance-sale.vendor-add') }}"></span>
@endif

<span id="get-multiple-product-details-route" data-action="{{ route('admin.retail.products.multiple-product-details') }}"></span>

@if(Helpers::module_permission_check('retail.orders'))
    <span id="get-orders-list-route" data-action="{{ route('admin.retail.orders.list', ['status' => 'all']) }}"></span>
@endif
