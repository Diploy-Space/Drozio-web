@extends('layouts.admin.app')

@section('title', translate('ReCAPTCHA Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <form action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.captcha') : 'javascript:' }}"
              method="POST" id="recaptcha-status-form" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
            @csrf
            @php($config = isset($config) ? (array)json_decode($config['value']) : [])
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-xl-9">
                            <div class="d-flex align-items-center gap-3">
                                <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                    <i class="fi fi-sr-shield-check d-flex"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <h2 class="mb-0">{{ translate('reCAPTCHA') }}</h2>
                                        <span class="badge badge-soft-{{ ($config['status'] ?? 0) == 1 ? 'success' : 'secondary' }} fs-10">
                                            {{ ($config['status'] ?? 0) == 1 ? translate('Active') : translate('Inactive') }}
                                        </span>
                                    </div>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Users must pass a ReCAPTCHA check.') }}
                                        .
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-3">
                            <div class="mt-3 mt-md-0">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                                    <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                                    <label class="switcher " for="recaptcha-id">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="recaptcha-id"
                                            {{ $config['status'] == 1? 'checked' : '' }}
                                            data-modal-type="input-change-form"
                                            data-modal-form="#recaptcha-status-form"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-on.svg') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-off.svg') }}"
                                            data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('ReCAPTCHA Setup')]) }}?"
                                            data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('ReCAPTCHA Setup')]) }}?"
                                            data-on-message="<p>{{ translate('Turning on ReCAPTCHA protects your site from spam.') }}</p>"
                                            data-off-message="<p>{{ translate('Turning off ReCAPTCHA lowers your spam protection.') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded-8 mt-3 d-flex gap-2 align-items-start">
                        <i class="fi fi-sr-info text-info mt-1"></i>
                        <div>
                            <h4 class="fw-medium fs-14 mb-1">{{ translate('Version 3 is available. Set up ReCAPTCHA v3.') }}</h4>
                            <span>{{ translate('Set up v3, or the default ReCAPTCHA is shown.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3 justify-content-between align-items-center flex-wrap mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                <i class="fi fi-sr-key d-flex"></i>
                            </span>
                            <div>
                                <h2 class="mb-1 text-capitalize">{{ translate('Google ReCAPTCHA credentials') }}</h2>
                                <p class="mb-0 fs-12">
                                    {{ translate('Add your Google ReCAPTCHA credentials to activate this feature.') }}
                                    .
                                </p>
                            </div>
                        </div>
                        <a data-bs-toggle="modal" href="#getRecaptchaCredentials"
                           class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 text-capitalize">
                            <i class="fi fi-sr-info d-flex"></i>
                            {{ translate('How to get credential') }}
                        </a>
                    </div>
                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="">{{translate('Site Key')}}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                              aria-label="{{ translate('Enter your site key') }}" data-bs-title="{{ translate('Enter your site key') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="site_key"
                                           placeholder="{{ translate('Enter your site key') }}"
                                           data-required-msg="{{translate('Site key field is required')}}"
                                           value="{{config('app.mode')!='demo'?$config['site_key']??"":''}}" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="">{{translate('Secret key')}}
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                              aria-label="{{ translate('Enter your secret key') }}" data-bs-title="{{ translate('Enter your secret key') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control pe-5" name="secret_key"
                                               autocomplete="off"
                                               placeholder="{{ translate('Enter your secret key') }}"
                                               data-required-msg="{{translate('Secret field is required')}}"
                                               value="{{config('app.mode')!='demo'?$config['secret_key']??"":''}}" required>
                                        <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary w-120 px-4">{{translate('Reset')}}</button>
                        <button type="{{config('app.mode')!='demo'?'submit':'button'}}"
                                class="btn btn-primary w-120 px-4 {{config('app.mode')!= 'demo'? '' : 'call-demo-alert'}}"
                        >{{translate('Save')}}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal fade" id="getRecaptchaCredentials" tabindex="-1" aria-labelledby="getInformationModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0" data-bs-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="swiper instruction-carousel pb-3">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80"
                                         src="{{dynamicAsset(path: 'public/assets/new/back-end/img/modal/google-analytics.png')}}"
                                         loading="lazy" decoding="async" alt="{{ translate('reCAPTCHA') }}">
                                    <h4 class="lh-md mb-3 text-capitalize">{{translate('Google ReCAPTCHA instructions')}}</h4>
                                    <ol class="pl-4 instructions-list">
                                        <li>
                                            {{translate('To get site key and secret key Go to the Credentials page')}}
                                            (<a href="https://www.google.com/recaptcha/admin/create"
                                                target="_blank">{{translate('Click Here')}}</a>)
                                        </li>
                                        <li>{{translate('Add a label (Ex: abc company)')}}</li>
                                        <li>{{translate('Select ReCAPTCHA v3 as the ReCAPTCHA type')}}</li>
                                        <li>{{translate('Select sub type').':'.translate('Im not a robot checkbox')}}</li>
                                        <li>{{translate('Add Domain (For ex: demo.6amtech.com)')}}</li>
                                        <li>{{translate('Check in Accept the ReCAPTCHA Terms of Service')}}</li>
                                        <li>{{translate('Press Submit')}}</li>
                                        <li>{{translate('Copy the Site Key and Secret Key, paste them below and save.').'.'}}</li>
                                    </ol>
                                    <div class="d-flex justify-content-center mt-4">
                                        <button type="button" class="btn btn-primary px-5"
                                                data-bs-dismiss="modal">{{translate('Got it')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._3rd-party-recaptcha-setup")
@endsection
