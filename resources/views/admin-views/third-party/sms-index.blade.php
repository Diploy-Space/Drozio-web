@extends('layouts.admin.app')

@section('title', translate('SMS Configuration'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-2">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('This sms gateway will work for') }}
                <a href="{{ route('admin.settings.system-setup.login-settings.customer-login-setup') }}" target="_blank"
                   class="text-decoration-underline fw-semibold">
                    {{ translate('OTP verification') }}
                </a> {{ translate('Or') }}
                <a href="{{ route('admin.settings.system-setup.push-notification.index') }}" target="_blank"
                   class="text-decoration-underline fw-semibold">
                    {{ translate('Notification') }}
                </a> {{ translate('Through SMS') }}
            </span>
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-info text-warning"></i>
            <span>
                {{ translate('Recheck the details, or ask your SMS gateway provider for help.') }}
            </span>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="h-100 d-flex flex-column justify-content-between gap-4">
                            <div class="">
                                <h2>{{ translate('SMS Configuration') }}</h2>
                                <p class="fs-12 mb-0">
                                    {{ translate('Choose the sms model for OTP and other messages.') }}
                                </p>
                            </div>
                            @if(!($firebaseOtpVerification['status'] == 1 || count(collect($smsGateways)->where('is_active', 1)) > 0))
                                <div
                                    class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                    <i class="fi fi-sr-triangle-warning text-danger"></i>
                                    <span>
                                    {{ translate('Not set up yet. Configure it first.') }}.
                                </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="p-12 p-sm-20 bg-section rounded">
                            <div class="form-group mb-20px">
                                <div class="mb-3 d-flex gap-3 flex-wrap justify-content-between align-items-center">
                                    <label class="form-label mb-0" for="">{{ translate('Select SMS Configuration Model') }}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                              aria-label="{{ translate('Select SMS Configuration Model') }}" data-bs-title="{{ translate('Select SMS Configuration Model') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#send-mail-confirmation-modal">
                                        <i class="fi fi-sr-paper-plane"></i>
                                        {{translate('Send test SMS')}}
                                    </button>
                                </div>
                                <div class="bg-white p-3 rounded">
                                    <div class="row g-4">
                                        <div class="col-xl-6 col-md-6">
                                            <div class="form-check d-flex gap-3">
                                                <form action="{{ route('admin.settings.third-party.firebase-configuration.update') }}" class=""
                                                      method="post" id="sms-configuration-third-party-form">
                                                    @csrf
                                                    <input type="hidden" name="web_api_key" value="{{ $firebaseOtpVerification['web_api_key'] }}">
                                                    <input
                                                        class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                        type="radio" value="0" name="status"
                                                        id=""
                                                        {{ $firebaseOtpVerification['status'] == 0 ? 'checked' : '' }}
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#sms-configuration-third-party-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/config-status-change.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/config-status-change.png') }}"
                                                        data-on-title="{{ translate('Are you sure you want to use third party gateway for sms') }} ?"
                                                        data-off-title="{{ translate('Are you sure you want to use third party gateway for sms') }} ?"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}">
                                                </form>
                                                <div class="flex-grow-1">
                                                    <label for="verification_by_email"
                                                           class="form-label text-dark fw-semibold mb-1">
                                                        {{ translate('3rd Party') }}
                                                    </label>
                                                    <p class="fs-12 mb-3">
                                                        {{ translate('Set up an SMS module below to activate this feature.') }}
                                                    </p>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-md-6">
                                            <div class="form-check d-flex gap-3">
                                                <form action="{{ route('admin.settings.third-party.firebase-configuration.update') }}" class=""
                                                      method="post" id="sms-configuration-firebase-form">
                                                    @csrf
                                                    <input type="hidden" name="web_api_key" value="{{ $firebaseOtpVerification['web_api_key'] }}">
                                                    <input
                                                        class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                        type="radio" value="1" name="status"
                                                        id=""
                                                        {{ $firebaseOtpVerification['status'] == 1 ? 'checked' : '' }}
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#sms-configuration-firebase-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/config-status-change.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/config-status-change.png') }}"
                                                        data-on-title="{{ translate('Are you sure you want to use firebase for sms') }} ?"
                                                        data-off-title="{{ translate('Are you sure you want to use firebase for sms') }} ?"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}">
                                                </form>
                                                <div class="flex-grow-1">
                                                    <label for="verification_by_phone"
                                                           class="form-label text-dark fw-semibold mb-1">
                                                        {{ translate('Firebase OTP') }}
                                                    </label>
                                                    <p class="fs-12 mb-3">
                                                        {{ translate('Setup necessary') }} <a
                                                            href="{{ route('admin.settings.third-party.firebase-configuration.authentication') }}"
                                                            target="_blank"
                                                            class="fw-semibold text-decoration-underline">{{ translate('Firebase Configurations') }}</a>.
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

        <div class="row g-3">
            @foreach($smsGateways as $key => $smsConfig)
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{route('admin.settings.third-party.addon-sms-set')}}" method="POST"
                                  id="sms-{{$smsConfig['key_name']}}-form" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
                                @csrf @method('PUT')
                                    <?php
                                    $imgPath = 'sms/' . $smsConfig['key_name'] . '.png';
                                    $logoPath = 'sms/logos/' . $smsConfig['key_name'] . '.png';
                                    ?>
                                <div class="view-details-container">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img class="method-icon rounded-circle border bg-white p-1 flex-shrink-0 object-fit-contain"
                                                 loading="lazy" decoding="async"
                                                 src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/' . $logoPath) }}"
                                                 alt="{{ str_replace('_', ' ', $smsConfig['key_name']) }}">
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h3 class="mb-0 text-capitalize">
                                                        {{ str_replace('_', ' ', $smsConfig['key_name'])}}
                                                    </h3>
                                                    <span class="badge badge-soft-{{ $smsConfig['is_active'] == 1 ? 'success' : 'secondary' }} fs-10">
                                                        {{ $smsConfig['is_active'] == 1 ? translate('Active') : translate('Inactive') }}
                                                    </span>
                                                </div>
                                                <p class="mb-0 fs-12 text-capitalize">
                                                    {{ translate('Setup') }} {{ str_replace('_', ' ', $smsConfig['key_name'])}} {{ translate('As the SMS Gateway') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <a href="javascript:"
                                               class="fs-12 fw-semibold d-flex align-items-end view-btn ">
                                                {{ translate('View') }}
                                                <i class="fi fi-rr-arrow-small-down fs-16 trans3"></i>
                                            </a>
                                            <label class="switcher mx-auto" for="{{$smsConfig['key_name']}}">
                                                <input
                                                    class="switcher_input custom-modal-plugin"
                                                    type="checkbox" value="1" name="status"
                                                    id="{{$smsConfig['key_name']}}"
                                                    {{ $smsConfig['is_active'] == 1 ? 'checked' : '' }}
                                                    data-modal-type="input-change-form"
                                                    data-modal-form="#sms-{{$smsConfig['key_name']}}-form"
                                                    data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/'. $imgPath) }}"
                                                    data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/' .$imgPath) }}"
                                                    data-on-title="{{translate('Want to Turn ON').' '.ucwords(str_replace('_',' ',$smsConfig['key_name'])).' '.translate('As the SMS Gateway').'?'}}"
                                                    data-off-title="{{translate('Want to Turn OFF').' '.ucwords(str_replace('_',' ',$smsConfig['key_name'])).' '.translate('As the SMS Gateway').'?'}}"
                                                    data-on-message="<p>{{translate('If enabled system can use this SMS Gateway')}}</p>"
                                                    data-off-message="<p>{{translate('If disabled system cannot use this SMS Gateway')}}</p>"
                                                    data-on-button-text="{{ translate('Turn On') }}"
                                                    data-off-button-text="{{ translate('Turn Off') }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="view-details mt-3 mt-sm-4">
                                        <div class="p-12 p-sm-20 bg-section rounded">
                                            <input name="gateway" value="{{$smsConfig['key_name']}}" class="d-none">
                                            <input name="mode" value="live" class="d-none">
                                            @php($skip=['gateway','mode','status'])
                                            @php($secretKeywords=['secret','password','pass','token','key','pin','sid','auth','hash','hmac','signature','private'])
                                            @foreach($smsConfig['live_values'] as $keyName => $value)
                                                @if(!in_array($keyName, $skip))
                                                    @php($isSecret = \Illuminate\Support\Str::contains(strtolower($keyName), $secretKeywords))
                                                    <div class="form-group">
                                                        <label for=""
                                                               class="form-label">{{ucwords(str_replace('_',' ',$keyName))}}
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        @if($isSecret)
                                                            <div class="position-relative">
                                                                <input type="password" class="form-control pe-5"
                                                                       name="{{$keyName}}" autocomplete="off"
                                                                       placeholder="{{ucwords(str_replace('_',' ',$keyName))}}"
                                                                       value="{{ showDemoModeInputValue(value: $value) }}" data-required-msg="{{ translate('The :field field is required', ['field' => ucwords(str_replace('_', ' ', $keyName))]) }}" required>
                                                                <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                                                    <i class="fi fi-sr-eye"></i>
                                                                </span>
                                                            </div>
                                                        @else
                                                            <input type="text" class="form-control"
                                                                   name="{{$keyName}}"
                                                                   placeholder="{{ucwords(str_replace('_',' ',$keyName))}}"
                                                                   value="{{ showDemoModeInputValue(value: $value) }}"  data-required-msg="{{ translate('The :field field is required', ['field' => ucwords(str_replace('_', ' ', $keyName))]) }}" required>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                                            <button type="reset" class="btn btn-secondary w-120 px-4">
                                                {{ translate('Reset') }}
                                            </button>
                                            <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                                    class="btn btn-primary w-120 px-4 {{ getDemoModeFormButton(type: 'class') }}">
                                                <i class="fi fi-sr-disk"></i>
                                                {{ translate('Save') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="send-mail-confirmation-modal" tabindex="-1"
     aria-labelledby="send-mail-confirmation-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0 btn-circle bg-section2 shadow-none"
                            data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body px-20 py-0 mb-30">
                    <form action="{{ route('admin.settings.third-party.send-test-sms') }}" method="post">
                        @csrf
                        <div class="d-flex flex-column gap-sm-20 gap-3">
                            <div>
                                <h3>{{ translate('Send test SMS') }}</h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('Insert a valid phone number to get sms') }}
                                </p>
                            </div>
                            <div
                                class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                                <i class="fi fi-sr-lightbulb-on text-info"></i>
                                <span>
                                    {{ translate('Configured for SMS. Send a test to confirm messages arrive.') }}.
                                </span>
                            </div>
                            <div class="p-12 p-sm-20 bg-section rounded d-flex flex-wrap gap-2 justify-content-end flex-column">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="">{{ translate('Phone') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="tel" name="phone"
                                               value=""
                                               placeholder="01xxxxxxxx">

                                        @if($firebaseOtpVerification && $firebaseOtpVerification['status'])
                                            <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                                        @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                                            <div class="dynamic-default-and-recaptcha-section">
                                                <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="login"
                                                       data-input="#login-default-captcha-section"
                                                       data-default-captcha="#login-default-captcha-section"
                                                >

                                                <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                                     data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                                     data-base-url="{{ route('g-recaptcha-session-store') }}"
                                                     data-session="{{ 'adminSMSRecaptchaSessionKey' }}"
                                                >
                                                </div>
                                            </div>
                                        @else
                                            <div class="row mt-2 g-2">
                                                <div class="col-8">
                                                    <input type="text" class="form-control form-control-lg form-control-focus-none"
                                                           id="admin-sms-recaptcha-input"
                                                           name="default_captcha_value" value="" required
                                                           placeholder="{{ translate('Enter ReCAPTCHA value') }}">
                                                </div>
                                                <div class="col-4 input-icons bg-white rounded">
                                                    <a class="get-login-recaptcha-verify cursor-pointer get-recaptcha-session-auto-fill user-select-none"
                                                       data-link="{{ route('g-recaptcha-session-store') }}"
                                                       data-session="{{ 'adminSMSRecaptchaSessionKey' }}"
                                                       data-input="#admin-sms-recaptcha-input"
                                                    >
                                                        <img
                                                            src="{{ route('g-recaptcha-session-store').'?sessionKey=adminSMSRecaptchaSessionKey' }}"
                                                            loading="lazy" decoding="async"
                                                            class="input-field w-90 h-40 p-0 rounded" id="default_sms_recaptcha_id" alt="{{ translate('reCAPTCHA') }}">
                                                        <i class="fi fi-rr-refresh"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                               <div>
                                   <button type="submit"  class="btn btn-primary px-4 min-w-120 h-40">
                                       {{ translate('Send SMS') }}
                                   </button>
                               </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._3rd-party-sms-setup")

    <span id="payment-gateway-published-status" data-status="{{ $paymentGatewayPublishedStatus == 1 }}"></span>
    <span id="route-g-recaptcha-session-store" data-route="{{ route('get-session-recaptcha-code') }}"
          data-mode="{{ config('app.mode') }}"
    ></span>

    <span id="message-please-check-recaptcha" data-text="{{ translate('Please check the ReCAPTCHA') }}"></span>
    <span id="message-copied_success" data-text="{{ translate('Copied successfully') }}"></span>
    <span id="route-get-session-recaptcha-code" data-route="{{ route('get-session-recaptcha-code') }}"
          data-mode="{{ config('app.mode') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/third-party/sms-setup.js') }}"></script>
    @php($recaptcha = getWebConfig(name: 'recaptcha'))
    <span id="get-google-recaptcha-key" data-value="{{ isset($recaptcha['status']) && $recaptcha['status'] == 1 ? $recaptcha['site_key'] ?? '' : '' }}"
          data-default-captcha-required="{{ session(\App\Services\RecaptchaService::FALLBACK_SESSION_KEY) ? 1 : 0 }}"></span>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.js') }}"></script>
@endpush
