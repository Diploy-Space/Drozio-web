@extends('layouts.admin.app')

@section('title', translate('Login Settings'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/login-settings.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-sign-in-alt text-primary d-flex"></i>
                {{ translate('Login Settings') }}
            </h2>
        </div>
        @include('admin-views.system-setup.login-settings.partials.login-settings-menu')

        <form action="{{ route('admin.settings.system-setup.login-settings.customer-login-setup') }}" method="post"
              enctype="multipart/form-data" id="customer-login-setup-update">
            @csrf

            <div class="card mb-3">
                <div class="card-header py-3">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-lock text-primary d-flex"></i>
                        {{ translate('Login Setup') }}
                    </h3>
                    <p class="mb-0 fs-12 text-capitalize text-muted">
                        {{ translate('Set up the login options for customer access to the system.') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-sm-20 gap-3">
                        <div class="card card-sm shadow-1">
                            <div class="card-body">
                                <div class="pb-12 pb-sm-20">
                                    <h3>{{ translate('Choose how to login') }}</h3>
                                    <p class="mb-0 fs-12">
                                        {{ translate('The option you select becomes the customer login method.') }}
                                    </p>
                                </div>
                                <div class="pb-12 pb-sm-20">
                                    <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                        <i class="fi fi-sr-bulb text-warning fs-16"></i>
                                        <span>
                                            {{ translate('At least one login method must remain active for the customer.') }}
                                            {{ translate('Otherwise they will be unable to log in to the system') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="p-1">
                                        <div class="row g-3">
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-check d-flex gap-10 login-option-card h-100">
                                                    <input class="form-check-input checkbox--input checkbox--input-lg login-option-type" type="checkbox"
                                                        name="manual_login"
                                                        id="customer-manual-login"
                                                        value="1"
                                                        {{ $loginOptions['manual_login'] ? 'checked' : '' }}
                                                    >
                                                    <div class="flex-grow-1">
                                                        <label for="customer-manual-login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                            {{ translate('Manual Login') }}
                                                        </label>
                                                        <p class="fs-12 mb-0">
                                                            {{ translate('Lets customers register and sign in with an email and password.') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-check d-flex gap-10 login-option-card h-100">
                                                   <input type="checkbox"
                                                            name="otp_login"
                                                            id="customer-otp-login"
                                                            value="1"
                                                            class="form-check-input checkbox--input checkbox--input-lg {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                          data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                            data-key="otp-login"
                                                            {{ $loginOptions['otp_login'] ? 'checked' : '' }}
                                                        >
                                                    <div class="flex-grow-1">
                                                        <label for="customer-otp-login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                            {{ translate('OTP Login') }}
                                                        </label>
                                                        <p class="fs-12 mb-0">
                                                           {{ translate('Customers sign in with a phone number and OTP, not a password.') }} <a href="{{ route('admin.settings.third-party.sms-module') }}" target="_blank" class="text-decoration-underline text-capitalize">{{ translate('Configure sms Setup') }}</a> {{ translate('Here') }}.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div class="form-check d-flex gap-10 login-option-card h-100">
                                                    <input class="form-check-input checkbox--input checkbox--input-lg login-option-type" type="checkbox"
                                                        name="social_login"
                                                        id="customer-social-login"
                                                        value="1"
                                                        {{ $loginOptions['social_login'] ? 'checked' : '' }}
                                                    >
                                                    <div class="flex-grow-1">
                                                        <label for="customer-social-login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                            {{ translate('Social Media Login') }}
                                                        </label>
                                                        <p class="fs-12 mb-0">
                                                            {{ translate('Customers sign in with a social media account.') }}
                                                            <a  href="{{ route('admin.settings.third-party.social-login.view') }}" target="_blank" class="text-decoration-underline">{{ translate('Configure social media setup') }}</a>
                                                            {{ translate('Here') }}.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-sm shadow-1 social-media-login-container {{ $loginOptions['social_login'] ? '' : 'd--none' }}">
                            <div class="card-body">
                                <div class="pb-12 pb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div>
                                        <h3>{{ translate('Social media login setup') }}</h3>
                                        <p class="mb-0 fs-12">
                                           {{ translate('The option you select becomes the customer login method.') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.settings.third-party.social-login.view') }}" class="text-decoration-underline" target="_blank">
                                        {{ translate('Connect 3rd party login system from here') }}
                                    </a>
                                </div>
                                <div class="pb-12 pb-sm-20">
                                    <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                        <i class="fi fi-sr-bulb text-warning fs-16"></i>
                                        <span>
                                            {{ translate('Choose the social login options offered to customers.') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="p-1">
                                        <div class="row g-3">
                                            <div class="col-xl-4 col-md-6">
                                                <div
                                                    @if(!$configStatus['google'])
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-title="{{ translate('Google login is currently disabled from configure 3rd party social login options.') }}"
                                                    @endif
                                                >
                                                    <div class="form-check d-flex gap-10 login-option-card h-100 {{ $configStatus['google'] ? '' : 'disabled' }}">
                                                        <input type="checkbox"
                                                            name="google_login"
                                                            data-status="{{ $configStatus['google'] ? 'true' : 'false' }}"
                                                            id="google_login"
                                                            value="1"
                                                            class="form-check-input checkbox--input checkbox--input-lg {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                            data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                            data-key="google"
                                                            {{ $socialMediaLoginOptions['google'] && $configStatus['google'] ? 'checked' : '' }}
                                                        >
                                                        <div class="flex-grow-1">
                                                            <label for="google_login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                                {{ translate('Google') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Customers sign in with their existing Google credentials.') }}.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div
                                                    @if(!$configStatus['facebook'])
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-title="{{ translate('Facebook login is currently disabled from configure 3rd party social login options.') }}"
                                                    @endif
                                                >
                                                    <div class="form-check d-flex gap-10 login-option-card h-100 {{ $configStatus['facebook'] ? '' : 'disabled' }}">
                                                        <input type="checkbox"
                                                            name="facebook_login"
                                                            data-status="{{ $configStatus['facebook'] ? 'true' : 'false' }}"
                                                            id="facebook_login"
                                                            value="1"
                                                            class="form-check-input checkbox--input checkbox--input-lg {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                            data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                            data-key="facebook"
                                                            {{ $socialMediaLoginOptions['facebook'] && $configStatus['facebook'] ? 'checked' : '' }}
                                                        >
                                                        <div class="flex-grow-1">
                                                            <label for="facebook_login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                                {{ translate('Facebook') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                               {{ translate('Customers sign in with their existing Facebook credentials.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4 col-md-6">
                                                <div
                                                    @if(!$configStatus['apple'])
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-title="{{ translate('Apple login is currently disabled from configure 3rd party social login options.') }}"
                                                    @endif
                                                >
                                                    <div class="form-check d-flex gap-10 login-option-card h-100 {{ $configStatus['apple'] ? '' : 'disabled' }}">
                                                        <input type="checkbox"
                                                            name="apple_login"
                                                            data-status="{{ $configStatus['apple'] ? 'true' : 'false' }}"
                                                            id="apple_login"
                                                            value="1"
                                                            class="form-check-input checkbox--input checkbox--input-lg {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                            data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                            data-key="apple"
                                                            {{ $socialMediaLoginOptions['apple'] && $configStatus['apple'] ? 'checked' : '' }}
                                                        >
                                                        <div class="flex-grow-1">
                                                            <label for="apple_login" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                                {{ translate('Apple') }}
                                                            </label>
                                                            <p class="fs-12 mb-0">
                                                                {{ translate('Customers sign in with Apple credentials, on Apple devices only.') }}
                                                            </p>
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
                </div>
            </div>

            <div class="card mb-20">
                <div class="card-header py-3">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-check text-primary d-flex"></i>
                        {{ translate('Verification') }}
                    </h3>
                    <p class="mb-0 fs-12 text-capitalize text-muted">
                        {{ translate('The customer verifies with the option you select.') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-sm-20 gap-3">
                        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                            <i class="fi fi-sr-bulb text-warning fs-16"></i>
                            <span>
                                {{ translate('Verification is optional. If you select none, customers sign up without verifying.') }}
                            </span>
                        </div>
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="p-1">
                                <div class="row g-3">
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-check d-flex gap-10 login-option-card h-100">
                                            <input type="checkbox"
                                                    name="email_verification"
                                                    id="email-verification"
                                                    value="1"
                                                    class="form-check-input checkbox--input checkbox--input-lg  {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                    data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                    data-key="email"
                                                {{ $emailVerification ? 'checked' : '' }}
                                            >
                                            <div class="flex-grow-1">
                                                <label for="email-verification" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                    {{ translate('Email Verification') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                   {{ translate('Customers must verify their email during sign-up.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-check d-flex gap-10 login-option-card h-100">
                                            <input type="checkbox"
                                                    name="phone_verification"
                                                   id="phone_verification"
                                                   value="1"
                                                   class="form-check-input checkbox--input checkbox--input-lg  {{ showDemoModeInputValue(value: 'social-media-status-checkbox') }} {{ getDemoModeFormButton(type: 'class') }}"
                                                   data-route="{{ route('admin.settings.system-setup.login-settings.config-status-validation') }}"
                                                   data-key="otp"
                                                {{ $phoneVerification ? 'checked' : '' }}
                                            >

                                            <div class="flex-grow-1">
                                                <label for="phone_verification" class="form-check-label text-dark fw-semibold mb-1 user-select-none cursor-pointer">
                                                    {{ translate('Phone Number Verification') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                    {{ translate('Customers must verify their phone with an OTP to sign up.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end trans3 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                            class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="customerLoginConfigValidation" tabindex="-1" aria-labelledby="toggle-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 p-2 rounded-circle" data-bs-dismiss="modal" aria-label="Close">
                        <i class="tio-clear"></i>
                    </button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">

                </div>
            </div>
        </div>
    </div>

    <span id="customer-login-setup-validation-msg"
          data-title="{{ translate('No login option selected') }}!"
          data-text="{{ translate('Please select at least one login option.') }}"
          data-ok="{{ translate('Okay') }}"
    ></span>
    <span class="select-google-or-facebook"
          data-text="{{ translate('Please select at least one between Google or Facebook.') }}"
          data-text-two="{{ translate('Please select at least one between Google or Facebook or Apple.') }}"
    ></span>

    <div class="modal fade" id="googleModal" tabindex="-1" aria-labelledby="toggle-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{  dynamicAsset(path: 'public/assets/new/back-end/img/modal/google-logo.png') }}" width="60" class="mb-20" alt="">
                        <h2 class="modal-title mb-3">{{ translate('Aet up google configuration first') }}</h2>
                        <div class="text-center">
                            {{ translate('Set up the SMS configuration first to enable OTP.') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-primary max-w-250 flex-grow-1"
                            onclick="closeModalAndRedirect(this)">
                            {{ translate('Go to google configuration') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="facebookModal" tabindex="-1" aria-labelledby="toggle-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{  dynamicAsset(path: 'public/assets/new/back-end/img/modal/facebook-logo.png') }}" width="60" class="mb-20" alt="">
                        <h2 class="modal-title mb-3">{{ translate('Set up facebook configuration first') }}</h2>
                        <div class="text-center">
                           {{ translate('Set up the Facebook configuration first to enable Facebook login.') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-primary max-w-250 flex-grow-1"
                            onclick="closeModalAndRedirect(this)">
                            {{ translate('Go to Facebook Configuration') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="appleModal" tabindex="-1" aria-labelledby="toggle-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                        data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <div class="d-flex flex-column align-items-center text-center mb-30">
                        <img src="{{  dynamicAsset(path: 'public/assets/new/back-end/img/modal/apple-logo.png') }}" width="60" class="mb-20" alt="">
                        <h2 class="modal-title mb-3">{{ translate('Set up apple configuration first') }}</h2>
                        <div class="text-center">
                          {{ translate('Set up the Apple ID configuration first to enable Apple login.') }}
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-primary max-w-250 flex-grow-1"
                            onclick="closeModalAndRedirect(this)">
                            {{ translate('Go to Apple Configuration') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._customer-login")
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/customer-login-setup.js')}}"></script>
@endpush
