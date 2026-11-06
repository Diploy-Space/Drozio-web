@extends('layouts.admin.app')

@section('title', translate('Firebase Authentication'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-bell text-primary d-flex"></i>
                {{ translate('Firebase') }}
            </h2>
        </div>

        @include('admin-views.third-party._third-party-firebase-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('Set up your Firebase configuration before using these features.') }}
                <a href="{{ route('admin.settings.third-party.firebase-configuration.setup') }}"
                   class="text-decoration-underline fw-semibold">
                    {{ translate('Firebase Configuration') }}
                </a>
            </span>
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-info text-warning"></i>
            <span>
                {{ translate('The Web API key must be correct for OTP authentication.') }}
            </span>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.third-party.firebase-configuration.update') }}" method="post">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">
                                {{ translate('Firebase Authentication') }}
                            </h3>
                            <p class="mb-0 fs-12">
                                {{ translate('If this feature is active customers get the otp through firebase') }}.
                            </p>
                        </div>
                        <div>
                            <label class="switcher" for="otp-verification-status">
                                <input
                                    class="switcher_input custom-modal-plugin firebase-auth-verification"
                                    type="checkbox" value="1" name="status"
                                    id="otp-verification-status"
                                    data-status="{{ $configStatus ? 'true' : 'false' }}"
                                    {{ $firebaseOTPVerification && $firebaseOTPVerification['status'] ? 'checked' : '' }}
                                    data-route="{{ route('admin.settings.third-party.firebase-configuration.config-status-validation') }}"
                                    data-verification="firebase-auth"
                                    data-key="firebase"
                                    data-modal-type="input-change"
                                    data-on-image="{{ dynamicAsset(path: 'public/assets/back-end/img/firebase-settings.png') }}"
                                    data-off-image="{{ dynamicAsset(path: 'public/assets/back-end/img/firebase-settings.png') }}"
                                    data-on-title="{{ translate('Turn ON Firebase otp Verification').'?' }}"
                                    data-off-title="{{ translate('Turn OFF Firebase otp Verification').'?' }}"
                                    data-on-message="<p>{{ translate('Firebase Auth Verification') }}</p>"
                                    data-off-message="<p>{{ translate('Firebase Auth Verification off') }}</p>">
                                <span class="switcher_control"></span>
                            </label>
                        </div>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded mb-4">
                        <div class="form-group mb-0">
                            <label class="form-label" for="web_api_key">{{ translate('Web Api Key') }} <span class="text-danger">*</span>
                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('Enter your firebase web api key') }}" data-bs-title="{{ translate('Enter your firebase web api key') }}">
                                    <i class="fi fi-sr-info"></i>
                                </span>
                            </label>
                            <input type="text" class="form-control" name="web_api_key" id="web_api_key"
                                   placeholder="{{ translate('Ex') }}: AIzaSyD-xxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off"
                                   {{ config('app.mode') != 'demo' ? '' : 'disabled' }}
                                   value="{{ showDemoModeInputValue(value: $firebaseOTPVerification && $firebaseOTPVerification['web_api_key'] ? $firebaseOTPVerification['web_api_key'] : '') }}">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3 mb-100">
                        <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                            {{ translate('Reset') }}
                        </button>
                        <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save Information') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="firebaseAuthConfigValidation" tabindex="-1" aria-labelledby="toggle-modal"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                        <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                                data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body px-4 px-sm-5 pt-0">

                    </div>
                </div>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._firebase-auth-setup")
@endsection
