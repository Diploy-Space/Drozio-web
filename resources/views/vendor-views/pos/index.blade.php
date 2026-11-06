@extends('layouts.vendor.app')

@section('title', translate('POS'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/shared/css/pos.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor/pos.css') }}">
@endpush
@section('content')
    @php
        $activeFilters = collect(['filter_sort_by', 'filter_product_types', 'product_status', 'filter_shop_ids', 'filter_brand_ids', 'filter_category_ids'])
            ->filter(fn ($key) => !empty(request($key)))
            ->count();
    @endphp

    <div class="content container-fluid pos-page">
        <div class="row">
            <div class="col-lg-7 pos-col-catalog mb-4 mb-lg-0">
                <div class="card card-body pos-panel pos-catalog-card">
                    <div class="pos-toolbar">
                        <form class="pos-search-form" action="{{ route('vendor.retail.pos.index') }}" method="get">
                            @if(request()->query('product_type') && request()->query('product_type') == 'digital')
                                <input type="hidden" name="product_type" value="digital">
                            @endif
                            <div class="pos-search search-with-icon">
                                <i class="fi fi-rr-search pos-search-icon"></i>
                                <input id="search" autocomplete="off" type="text"
                                       value="{{ $searchValue }}"
                                       name="searchValue" class="form-control search-bar-input"
                                       placeholder="{{ translate('Search by name or sku') }}"
                                       aria-label="{{ translate('Search by name or sku') }}">
                                <div class="pos-search-actions">
                                    <a href="{{ route('vendor.retail.pos.index') }}" class="search-cross d-none"
                                       aria-label="{{ translate('Clear') }}">
                                        <i class="fi fi-rr-cross fs-10 d-flex"></i>
                                    </a>
                                    <button type="submit" class="pos-search-submit" aria-label="{{ translate('Search') }}">
                                        <i class="fi fi-rr-search d-flex"></i>
                                    </button>
                                </div>
                                <div class="card pos-search-card position-absolute z-9 w-100">
                                    <div id="pos-search-box" class="card-body search-result-box d-none p-0"></div>
                                </div>
                            </div>
                        </form>

                        <button type="button"
                                class="pos-icon-btn {{ $activeFilters ? 'is-active' : '' }}"
                                data-toggle="offcanvas" data-target="#offcanvasPosFilter">
                            <i class="fi fi-sr-settings-sliders d-flex"></i>
                            {{ translate('Filter') }}
                            @if($activeFilters)
                                <span class="pos-filter-count">{{ $activeFilters }}</span>
                            @endif
                        </button>

                        <button type="button" id="short-cut" class="pos-icon-btn pos-icon-btn--square"
                                data-toggle="modal" data-target="#short-cut-keys"
                                title="{{ translate('Short cut keys') }}" aria-label="{{ translate('Short cut keys') }}">
                            <i class="fi fi-rr-keyboard d-flex fs-16"></i>
                        </button>
                    </div>

                    <div class="pos-toolbar pos-toolbar--tabs">
                        <div class="pos-seg">
                            <a class="{{ $productType != 'digital' ? 'active' : '' }}"
                               href="{{ url()->current() }}?product_type=physical">
                                {{ translate('Physical Products') }}
                            </a>
                            <a class="{{ $productType == 'digital' ? 'active' : '' }}"
                               href="{{ url()->current() }}?product_type=digital">
                                {{ translate('Digital Products') }}
                            </a>
                        </div>
                        <div class="ms-auto fs-12 text-muted pos-num">
                            {{ $products->total() }} {{ $products->total() > 1 ? translate('Products') : translate('Product') }}
                        </div>
                    </div>

                    <div class="pos-catalog" id="items">
                        @if(count($products) > 0)
                            <div class="pos-item-wrap" id="pos-product-scroll"
                                 data-url="{{ route('vendor.retail.pos.product-list') }}"
                                 data-next-page="{{ $products->currentPage() + 1 }}"
                                 data-has-more="{{ $products->hasMorePages() ? 1 : 0 }}">
                                @include('vendor-views.pos.partials._product-list')
                                <div class="pos-scroll-sentinel">
                                    <span class="pos-spinner" role="status"
                                          aria-label="{{ translate('Loading') }}"></span>
                                </div>
                            </div>
                        @else
                            <div class="pos-empty">
                                <img src="{{ asset('assets/back-end/img/empty-product.png') }}" width="64" alt="">
                                <div class="pos-empty__title">{{ translate('No Product Found') }}</div>
                                <div class="pos-empty__text">{{ translate('Try a different search or adjust the filters') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5 pos-col-billing">
                <div class="card pos-panel billing-section-wrap">
                    <div class="pos-billing-header">
                        <h3 class="pos-billing-title">{{ translate('Billing Section') }}</h3>
                        <button type="button" class="pos-hold-link action-view-all-hold-orders"
                                data-toggle="tooltip"
                                title="{{ translate('Please resume the order from here') }}">
                            <i class="fi fi-rr-pause d-flex fs-12"></i>
                            {{ translate('View All Hold Orders') }}
                            <span class="total_hold_orders pos-num {{ $totalHoldOrder > 0 ? '' : 'd-none' }}">{{ $totalHoldOrder }}</span>
                        </button>
                    </div>

                    <div class="card-body pos-billing-body">
                        <div class="pos-billing-inner">
                            @php
                                $userId = 0;
                                if (Illuminate\Support\Str::contains(session('current_user'), 'saved-customer')) {
                                    $userId = explode('-', session('current_user'))[2];
                                }
                            @endphp

                            <div class="position-relative custom_dropdown_wrapper">
                                <div class="form-control custom_dropdown_toggle d-flex gap-2 justify-content-between align-items-center overflow-wrap-anywhere">
                                    <span class="pos-customer-lead">
                                        <span class="pos-avatar"><i class="fi fi-rr-user d-flex fs-12"></i></span>
                                        <span class="selected_text text-truncate">
                                            {{ $userId == 0 ? translate('Walk-In-Customer') : ($customers->firstWhere('id', $userId)->f_name ?? '') . ' ' . ($customers->firstWhere('id', $userId)->l_name ?? '') }}
                                        </span>
                                    </span>
                                    <i class="fi fi-sr-angle-down fs-12"></i>
                                </div>
                                <div class="custom_dropdown_menu">
                                    <div class="card card-body p-3 shadow-popup">
                                        <div class="d-flex flex-column gap-2">
                                            <div class="position-relative d-flex form-control align-items-center gap-2 bg-section">
                                                <i class="fi fi-rr-search fs-16"></i>
                                                <input type="search"
                                                       class="w-100 border-0 outline-none bg-transparent custom_dropdown_search"
                                                       placeholder="{{ translate('Search name or phone number') }}">
                                            </div>
                                            <a class="text-primary fw-medium text-underline text-capitalize text-end"
                                               id="add_new_customer"
                                               data-toggle="offcanvas"
                                               data-target="#offcanvasAddNewCustomer"
                                               href="javascript:"
                                               role="button"
                                               aria-controls="offcanvasAddNewCustomer"
                                               title="{{ translate('Add New Customer') }}">
                                                + {{ translate('Add New Customer') }}
                                            </a>

                                            <div class="custom_dropdown_list">
                                                <div class="custom_dropdown_item action-customer-change fs-12 fw-medium"
                                                     data-id="0">
                                                    {{ translate('Walk-In-Customer') }}
                                                </div>
                                                @foreach ($customers as $customer)
                                                    <div class="custom_dropdown_item action-customer-change fs-12 fw-medium"
                                                         data-id="{{ $customer->id }}">
                                                        {{ $customer->f_name }} {{ $customer->l_name }}
                                                        ({{ config('app.mode') != 'demo' ? $customer->phone : '+88017'.rand(111, 999).'XXXXX' }})
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="bg-section p-5 rounded custom_dropdown_empty">
                                                <div class="d-flex justify-content-center align-items-center">{{ translate('No Customer Found') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ $userId }}">
                            @include('vendor-views.pos.partials._cart-summary')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pos-quickview" id="quick-view" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="quick-view-modal"></div>
        </div>
    </div>
    <button class="d-none" id="hold-orders-modal-btn" type="button" data-toggle="modal"
            data-target="#hold-orders-modal"></button>

    @if($order)
        @include('vendor-views.pos.partials.offcanvas._print-invoice-offcanvas')
    @endif

    @include('vendor-views.pos.partials.modals._add-customer')
    @include('vendor-views.pos.partials.modals._hold-orders-modal')
    @include('vendor-views.pos.partials.modals._add-coupon-discount')
    @include('vendor-views.pos.partials.modals._add-discount')
    @include('vendor-views.pos.partials.modals._short-cut-keys')

    @include('vendor-views.pos.partials.offcanvas._filter-offcanvas')
    @include('vendor-views.pos.partials.offcanvas._add-new-customer-offcanvas')

    <span id="route-vendor-pos-new-cart-id" data-url="{{ route('vendor.retail.pos.new-cart-id') }}"></span>
    <span id="route-vendor-pos-clear-cart-ids" data-url="{{ route('vendor.retail.pos.clear-cart-ids') }}"></span>
    <span id="route-vendor-pos-view-hold-orders" data-url="{{ route('vendor.retail.pos.view-hold-orders') }}"></span>
    <span id="route-vendor-products-search-product" data-url="{{ route('vendor.retail.pos.search-product') }}"></span>
    <span id="route-vendor-pos-change-customer" data-url="{{ route('vendor.retail.pos.change-customer') }}"></span>
    <span id="route-vendor-pos-update-discount" data-url="{{ route('vendor.retail.pos.update-discount') }}"></span>
    <span id="route-vendor-pos-coupon-discount" data-url="{{ route('vendor.retail.pos.coupon-discount') }}"></span>
    <span id="route-vendor-pos-cancel-order" data-url="{{ route('vendor.retail.pos.cancel-order') }}"></span>
    <span id="route-vendor-pos-quick-view" data-url="{{ route('vendor.retail.pos.quick-view') }}"></span>
    <span id="route-vendor-pos-add-to-cart" data-url="{{ route('vendor.retail.pos.add-to-cart') }}"></span>
    <span id="route-vendor-pos-remove-cart" data-url="{{ route('vendor.retail.pos.cart-remove') }}"></span>
    <span id="route-vendor-pos-empty-cart" data-url="{{ route('vendor.retail.pos.cart-empty') }}"></span>
    <span id="route-vendor-pos-update-quantity" data-url="{{ route('vendor.retail.pos.quantity-update') }}"></span>
    <span id="route-vendor-pos-get-variant-price" data-url="{{ route('vendor.retail.pos.get-variant-price') }}"></span>
    <span id="route-vendor-pos-change-cart-editable" data-url="{{ route('vendor.retail.pos.change-cart').'/?cart_id=:value' }}"></span>

    <span id="message-cart-word" data-text="{{ translate('Cart') }}"></span>
    <span id="message-stock-out" data-text="{{ translate('Stock out') }}"></span>
    <span id="message-stock-id" data-text="{{ translate('In Stock') }}"></span>
    <span id="message-add-to-cart" data-text="{{ translate('Add To Cart') }}"></span>
    <span id="message-cart-updated" data-text="{{ translate('Cart updated') }}"></span>
    <span id="message-update-to-cart" data-text="{{ translate('Update Cart') }}"></span>
    <span id="message-cart-is-empty" data-text="{{ translate('Your cart is empty.') }}"></span>
    <span id="message-coupon-is-invalid" data-text="{{ translate('Coupon is invalid') }}"></span>
    <span id="message-product-quantity-updated" data-text="{{ translate('Product quantity updated') }}"></span>
    <span id="message-coupon-added-successfully" data-text="{{ translate(':item added successfully', ['item' => translate('Coupon')]) }}"></span>
    <span id="message-sorry-stock-limit-exceeded" data-text="{{ translate('Sorry stock limit exceeded') }}"></span>
    <span id="message-please-choose-all-the-options" data-text="{{ translate('Please choose all the options') }}"></span>
    <span id="message-item-has-been-removed-from-cart" data-text="{{ translate('Item has been removed from cart') }}"></span>
    <span id="message-you-want-to-remove-all-items-from-cart" data-text="{{ translate('You want to remove all items from cart') }}"></span>
    <span id="message-you-want-to-create-new-order" data-text="{{ translate('Want to create new order for another customer') }}"></span>
    <span id="message-product-quantity-is-not-enough" data-text="{{ translate('Product quantity is not enough') }}"></span>
    <span id="message-sorry-product-is-out-of-stock" data-text="{{ translate('Sorry product is out of stock') }}"></span>
    <span id="message-item-has-been-added-in-your-cart" data-text="{{ translate('Item has been added to your cart') }}"></span>
    <span id="message-extra-discount-added-successfully" data-text="{{ translate(':item added successfully', ['item' => translate('Extra discount')]) }}"></span>
    <span id="message-amount-can-not-be-negative-or-zero" data-text="{{ translate('Amount cannot be negative or zero') }}"></span>
    <span id="message-sorry-the-minimum-value-was-reached" data-text="{{ translate('Sorry the minimum value was reached') }}"></span>
    <span id="message-this-discount-is-not-applied-for-this-amount" data-text="{{ translate('This discount is not applied for this amount') }}"></span>
    <span id="message-product-quantity-cannot-be-zero-in-cart" data-text="{{ translate('Product quantity cannot be zero or less than zero in cart') }}"></span>
    <span id="message-enter-valid-amount" data-text="{{ translate('Please enter a valid amount') }}"></span>
    <span id="message-less-than-total-amount" data-text="{{ translate('Paid amount is less than total amount') }}"></span>
    <span id="message-product-quantity-cannot-be-less-then-one" data-text="{{ translate('Product quantity cannot be less than one') }}"></span>

@endsection

@push('script_2')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/libs/printThis/printThis.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/pos-script.js') }}"></script>
    <script>
        "use strict";
        $(document).ready(function () {
            updateProductCounts({productCounts: @json($productCounts ?? [])});
            @if($order)
            $('#print-invoice').addClass('active');
            @endif
        });
    </script>
@endpush
