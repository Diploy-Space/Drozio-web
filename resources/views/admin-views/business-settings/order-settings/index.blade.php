@extends('layouts.admin.app')

@section('title', translate('Order Settings'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.order-settings.update-order-settings') }}" method="post"
              enctype="multipart/form-data">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3>{{ translate('General Setup') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Complete the necessary setup for order process completion') }}
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-start gap-3 border rounded p-3 user-select-none h-100 bg-white">
                                    <span>
                                        <h5 class="fw-medium text-dark fs-14 mb-1">{{ translate('Order Delivery Verification') }}</h5>
                                        <p class="mb-0 fs-12">
                                            {{ translate('Customers get a code. The delivery man collects it on delivery.') }}
                                        </p>
                                    </span>

                                    <label class="switcher" for="order-verification-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="order_verification"
                                            id="order-verification-status"
                                            {{ $orderVerification ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/order-verifications-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/order-verifications-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Order Delivery Verification')]) }}"
                                            data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Order Delivery Verification')]) }}"
                                            data-on-message="<p>{{ translate('Delivery men verify deliveries with an OTP from the customer.') }}</p>"
                                            data-off-message="<p>{{ translate('If disabled deliverymen do not need to verify the order deliveries') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between flex-column gap-3 border rounded p-3 user-select-none h-100 bg-white">
                                   <div class="d-flex justify-content-between align-items-start gap-3">
                                        <span>
                                            <h5 class="fw-medium text-dark fs-14 mb-1">{{ translate('Vendor Can Set Minimum Order Amount') }}
                                            </h5>
                                            <p class="mb-0 fs-12">
                                                {{ translate("Set a certain amount below that customer can't place any order for others vendor and in-house vendor") }}
                                            </p>
                                        </span>

                                        <label class="switcher" for="minimum-order-amount-status">
                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox" value="1" name="minimum_order_amount_status"
                                                id="minimum-order-amount-status"
                                                {{ $minimumOrderAmountStatus ? 'checked' : '' }}
                                                data-modal-type="input-change"
                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/minimum-order-amount-on.png') }}"
                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/minimum-order-amount-off.png') }}"
                                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Vendor Can Set Minimum Order Amount')]) }}?"
                                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Vendor Can Set Minimum Order Amount')]) }}?"
                                                data-on-message="<p>{{ translate('Vendors can set a minimum order amount for their shop.') }}</p>"
                                                data-off-message="<p>{{ translate('Vendors cannot set a minimum order amount for their shop.') }}</p>">
                                            <span class="switcher_control"></span>
                                        </label>
                                   </div>
                                    <div
                                            class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-12 align-items-center">
                                            <i class="fi fi-sr-bulb text-info fs-16"></i>
                                            <span>{{ translate('For your shop setup from this page') }}
                                                <a href="{{ route('admin.settings.business-settings.inhouse-shop') }}"
                                                   target="_blank" class="text-decoration-underline fw-semibold">
                                                    {{ translate('In-house Shop') }}
                                                </a>.
                                            </span>
                                        </div>
                                </label>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-start gap-3 border rounded p-3 user-select-none h-100 bg-white">
                                    <span>
                                        <h5 class="fw-medium text-dark fs-14 mb-1">{{ translate('Show Billing Address') }}</h5>
                                        <p class="mb-0 fs-12">
                                            {{ translate('Customers can enter their billing address.') }}
                                        </p>
                                    </span>

                                    <label class="switcher" for="billing-input-by-customer-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="billing_input_by_customer"
                                            id="billing-input-by-customer-status"
                                            {{ $billingInputByCustomer ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/billing-address-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/billing-address-off.png') }}"
                                            data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Billing Address in Checkout')]) }}"
                                            data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Billing Address in Checkout')]) }}"
                                            data-on-message="<p>{{ translate('If enabled the billing address will be shown on the checkout page') }}</p>"
                                            data-off-message="<p>{{ translate('If disabled the billing address will be hidden from the checkout page') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>
                                </label>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <label
                                    class="d-flex justify-content-between align-items-start gap-3 border rounded p-3 user-select-none h-100 bg-white">
                                    <span>
                                        <h5 class="fw-medium text-dark fs-14 mb-1">{{ translate('Guest Checkout') }}</h5>
                                        <p class="mb-0 fs-12">
                                            {{ translate('Customers can check out without creating or logging into an account.') }}
                                        </p>
                                    </span>
                                    <label class="switcher" for="guest-checkout-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="guest_checkout"
                                            id="guest-checkout-status"
                                            {{ $guestCheckout ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/guest-checkout-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/guest-checkout-off.png') }}"
                                            data-on-title="{{ translate('By Turning ON Guest Checkout Mode') }}"
                                            data-off-title="{{ translate('By Turning Off Guest Checkout Mode') }}"
                                            data-on-message="<p>{{ translate('User can place order without login') }}</p>"
                                            data-off-message="<p>{{ translate('User cannot place order without login') }}</p>">
                                        <span class="switcher_control"></span>
                                    </label>

                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin-views.business-settings.order-settings._free-delivery-setup', ['context' => 'retail'])

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" id="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

    </div>
    @include("layouts.admin.partials.offcanvas._order-settings")
@endsection

@push('script')
    <script
        src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/order-settings.js') }}"></script>
@endpush
