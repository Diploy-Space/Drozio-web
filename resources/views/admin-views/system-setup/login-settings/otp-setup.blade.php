@extends('layouts.admin.app')

@section('title', translate('OTP setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-sign-in-alt text-primary d-flex"></i>
                {{ translate('Login Settings') }}
            </h2>
        </div>
        @include('admin-views.system-setup.login-settings.partials.login-settings-menu')

        <form action="{{ route('admin.settings.system-setup.login-settings.otp-setup') }}" method="post"
              enctype="multipart/form-data" id="update-settings" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-check text-primary d-flex"></i>
                        {{ translate('OTP setup') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('How many times a user can enter the OTP.') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-xl-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="maximum_otp_hit">{{ translate('Maximum OTP Hit') }}
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              aria-label="{{ translate('Set how many times a user can enter the wrong OTP.').'.'.translate('After reaching this limit the user will be blocked for a time') }}"
                                              data-bs-title="{{ translate('Set how many times a user can enter the wrong OTP.').'.'.translate('After reaching this limit the user will be blocked for a time') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="{{ $otpFields['maximum_otp_hit']['min'] }}" max="{{ $otpFields['maximum_otp_hit']['max'] }}" value="{{$maximumOtpHit}}" name="maximum_otp_hit"
                                           data-min-msg="{{ $otpFields['maximum_otp_hit']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['maximum_otp_hit']['range_message'] }}"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Maximum OTP Hit')]) }}"
                                           class="form-control" placeholder="{{translate('Ex').':'.'5'}}" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['maximum_otp_hit']['min'] }} - {{ $otpFields['maximum_otp_hit']['max'] }}</small>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                @php($firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [])
                                <div class="form-group">
                                    <label class="form-label" for="otp_resend_time">
                                        {{translate('OTP Resend Time')}} ({{translate('Sec')}})
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              @if ($firebaseOTPVerification && $firebaseOTPVerification['status'])
                                                  aria-label="{{ translate('Use at least 60 seconds for OTP resend with firebase auth.') }}"
                                              data-bs-title="{{ translate('Use at least 60 seconds for OTP resend with firebase auth.') }}"
                                              @else
                                                  aria-label="{{ translate('Set the time for requesting a new OTP') }}"
                                              data-bs-title="{{ translate('Set the time for requesting a new OTP') }}"
                                                @endif
                                                >
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="10" max="86400" value="{{$otpResendTime}}"
                                           name="otp_resend_time"
                                           data-min-msg="{{ $otpFields['otp_resend_time']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['otp_resend_time']['range_message'] }}" class="form-control"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('OTP Resend Time')]) }}"
                                           placeholder="{{ translate('Ex') }}: 5" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['otp_resend_time']['min'] }} - {{ $otpFields['otp_resend_time']['max'] }} {{ translate('Sec') }}</small>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label"
                                           for="temporary_block_time">{{ translate('Temporary Block Time') }}
                                        ({{translate('Sec')}})
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              aria-label="{{ translate('Within this time users cannot make OTP requests again') }}"
                                              data-bs-title="{{ translate('Within this time users cannot make OTP requests again') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="10" max="86400" value="{{$temporaryBlockTime}}"
                                           name="temporary_block_time"
                                           data-min-msg="{{ $otpFields['temporary_block_time']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['temporary_block_time']['range_message'] }}" class="form-control"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Temporary Block Time')]) }}"
                                           placeholder="{{ translate('Ex') }}: 120" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['temporary_block_time']['min'] }} - {{ $otpFields['temporary_block_time']['max'] }} {{ translate('Sec') }}</small>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label"
                                           for="otp_expiry_time">{{ translate('OTP Expiry Time') }}
                                        ({{translate('Sec')}})
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              aria-label="{{ translate('How long an OTP stays valid after it is sent') }}"
                                              data-bs-title="{{ translate('How long an OTP stays valid after it is sent') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="30" max="86400" value="{{$otpExpiryTime}}"
                                           name="otp_expiry_time"
                                           data-min-msg="{{ $otpFields['otp_expiry_time']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['otp_expiry_time']['range_message'] }}" class="form-control"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('OTP Expiry Time')]) }}"
                                           placeholder="{{ translate('Ex') }}: 300" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['otp_expiry_time']['min'] }} - {{ $otpFields['otp_expiry_time']['max'] }} {{ translate('Sec') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-lock text-primary d-flex"></i>
                        {{ translate('Login Setup') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('How many times a user can try to log in.') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-xl-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label text-capitalize"
                                           for="maximum_login_hit">{{ translate('Maximum login hit') }}
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              aria-label="{{ translate('Set the maximum unsuccessful login attempts users can make using wrong passwords.') }} {{ translate('After reaching this limit they will be blocked for a time') }}"
                                              data-bs-title="{{ translate('Set the maximum unsuccessful login attempts users can make using wrong passwords.') }} {{ translate('After reaching this limit they will be blocked for a time') }}"
                                        >
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="1" max="20" value="{{$maximumLoginHit}}"
                                           placeholder="{{ translate('Ex') }}: 5"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Maximum login hit')]) }}"
                                           name="maximum_login_hit"
                                           data-min-msg="{{ $otpFields['maximum_login_hit']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['maximum_login_hit']['range_message'] }}" class="form-control" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['maximum_login_hit']['min'] }} - {{ $otpFields['maximum_login_hit']['max'] }}</small>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label text-capitalize" for="maximum_otp_hit">
                                        {{translate('Temporary login block time')}} ({{translate('Sec')}})
                                        <span class="tooltip-icon"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="right"
                                              aria-label="{{ translate('How long a user is locked out after too many login attempts.') }}"
                                              data-bs-title="{{ translate('How long a user is locked out after too many login attempts.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                    </label>
                                    <input type="number" min="10" max="86400" value="{{$temporaryLoginBlockTime}}"
                                           placeholder="{{translate('Ex').':'.'1210'}}"
                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Temporary login block time')]) }}"
                                           name="temporary_login_block_time"
                                           data-min-msg="{{ $otpFields['temporary_login_block_time']['range_message'] }}"
                                           data-max-msg="{{ $otpFields['temporary_login_block_time']['range_message'] }}" class="form-control" required>
                                    <small class="d-block fs-12 text-muted mt-1">{{ translate('Allowed range') }}: {{ $otpFields['temporary_login_block_time']['min'] }} - {{ $otpFields['temporary_login_block_time']['max'] }} {{ translate('Sec') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{config('app.mode')!='demo'?'submit':'button'}}"
                            class="btn btn-primary px-3 px-sm-4 {{config('app.mode') != 'demo'?'':'call-demo-alert'}}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @include("layouts.admin.partials.offcanvas._otp-setup")
@endsection
