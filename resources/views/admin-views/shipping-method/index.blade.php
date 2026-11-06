@extends('layouts.admin.app')

@section('title', translate('Shipping Method'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/shipping-method.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route($routeNames['responsibility']) }}"
              method="post">
            @csrf
            @php($shippingMethod = $responsibilityValue)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="text-capitalize">{{ translate('Shipping Responsibility') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Set up the shipping responsibility and associated charges for order delivery.') }}
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="form-group mb-20">
                            <div class="bg-white p-3 rounded">
                                <div class="row g-4">
                                    <div class="col-xl-6 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input type="hidden" name="id" value="{{ isset($shippingMethod->id) ? $shippingMethod->id : null }}">
                                            <input
                                                class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                type="radio" value="inhouse_shipping" name="shipping_method"
                                                id="inhouse-shipping"
                                                {{ $shippingMethod == 'inhouse_shipping' ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/inhouse-shipping.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/inhouse-shipping.png') }}"
                                                data-on-title="{{ translate('Change the shipping responsibility to Inhouse') . '?' }}"
                                                data-off-title="{{ translate('Change the shipping responsibility to Inhouse') . '?' }}"
                                                data-on-message="<p>{{ translate('Admin will handle the shipping responsibilities when you choose inhouse shipping method') . '.' }}</p>"
                                                data-off-message="<p>{{ translate('Admin will handle the shipping responsibilities when you choose inhouse shipping method') . '.' }}</p>">
                                            <div class="flex-grow-1">
                                                <label for="inhouse-shipping"
                                                       class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                    {{ translate('Inhouse shipping') }}
                                                </label>
                                                <p class="fs-12 mb-3">
                                                    {{ translate('Admin will handle the shipping responsibilities when you choose inhouse shipping method') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input
                                                class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                type="radio" value="sellerwise_shipping" name="shipping_method"
                                                id="seller-wise-shipping"
                                                {{ $shippingMethod == 'sellerwise_shipping' ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-wise-shipping.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/vendor-wise-shipping.png') }}"
                                                data-on-title="{{ translate('Change the shipping responsibility to Vendor Wise') . '?' }}"
                                                data-off-title="{{ translate('Change the shipping responsibility to Vendor Wise') . '?' }}"
                                                data-on-message="<p>{{ translate('The vendor handles shipping for vendor-wise orders.') . '.' }}</p>"
                                                data-off-message="<p>{{ translate('The vendor handles shipping for vendor-wise orders.') . '.' }}</p>">
                                            <div class="flex-grow-1">
                                                <label for="seller-wise-shipping"
                                                       class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                    {{ translate('Vendor wise shipping') }}
                                                </label>
                                                <p class="fs-12 mb-3">
                                                    {{ translate('Vendors handle shipping when you choose vendor wise shipping.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end flex-wrap gap-3">
                            <button type="reset" class="btn btn-secondary px-4 w-120">
                                {{ translate('Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary px-4 w-120">
                                <i class="fi fi-sr-disk"></i>
                                {{ translate('Submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($isCourierAddonPublished)
            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route($routeNames['third_party_mode']) }}"
                          method="post" id="third-party-delivery-mode-form">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                            <div class="flex-1">
                                <h3 class="text-capitalize">{{ translate('Third Party Delivery Service') }}</h3>
                                <p class="mb-0 fs-12">
                                    {{ translate('Ship orders through your integrated third-party delivery partners.') }}
                                    {{ translate('Your shipping methods stay available; when off, every order uses the default.') }}
                                </p>
                            </div>
                            <div class="p-3 bg-section rounded d-flex align-items-center gap-3 flex-shrink-0 ms-auto">
                                <label class="form-label fw-semibold mb-0 text-capitalize" for="third-party-delivery-mode">
                                    {{ translate('Third Party Delivery Mode') }}
                                </label>
                                <label class="switcher" for="third-party-delivery-mode">
                                    <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                           name="status" id="third-party-delivery-mode"
                                           {{ $isThirdPartyDeliveryEnabled ? 'checked' : '' }}
                                           data-modal-type="input-change-form"
                                           data-modal-form="#third-party-delivery-mode-form"
                                           data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png') }}"
                                           data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png') }}"
                                           data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Third Party Delivery Mode')]) . '?' }}"
                                           data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Third Party Delivery Mode')]) . '?' }}"
                                           data-on-message="<p>{{ translate('Makes delivery partners selectable on the order details page.') . '.' }}</p>"
                                           data-off-message="<p>{{ translate('Turning this off hides delivery partners on the order details page.') . '.' }}</p>"
                                           data-on-button-text="{{ translate('Turn On') }}"
                                           data-off-button-text="{{ translate('Turn Off') }}">
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if ($isThirdPartyDeliveryEnabled)
                <div class="card mb-3">
                    <div class="card-body">
                        <form action="{{ route($routeNames['vendor_partner_setup']) }}"
                              method="post" id="vendor-delivery-partner-setup-form">
                            @csrf
                            <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                                <div class="flex-1">
                                    <h3 class="text-capitalize">{{ translate('Vendor Delivery Partner Setup') }}</h3>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Vendors connect their own delivery partner accounts and credentials.') }}
                                    </p>
                                </div>
                                <div class="p-3 bg-section rounded d-flex align-items-center gap-3 flex-shrink-0 ms-auto">
                                    <label class="form-label fw-semibold mb-0 text-capitalize" for="vendor-delivery-partner-setup">
                                        {{ translate('Vendor Delivery Partner Setup') }}
                                    </label>
                                    <label class="switcher" for="vendor-delivery-partner-setup">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1"
                                               name="status" id="vendor-delivery-partner-setup"
                                               {{ $isVendorDeliveryPartnerSetupEnabled ? 'checked' : '' }}
                                               data-modal-type="input-change-form"
                                               data-modal-form="#vendor-delivery-partner-setup-form"
                                               data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png') }}"
                                               data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/third-party-delivery.png') }}"
                                               data-on-title="{{ translate('Want to let vendors set up their own delivery partners') . '?' }}"
                                               data-off-title="{{ translate('Want to stop vendors setting up their own delivery partners') . '?' }}"
                                               data-on-message="<p>{{ translate('Each vendor gets an integration page and their own carrier accounts.') . '.' }}</p>"
                                               data-off-message="<p>{{ translate('Hidden from vendors. Their saved credentials are kept but unused.') . '.' }}</p>"
                                               data-on-button-text="{{ translate('Turn On') }}"
                                               data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-4">
                    <h3 class="text-capitalize">{{ translate('Shipping Methods') }}</h3>
                    <p class="mb-0 fs-12">
                        {{ translate('Setup how you want to calculate shipping charge for order delivery') }}
                    </p>
                </div>
                <div class="p-12 p-sm-20 bg-section rounded">
                    <form action="{{ route($routeNames['type']) }}" method="post" id="shipping-type-form">
                        @csrf
                        <div class="form-group">
                            <div class="bg-white p-3 rounded">
                                @php($shippingType = isset($adminShipping) ? $adminShipping['shipping_type'] : 'order_wise')
                                <div class="row g-4">
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input
                                                class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                type="radio" value="order_wise" name="shippingType"
                                                id="order_wise" {{ $shippingType == 'order_wise' ? 'checked' : '' }}

                                                data-modal-type="input-change-form"
                                                data-modal-form="#shipping-type-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Order wise shipping method')]) . '?' }}"
                                                data-off-title="{{ translate('Want to Turn ON :item', ['item' => translate('Order wise shipping method')]) . '?' }}"
                                                data-on-message="<p>{{ translate('Makes this shipping option selectable at checkout.') . '.' }}</p>"
                                                data-off-message="<p>{{ translate('Turning this off hides the shipping option at checkout.') . '.' }}</p>">
                                            <div class="flex-grow-1">
                                                <label for="order_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                    {{ translate('Order wise') }}
                                                </label>
                                                <p class="fs-12 mb-3">
                                                    {{ translate('Set the shipping cost by order amount and delivery time.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input
                                                class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                type="radio" value="category_wise" name="shippingType"
                                                id="category_wise" {{ $shippingType == 'category_wise' ? 'checked' : '' }}

                                                data-modal-type="input-change-form"
                                                data-modal-form="#shipping-type-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-on-title="{{ translate('Change Shipping Method to Category Wise') . '?' }}"
                                                data-off-title="{{ translate('Change Shipping Method to Category Wise') . '?' }}"
                                                data-on-message="<p>{{ translate('Category-wise shipping needs a shipping cost set on every category.') . '.' }}</p>"
                                                data-off-message="<p>{{ translate('Category-wise shipping needs a shipping cost set on every category.') . '.' }}</p>">
                                            <div class="flex-grow-1">
                                                <label for="category_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                    {{ translate('Category wise') }}
                                                </label>
                                                <p class="fs-12 mb-3">
                                                    {{ translate('Set a shipping cost per category so the right amount charges.') }}
                                                </p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input
                                                class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                type="radio" value="product_wise" name="shippingType"
                                                id="product_wise" {{ $shippingType == 'product_wise' ? 'checked' : '' }}

                                                data-modal-type="input-change-form"
                                                data-modal-form="#shipping-type-form"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-wise-shipping.png') }}"
                                                data-on-title="{{ translate('Change Shipping Method to Product Wise') . '?' }}"
                                                data-off-title="{{ translate('Change Shipping Method to Product Wise') . '?' }}"
                                                data-on-message="<p>{{ translate('Product-wise shipping needs a shipping cost set on every product.') . '.' }}</p>"
                                                data-off-message="<p>{{ translate('Product-wise shipping needs a shipping cost set on every product.') . '.' }}</p>">
                                            <div class="flex-grow-1">
                                                <label for="product_wise" class="form-label text-dark fw-semibold mb-1 text-capitalize">
                                                    {{ translate('Product wise') }}
                                                </label>
                                                <p class="fs-12 mb-3">
                                                    {{ translate('Set a shipping cost for each product to enable individual fees.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-column gap-20">
                <div id="order_wise_shipping" @style(['display: none' => $shippingType !== 'order_wise'])>
                    <div class="d-flex flex-column gap-20">
                        <div class="card card-sm shadow-2">
                            <div class="card-body">
                                <div class="mb-4">
                                    <h3 class="text-capitalize">
                                        {{ translate('Add order wise shipping method') }}
                                    </h3>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Set up shipping fee calculation based on order and shipping time.') }}
                                    </p>
                                </div>
                                <form action="{{route($routeNames['store'])}}" method="post" class="form-advance-validation  form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                                    @csrf
                                    <div class="px-20 py-4 bg-section rounded mb-20">
                                        <div class="row g-4">
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize" for="">
                                                        {{ translate('Title') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                              data-bs-placement="right" data-bs-title="{{ translate('Enter the title that will be shown for this shipping method') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                    </label>
                                                    <input type="text" name="title" class="form-control" maxlength="100"
                                                           placeholder="{{ translate('Enter the title that will be shown for this shipping method') }}"    data-required-msg="{{ translate(':field is required', ['field' => translate('Title')]) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize"
                                                           for="">{{ translate('Shipping Duration') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                              data-bs-placement="right"
                                                              data-bs-title="{{ translate('Set up the timeframe for the shipping method') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                    </label>
                                                    <input type="text" class="form-control" name="duration" id="duration"
                                                           maxlength="20" placeholder="{{ translate('Ex').': 4-6 Days' }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Shipping Duration')]) }}" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-group mb-0">
                                                    <label class="form-label text-capitalize" for="">
                                                        {{ translate('Shipping Cost') }}
                                                        <span class="text-danger">*</span>
                                                        <span class="tooltip-icon" data-bs-toggle="tooltip"
                                                              data-bs-placement="right"
                                                              data-bs-title="{{ translate('Define the cost based on the time frame') }}">
                                                            <i class="fi fi-sr-info"></i>
                                                        </span>
                                                    </label>
                                                    <input type="number" min="0" step="0.01" max="1000000"
                                                           name="cost" class="form-control"
                                                           placeholder="{{ translate('Define the cost based on the time frame') }}:"   data-required-msg="{{ translate(':field is required', ['field' => translate('Shipping Cost')]) }}" required="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end flex-wrap gap-3">
                                        <button type="reset" class="btn btn-secondary px-4 w-120">
                                            {{ translate('Reset') }}
                                        </button>
                                        <button type="submit" class="btn btn-primary px-4 w-120">
                                            <i class="fi fi-sr-disk"></i>
                                            {{ translate('Submit') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card shadow-2">
                            <div class="card-body">
                                <div class="d-flex flex-column gap-20">
                                    <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                                        <h4 class="text-capitalize">{{ translate('List of order wise shipping method') }}
                                            <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $shippingMethods->count() }}</span>
                                        </h4>
                                        <form action="{{route($routeNames['self'])}}"
                                              method="get" class="flex-grow-1 max-w-300 min-w-100-mobile">
                                            @csrf
                                            <div class="input-group">
                                                <input type="search" name="order_search" class="form-control"
                                                       placeholder="{{ translate('Search by title') }}"
                                                       value="{{ request('order_search') }}">
                                                <div class="input-group-append search-submit">
                                                    <button type="submit">
                                                        <i class="fi fi-rr-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-borderless">
                                            <thead class="text-capitalize">
                                            <tr>
                                                <th>{{ translate('Serial') }}</th>
                                                <th>{{ translate('Title') }}</th>
                                                <th>{{ translate('Shipping Duration') }}</th>
                                                <th>
                                                    {{ translate('Cost') }} ({{ getCurrencySymbol(type: 'default') }})
                                                </th>
                                                <th class="text-center">{{ translate('Status') }}</th>
                                                <th class="text-center">{{ translate('Action') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($shippingMethods as $key => $method)
                                                <tr>
                                                    <th>{{ $key + 1 }}</th>
                                                    <td class="max-w-370 text-wrap">{{ $method['title'] }}</td>
                                                    <td>
                                                        {{ $method['duration'] }}
                                                    </td>
                                                    <td>
                                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $method['cost']), currencyCode: getCurrencyCode(type: 'default')) }}
                                                    </td>
                                                    <td>
                                                        <form
                                                            action="{{ route($routeNames['status']) }}"
                                                            method="post" id="shipping-methods{{ $method['id'] }}-form" class="no-reload-form">
                                                            @csrf
                                                            <input type="hidden" name="id"
                                                                   value="{{ $method['id'] }}">
                                                            <label class="switcher mx-auto" for="shipping-methods{{ $method['id'] }}">
                                                                <input
                                                                    class="switcher_input custom-modal-plugin"
                                                                    type="checkbox" value="1" name="status"
                                                                    id="shipping-methods{{ $method['id'] }}"
                                                                    {{ $method->status == 1 ? 'checked' : '' }}
                                                                    data-modal-type="input-change-form"
                                                                    data-modal-form="#shipping-methods{{ $method['id'] }}-form"
                                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-status-on.png') }}"
                                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/category-status-off.png') }}"
                                                                    data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Shipping Method')]) . '?' }}"
                                                                    data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Shipping Method')]) . '?' }}"
                                                                    data-on-message="<p>{{ translate('Shows this shipping method at checkout in the app and website.') }}</p>"
                                                                    data-off-message="<p>{{ translate('Hides this shipping method from checkout in the app and website.') }}</p>"
                                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                                <span class="switcher_control"></span>
                                                            </label>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center gap-3">
                                                            <a class="btn btn-outline-info icon-btn edit"
                                                               title="{{ translate('Edit') }}"
                                                               href="{{ route($routeNames['update'], [$method['id']]) }}">
                                                                <i class="fi fi-sr-pencil"></i>
                                                            </a>
                                                            <a title="{{ translate('Delete') }}"
                                                               class="btn btn-outline-danger icon-btn delete-data"
                                                               data-action="{{ route($routeNames['delete']) }}"
                                                               data-id="{{ $method['id'] }}">
                                                                <i class="fi fi-rr-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="table-responsive">
                                        <div class="d-flex justify-content-lg-end">
                                            {{ $shippingMethods->links() }}
                                        </div>
                                    </div>
                                    @if ($shippingMethods->count() == 0)
                                        @include('layouts.admin.partials._empty-state', [
                                            'text' => 'No shipping method found',
                                            'image' => 'default',
                                        ])
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="update_category_shipping_cost" @style(['display: none' => $shippingType !== 'category_wise'])>
                    <div class="card shadow-2 category-cost-card">
                        <div class="card-body">
                            <div class="d-flex flex-column gap-20">
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                    <div class="min-w-0">
                                        <h4 class="mb-1 d-flex align-items-center gap-2">
                                            {{ translate('Category wise shipping cost') }}
                                            <span class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $allCategoryShippingCost->total() }}</span>
                                        </h4>
                                        <p class="mb-0 fs-12 text-muted">
                                            {{ translate('Changes to a cost are saved as you type.') }}
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="input-group category-cost-bulk">
                                            <span class="input-group-text">{{ translate('Set all') }}</span>
                                            <span class="input-group-text">{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}</span>
                                            <input type="number" min="0" step="0.01" class="form-control"
                                                   id="category-cost-bulk-value" placeholder="{{ translate('Ex') }}: 50">
                                            <button class="btn btn-outline-primary" type="button" id="category-cost-bulk-apply">
                                                {{ translate('Apply') }}
                                            </button>
                                        </div>
                                        <form action="{{route($routeNames['self'])}}" method="get" class="category-cost-search">
                                            <div class="input-group">
                                                <input type="search" name="category_search" class="form-control"
                                                       placeholder="{{ translate('Search by category name') }}"
                                                       value="{{ request('category_search') }}">
                                                <div class="input-group-append search-submit">
                                                    <button type="submit">
                                                        <i class="fi fi-rr-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="table-responsive category-cost-scroll" id="category-cost-form"
                                     data-action="{{ route($routeNames['category_store']) }}">
                                    <table class="table table-hover table-borderless align-middle category-cost-table">
                                        <thead class="text-capitalize">
                                        <tr>
                                            <th>{{ translate('SL') }}</th>
                                            <th>{{ translate('Category Name') }}</th>
                                            <th>
                                                {{ translate('Shipping Cost') }} ({{ getCurrencySymbol(currencyCode: getCurrencyCode()) }})
                                            </th>
                                            <th class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <span>{{ translate('Multiply by quantity') }}</span>
                                                    <span class="trx-y-2" data-bs-toggle="tooltip" data-bs-placement="top"
                                                          data-bs-title="{{ translate('Charges the cost once per item instead of once per order.') }}">
                                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                                    </span>
                                                </div>
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($allCategoryShippingCost as $key => $item)
                                            <tr class="category-cost-row" data-category-id="{{ $item->category_id }}">
                                                <td>{{ $allCategoryShippingCost->firstItem() + $key }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img class="rounded-circle aspect-1 flex-shrink-0" width="32" height="32"
                                                             src="{{ getStorageImages(path: $item->category?->icon_full_url, type: 'backend-category') }}"
                                                             alt="">
                                                        <div class="min-w-0">
                                                            <div class="category-cost-name" title="{{ $item->category?->name }}">
                                                                {{ $item->category?->name ?? translate('Category not found') }}
                                                            </div>
                                                            <div class="fs-12 text-body">{{ translate('ID') }} : #{{ $item->category_id }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group category-cost-input">
                                                        <span class="input-group-text">{{ getCurrencySymbol(currencyCode: getCurrencyCode()) }}</span>
                                                        <input type="number" class="form-control category-cost-value"
                                                               min="0" step="0.01"
                                                               placeholder="{{ translate('Ex') }}: 50"
                                                               value="{{ usdToDefaultCurrency(amount: $item->cost) }}">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center align-items-center">
                                                        <input class="form-check-input checkbox--input category-cost-multiply"
                                                               type="checkbox" value="1"
                                                            {{ $item->multiply_qty == 1 ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="table-responsive">
                                    <div class="d-flex justify-content-lg-end">
                                        {{ $allCategoryShippingCost->links() }}
                                    </div>
                                </div>

                                @if ($allCategoryShippingCost->count() == 0)
                                    @include('layouts.admin.partials._empty-state', [
                                        'text' => 'No category found',
                                        'image' => 'default',
                                    ])
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-section rounded-10 p-20 text-center" id="product_wise_note"
                     @style(['display: none' => in_array($shippingType, ['order_wise', 'category_wise'], true)])>
                    <img width="60" class="rounded-circle aspect-1 mb-3"
                         src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-wise-shipping.png') }}"
                         alt="">
                    <h3 class="mb-2">{{ translate('You are currently using the product wise shipping method') }}</h3>
                    <p>{{ translate('Update the delivery cost for each product on the product page.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._shipping-method")

    <span id="order-wise-shipping-method-delete-content" data-sure="{{ translate('Are you sure you want to delete this :item', ['item' => translate('Order wise shipping method')]) }}?" data-text="{{ translate('Once deleted') }}, {{ translate('Removes this shipping option from checkout.') }}" data-confirm="{{ translate('Yes, Delete it') }}" data-cancel="Cancel" aria-hidden="true"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/shipping-method.js') }}"></script>
@endpush
