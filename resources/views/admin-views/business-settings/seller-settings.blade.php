@extends('layouts.admin.app')

@section('title', translate('Vendors'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.vendor-settings.update-vendor-settings') }}" method="post" id="update-vendor-settings-form">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="mb-1">{{ translate('General Setup') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Complete the basic settings for vendors') }}
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-xl-6 col-md-6">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize">
                                            {{ translate('Enable vendor registration') }}
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('Enabling this option allows users to send requests to become registered') }}
                                        </span>
                                    </span>
                                    @php($vendorRegistration = getWebConfig('seller_registration'))

                                    <label class="switcher" for="vendor-registration">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="seller_registration"
                                            id="vendor-registration"
                                            {{ $vendorRegistration == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-modal-form="#update-vendor-settings-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/self-registrations-on.png') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/self-registrations-off.png') }}"
                                            data-on-title = "{{ translate('Want to Turn ON Self Registration') . '?' }}"
                                            data-off-title = "{{ translate('Want to Turn OFF Self Registration') . '?' }}"
                                            data-on-message = "<p>{{ translate('Enabling this option allows vendors to send requests to become registered') }}</p>"
                                            data-off-message = "<p>{{ translate('Vendors cannot send requests to register.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded p-12 p-sm-3 user-select-none h-100 bg-white">
                                    <span class="d-flex flex-column">
                                        <span class="fw-medium text-dark fs-14 mb-1 text-capitalize d-flex align-items-center gap-1">
                                            {{ translate('Show verified badge') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('If you enable this feature, a vendor verification option will appear on the vendor list and details page, allowing you to verify vendor based on their performance.') }}"
                                                  data-bs-title="{{ translate('If you enable this feature, a vendor verification option will appear on the vendor list and details page, allowing you to verify vendor based on their performance.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </span>
                                        <span class="mb-0 fs-12">
                                            {{ translate('If enabled show a verified badge on approved vendor.') }}
                                        </span>
                                    </span>

                                    <label class="switcher" for="vendor-verified-badge-status">
                                        <input
                                            class="switcher_input "
                                            type="checkbox" value="1" name="vendor_verified_badge_status"
                                            id="vendor-verified-badge-status"
                                            {{ $verifiedBadgeStatus ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-modal-form="#update-vendor-settings-form"
                                            data-on-title="{{ translate('Want to turn ON the verified badge') . '?' }}"
                                            data-off-title="{{ translate('Want to turn OFF the verified badge') . '?' }}"
                                            data-on-message="<p>{{ translate('A verified badge shows on approved vendors and the verification option appears on the vendor list and details page.') }}</p>"
                                            data-off-message="<p>{{ translate('The verified badge is hidden and the verification option is removed from the vendor list and details page.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="mb-3 mb-sm-20">
                        <h3 class="mb-1 text-capitalize">{{ translate('Forget Password Setup') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Setup how vendors can recover their forgotten passwords') }}.
                        </p>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="form-group mb-20px">
                            <label class="form-label mb-3 text-capitalize" for="">
                                {{ translate('Select Verification Option') }}
                            </label>
                            <div class="bg-white p-3 rounded">
                                <div class="row g-4">
                                    <div class="col-xl-6 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input class="form-check-input radio--input radio--input_lg" type="radio"
                                                name="vendor_forgot_password_method" id="verification_by_email"
                                                value="email"
                                                {{ getWebconfig(name: 'vendor_forgot_password_method') == 'email' ? 'checked' : '' }}>
                                            <div class="flex-grow-1">
                                                <label for="verification_by_email"
                                                    class="form-label text-dark fw-semibold mb-1">
                                                    {{ translate('Email') }}
                                                </label>
                                                <p class="fs-12 mb-2">
                                                    {{ translate('Vendor will recover his password through email') }}
                                                </p>
                                                <div
                                                    class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                    <i class="fi fi-sr-bulb text-warning fs-16"></i>
                                                    <span>
                                                        {{ translate('To configure email for this system visit') }}
                                                        <a href="{{ route('admin.settings.third-party.mail.index') }}" target="_blank"
                                                            class="text-decoration-underline fw-semibold">{{ translate('Email Configuration') }}</a>
                                                        {{ translate('Page') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-md-6">
                                        <div class="form-check d-flex gap-3">
                                            <input class="form-check-input radio--input radio--input_lg" type="radio"
                                                value="phone" name="vendor_forgot_password_method"
                                                id="verification_by_phone"
                                                {{ getWebconfig(name: 'vendor_forgot_password_method') == 'phone' ? 'checked' : '' }}>
                                            <div class="flex-grow-1">
                                                <label for="verification_by_phone"
                                                    class="form-label text-dark fw-semibold mb-1">
                                                    {{ translate('Phone') }} ({{ translate('OTP') }})
                                                </label>
                                                <p class="fs-12 mb-2">
                                                    {{ translate('Vendor will recover his password through OTP') }}
                                                </p>
                                                <div
                                                    class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                                    <i class="fi fi-sr-bulb text-warning fs-16"></i>
                                                    <span>
                                                        {{ translate('To configure phone for this system visit') }}
                                                        <a href="{{ route('admin.settings.third-party.sms-module') }}" target="_blank"
                                                            class="text-decoration-underline fw-semibold">
                                                            {{ translate('OTP Configuration') }}
                                                        </a>
                                                        {{ translate('Page') }}
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
            </div>

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @include("layouts.admin.partials.offcanvas._seller-settings")
@endsection
