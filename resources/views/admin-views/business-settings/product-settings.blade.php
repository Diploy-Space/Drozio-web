@extends('layouts.admin.app')

@section('title', translate('Product settings'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.product-settings.index') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3>{{ translate('General Setup') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Manage digital products and brands visibility preferences for the shops') }}
                        </p>
                    </div>
                    <div class="bg-section-sm">
                        <div class="row g-4">
                            <div class="col-xl-6 col-md-6">
                                <div
                                    class="border rounded p-3 user-select-none h-100 bg-white d-flex flex-column justify-content-between gap-2">
                                   <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                                {{ translate('Sell Digital Product') }}
                                            </div>
                                            <p class="mb-0 fs-12">
                                                {{ translate('If enabled vendors can sell digital products (Software, eBooks, Activation keys, JPG, PNG, etc.) in their shops.') }}
                                            </p>
                                        </div>
                                        <label class="switcher" for="digital-product">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="digital_product"
                                                id="digital-product"
                                                {{ $digitalProduct && $digitalProduct['value'] ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-product-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/digital-product-off.png') }}"
                                                data-on-title = "{{ translate('Want to Turn ON :item', ['item' => translate('Digital Product')]) . '?' }}"
                                                data-off-title = "{{ translate('Want to Turn OFF :item', ['item' => translate('Digital Product')]) . '?' }}"
                                                data-on-message = "<p>{{ translate('If enabled vendors can sell digital products in their shops') }}</p>"
                                                data-off-message = "<p>{{ translate('If disabled vendors cannot sell digital products in their shops') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                   </div>
                                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                        <i class="fi fi-sr-bulb text-info fs-16"></i>
                                       <span>
                                            {{ translate('To add a new digital product for your shop, visit') }}
                                            <a href="{{ route('admin.retail.products.add') }}" target="_blank" class="text-decoration-underline fw-semibold">{{ translate('Add New Product') }}</a>
                                            {{ translate('Page') }}.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="border rounded p-3 user-select-none h-100 bg-white d-flex flex-column justify-content-between gap-2">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <span>
                                            <div class="fw-medium text-dark fs-14 mb-1  text-capitalize">
                                                {{ translate('Show brand') }}
                                            </div>
                                            <p class="mb-0 fs-12">
                                                {{ translate('If enabled customers can see brands and they can browse and search for products from each brand inside any shop.') }}
                                            </p>
                                        </span>
                                        <label class="switcher" for="product-brand">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="product_brand"
                                                id="product-brand"
                                                {{ $brand && $brand['value'] ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-brand-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/product-brand-off.png') }}"
                                                data-on-title = "{{ translate('Do You Want to Show Brands') . '?' }}"
                                                data-off-title = "{{ translate('Do You Want to Hide Brands') . '?' }}"
                                                data-on-message = "<p>{{ translate('Customers can browse and search products by brand inside any shop.') }}</p>"
                                                data-off-message = "<p>{{ translate('Hides the brand section on the website and app.') }}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}">
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                            <i class="fi fi-sr-bulb text-info fs-16"></i>
                                            <span>
                                                {{ translate('You can manage all brands from this page') }}
                                                <a href="{{ route('admin.retail.brand.list') }}" target="_blank" class="text-decoration-underline fw-semibold">{{ translate('Brand List') }}</a>.
                                            </span>
                                        </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="text-capitalize">{{ translate('Need Product Approval') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Choose in which cases need approval for the vendor products') }}
                        </p>
                    </div>
                    @php($newProductApproval = getWebConfig('new_product_approval'))
                    @php($productWiseShippingCostApproval = getWebConfig('product_wise_shipping_cost_approval'))
                    <div class="bg-section-sm">
                        <div class="bg-white p-3 rounded border">
                            <div class="row g-4">
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-check d-flex gap-10">
                                        <input class="form-check-input checkbox--input checkbox--input-lg" type="checkbox" name="new_product_approval"
                                            id="new-product-approval" value="1" {{$newProductApproval == 1 ? 'checked': ''}}>
                                        <div class="flex-grow-1">
                                            <label for="new-product-approval"
                                                class="form-label text-dark fw-semibold mb-1 user-select-none">
                                                {{ translate('New Product') }}
                                            </label>
                                            <p class="fs-12 mb-0">
                                                {{ translate('If you checked') }}
                                                <span class="fw-semibold">{{ translate('New Product') }}</span>,
                                                {{ translate('Vendors need admin approval to add a new product to their shop') }}.
                                            </p>
                                            <div
                                                class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mt-2">
                                                <i class="fi fi-sr-bulb text-info fs-16"></i>
                                                <span>
                                                    {{ translate('You can see all the request from') }}
                                                    <a href="{{route('admin.retail.products.list',['vendor', 'request_status'=>'0'])}}" target="_blank" class="text-decoration-underline fw-semibold">{{ translate('New products request') }}</a>
                                                    {{ translate('Page') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-md-6">
                                    <div class="form-check d-flex gap-10">
                                        <input class="form-check-input checkbox--input checkbox--input-lg" name="product_wise_shipping_cost_approval" type="checkbox" id="product-wise-shipping" value="1" {{$productWiseShippingCostApproval == 1 ? 'checked' : '' }}>
                                        <div class="flex-grow-1">
                                            <label for="product-wise-shipping" class="form-label text-dark fw-semibold mb-1 user-select-none d-flex gap-1 align-items-center">
                                                {{ translate('Update Product Wise Shipping Cost') }}

                                                <span class="tooltip-icon d-flex" data-bs-toggle="tooltip"
                                                      data-bs-placement="right" data-bs-title="{{ translate('Vendors will need approval to update shipping costs for each product.') }}">
                                                            <i class="fi fi-sr-triangle-warning text-warning"></i>
                                                </span>
                                            </label>
                                            <p class="fs-12 mb-0">
                                                {{ translate('Require approval for vendor products with updated shipping costs.') }}
                                            </p>
                                            <div
                                                class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mt-2">
                                                <i class="fi fi-sr-bulb text-info fs-16"></i>
                                                <span>
                                                    {{ translate('Shipping cost updates require admin approval before going live') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="text-capitalize">{{ translate('Re-order Level') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Choose in which cases need approval for the vendor products') }}
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4 align-items-center">
                            <div class="col">
                                <h4 class=" text-capitalize">
                                    {{ translate('Re-order Level Amount') }}
                                </h4>
                                <p>{{ translate('Vendors see products needing restock once stock reaches this reorder level.') }}</p>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <input type="number" class="form-control" name="stock_limit"
                                           id="stock_limit"
                                           placeholder="{{ translate('Ex') . ': ' . '100' }}"
                                           value="{{ $stockLimit->value }}" min="0">
                                    <p class="mt-1 mb-0">
                                        {{ translate('Set the stock limit for the reorder level.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php($recommendationSetup = getWebConfig('product_recommendation_setup'))
            @php($recommendationSetup = is_array($recommendationSetup) ? $recommendationSetup : [])
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h3 class="text-capitalize">{{ translate('Personalized Product Recommendation') }}</h3>
                            <p class="mb-0 fs-12">
                                {{ translate('Homepage sections reorder automatically for each customer. No configuration needed.') }}
                            </p>
                        </div>
                        <label class="switcher {{ config('app.mode') == 'demo'? 'call-demo-alert' : '' }}" for="product-recommendation-status">
                            <input class="switcher_input" type="checkbox" value="1" name="product_recommendation_status"
                                   id="product-recommendation-status" {{ ($recommendationSetup['status'] ?? 0) ? 'checked' : '' }} {{ config('app.mode') == 'demo'? 'disabled' : '' }}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                        {{ translate('Reset') }}
                    </button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 text-capitalize {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @include("layouts.admin.partials.offcanvas._product-settings")
@endsection
